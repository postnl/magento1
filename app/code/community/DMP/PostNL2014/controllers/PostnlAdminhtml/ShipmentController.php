<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@postnl-plugins.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@postnl-plugins.nl for more information.
 *
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class DMP_PostNL2014_PostnlAdminhtml_ShipmentController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Used module name in current adminhtml controller.
     */
    protected $_usedModuleName = 'DMP_PostNL2014';

    /**
     * @var array
     */
    protected $_warnings = array();

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/dmp_postnl');
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    /**
     * @param array $warnings
     *
     * @return $this
     */
    public function setWarnings(array $warnings)
    {
        $this->_warnings = $warnings;

        return $this;
    }

    /**
     * @param array|string $warning
     *
     * @return $this
     */
    public function addWarning($warning)
    {
        if (!is_array($warning)) {
            $warning = array(
                'entity_id'   => null,
                'code'        => null,
                'description' => $warning,
            );
        }

        $warnings = $this->getWarnings();
        $warnings[] = $warning;

        $this->setWarnings($warnings);
        return $this;
    }

    /**
     * Get shipment Ids from the request.
     *
     * @return array
     *
     * @throws DMP_PostNL2014_Exception
     */
    protected function _getShipmentIds()
    {
        $shipmentIds = $this->getRequest()->getParam('shipment_ids', array());

        /**
         * Check if a shipment was selected.
         */
        if (!is_array($shipmentIds) || empty($shipmentIds)) {
            throw new DMP_PostNL2014_Exception(
                $this->__('Please select one or more shipments.'),
                'MYPA-0001'
            );
        }

        return $shipmentIds;
    }

    /**
     * Get order Ids from the request.
     *
     * @return array
     *
     * @throws DMP_PostNL2014_Exception
     */
    protected function _getOrderIds()
    {
        $orderIds = $this->getRequest()->getParam('order_ids', array());
        $orderId = $this->getRequest()->getParam('order_id', array());

        /**
         * Check if the request came from the order detail page.
         */
        if(!empty($orderId)) {
            $orderIds[] = $orderId;
        } else {
            /**
             * Request came from the order overview
             * Check if an order was selected.
             */
            if (!is_array($orderIds) || empty($orderIds)) {
                throw new DMP_PostNL2014_Exception(
                    $this->__('Please select one or more orders.'),
                    'MYPA-0002'
                );
            }
        }

        return $orderIds;
    }

    /**
     * Creates shipments for a supplied array of orders. This action is triggered by a massaction in the sales > order
     * grid.
     *
     * @return $this
     */
    public function massCreateShipmentsAction()
    {
        $helper = Mage::helper('dmp_postnl');

        try {
            $orderIds = $this->_getOrderIds();

            /**
             * Create the shipments.
             */
            $errors = 0;
            foreach ($orderIds as $orderId) {
                try {
                    $this->_createShipment($orderId);
                } catch (DMP_PostNL2014_Exception $e) {
                    $helper->logException($e);
                    $this->addWarning(
                        array(
                            'entity_id'   => Mage::getResourceModel('sales/order')->getIncrementId($orderId),
                            'code'        => $e->getCode(),
                            'description' => $e->getMessage(),
                        )
                    );
                    $errors++;
                } catch (Exception $e) {
                    $helper->logException($e);
                    $this->addWarning(
                        array(
                            'entity_id'   => Mage::getResourceModel('sales/order')->getIncrementId($orderId),
                            'code'        => null,
                            'description' => $e->getMessage(),
                        )
                    );
                    $errors++;
                }
            }
        } catch (DMP_PostNL2014_Exception $e) {
            $helper->logException($e);
            $helper->addExceptionSessionMessage('adminhtml/session', $e);

            $this->_redirect('adminhtml/sales_order/index');
            return $this;
        } catch (Exception $e) {
            $helper->logException($e);
            $helper->addSessionMessage(
                'adminhtml/session',
                null,
                'error',
                $this->__('An error occurred while processing this action.')
            );

            $this->_redirect('adminhtml/sales_order/index');
            return $this;
        }

        /**
         * Check for warnings.
         */
        $this->_checkForWarnings();

        /**
         * Add either a success or failure message and redirect the user accordingly.
         */
        if ($errors < count($orderIds)) {
            $helper->addSessionMessage(
                'adminhtml/session', null, 'success',
                $this->__('The shipments were successfully created.')
            );

            $this->_redirect('adminhtml/sales_order/index');
        } else {
            $helper->addSessionMessage(
                'adminhtml/session', null, 'error',
                $this->__('None of the shipments could be created. Please check the error messages for more details.')
            );

            $this->_redirect('adminhtml/sales_order/index');
        }

        return $this;
    }

    /**
     * Creates a single consignment, for an already existing Magento Shipping.
     * This action is triggered in shipment-view page
     *
     * @return $this
     */
    public function createConsignmentAction()
    {
        $helper = Mage::helper('dmp_postnl');

        //get post variables
        $selectedConsignmentOptions = $this->getRequest()->getPost('dmp_postnl');
        $shipmentId                 = $this->getRequest()->getPost('shipment_id');

        $error            = false;
        $postNLShipment = false;

        //check if shipment id is present and set the shipmentId to the PostNL Shipment model
        try{

            if(!empty($shipmentId)){
                /** @var DMP_PostNL2014_Model_Shipment $postNLShipment */
                $postNLShipment = Mage::getModel('dmp_postnl/shipment')->setShipmentId($shipmentId);

            }else{
                throw new DMP_PostNL2014_Exception(
                    $helper->__('Please select one or more shipments.'),
                    'MYPA-0001'
                );
            }
        }catch (DMP_PostNL2014_Exception $e) {
            $error = true;
            $helper->logException($e);
            $helper->addExceptionSessionMessage('adminhtml/session', $e);

            $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipmentId));
            return $this;

        }

        //check if consigment options are selected and matches with the type of consignment and Magento shipment
        if (!empty($selectedConsignmentOptions['shipment_type'])) {
            $shipmentType = $selectedConsignmentOptions['shipment_type'];

            /**
             * check if it is an pakjegemak-shipment && the shipment type is not equal to normal
             * pakjegemak shipments can only be created with the normal shipment type
             */
            try {
                /** @var Mage_Sales_Model_Order_Shipment $shipment */
                $shipment = $postNLShipment->getShipment();
                if($helper->getPgAddress($shipment->getOrder()) && ($shipmentType != DMP_PostNL2014_Model_Shipment::TYPE_NORMAL && $shipmentType != 'default')){
                    $shipment_url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/view',array('shipment_id' => $shipment->getShipment()->getId()));
                    throw new DMP_PostNL2014_Exception(
                        $helper->__('The selected shipment type cannot be used. Pakjegemak shipments can only be created with the normal shipment type.<br/> The Magento shipment has been created without a PostNL shipment, select a different shipment type or go to the shipment page to create a single PostNL shipment. <a target="_blank" href="%s">View shipment</a>',$shipment_url),
                        'MYPA-0023'
                    );
                }
            } catch(DMP_PostNL2014_Exception $e) {
                $error = true;
                $helper->logException($e);
                $helper->addExceptionSessionMessage('adminhtml/session', $e);

                $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipmentId));
                return $this;
            }

            //if not the normal shipment-type, no extra options are needed, so reset the consignment options
            if ($shipmentType != DMP_PostNL2014_Model_Shipment::TYPE_NORMAL) {
                $selectedConsignmentOptions = array(
                    'shipment_type' => $shipmentType
                );
            }

            //register the consignment options
            Mage::register('dmp_postnl_consignment_options', $selectedConsignmentOptions);

            /**
             * consignment options are set, try if we can create a postnl consignment
             */
            try{
                $postNLShipment->setConsignmentOptions()->createConsignment()->save();
                $barcode = $postNLShipment->getBarcode();
                if ($barcode) {
                    $postNLShipment->addTrackingCodeToShipment($barcode);
                }
            }catch (DMP_PostNL2014_Exception $e) {
                $error = true;
                $helper->logException($e);
                $helper->addExceptionSessionMessage('adminhtml/session', $e);
            } catch (Exception $e) {
                $error = true;
                $helper->logException($e);
                $helper->addSessionMessage(
                    'adminhtml/session',
                    null,
                    'error',
                    $this->__('An error occurred while processing this action.')
                );
            }
        }

        if(true !== $error){
            $helper->addSessionMessage(
                'adminhtml/session', null, 'success',
                $this->__('The PostNL consignment is successfully created.')
            );
        }

        $this->_redirect('adminhtml/sales_shipment/view', array('shipment_id' => $shipmentId));
        return $this;
    }

    /**
     * Print shipping labels for all selected orders.
     *
     * @return $this
     *
     * @throws DMP_PostNL2014_Exception
     */
    public function massPrintLabelsAction()
    {
        $helper = Mage::helper('dmp_postnl');
        $orderIds = $this->_getOrderIds();

        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
                                  ->addFieldToSelect(array('entity_id','order_id'))
                                  ->addFieldToFilter('order_id', array('in', $orderIds));

        $shipmentIds      = $shipmentCollection->getColumnValues('entity_id');
        $shipmentOrderIds = $shipmentCollection->getColumnValues('order_id');

        Mage::register('dmp_postnl_consignment_options', array(
            'create_consignment' => '1',
            'type_consignment' => $this->getRequest()->getParam('type_consignment'),
        ));

        /**
         * create new shipments if not yet created
         */
        $hasNoShipments = array_diff($orderIds, $shipmentOrderIds);
        $newShipments   = array();

        $errors = 0;
        if(!empty($hasNoShipments)){
            foreach($hasNoShipments as $orderId)
            {
                /**
                 * returns a shipment object
                 */
                try {
                    $newShipments[] = $this->_createShipment($orderId, true);
                } catch (DMP_PostNL2014_Exception $e) {
                    $helper->logException($e);

                    $helper->addSessionMessage(
                        'adminhtml/session',
                        null,
                        'error',
                        'Order: '.Mage::getResourceModel('sales/order')->getIncrementId($orderId). ' - ' .$e->getMessage()
                    );

                    $errors++;
                } catch (Exception $e) {
                    $helper->logException($e);

                    $helper->addSessionMessage(
                        'adminhtml/session',
                        null,
                        'error',
                        'Order: '.Mage::getResourceModel('sales/order')->getIncrementId($orderId). ' - ' .$e->getMessage()
                    );

                    $errors++;
                }
            }
        }

        // if new shipments are created, refresh the collection of shipments for the orders
        if(!empty($newShipments))
        {
            $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
                                      ->addFieldToSelect(array('entity_id','order_id'))
                                      ->addFieldToFilter('order_id', array('in', $orderIds));

            $shipmentIds = $shipmentCollection->getColumnValues('entity_id');
        }

        /**
         * Load the shipments and check if they are valid.
         * returns an array with shipment objects
         */
        $shipments = $this->_loadAndCheckShipments($shipmentIds, true, true, false);

        /**
         * Get the labels from CIF.
         *
         * @var DMP_PostNL2014_Model_Shipment $shipment
         */
        $consignmentIds = array();

        $type = $this->getRequest()->getParam('type_consignment');
        $type = $type ? $type : 'default';

        foreach ($shipments as $shipment) {
            try {
                if (!$shipment->hasConsignmentId()) {

                    if($helper->getPgAddress($shipment->getOrder()) && $type != DMP_PostNL2014_Model_Shipment::TYPE_NORMAL && $type != 'default'){
                        $shipment_url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/view',array('shipment_id' => $shipment->getShipment()->getId()));
                        throw new DMP_PostNL2014_Exception(
                            $helper->__('The selected shipment type cannot be used. Pakjegemak shipments can only be created with the normal shipment type.<br/> The Magento shipment has been created without a PostNL shipment, select a different shipment type or go to the shipment page to create a single PostNL shipment. <a target="_blank" href="%s">View shipment</a>',$shipment_url),
                            'MYPA-0023'
                        );
                    }

                    $consignmentOptions = array('shipment_type' => $type);
                    if (Mage::registry('dmp_postnl_consignment_options')) {
                        $consignmentOptions = array_merge(
                            $consignmentOptions,
                            Mage::registry('dmp_postnl_consignment_options')
                        );
                        Mage::unregister('dmp_postnl_consignment_options');
                    }
                    Mage::register('dmp_postnl_consignment_options', $consignmentOptions);
                    $shipment->setShipmentId($shipment->getShipment()->getId())
                        ->setConsignmentOptions($consignmentOptions)
                        ->createConsignment()
                        ->save();
                }

                $consignmentIds[] = $shipment->getConsignmentId();
            } catch (Exception $e) {
                $helper->logException($e);

                $helper->addSessionMessage(
                    'adminhtml/session',
                    null,
                    'error',
                    'Order: '.$shipment->getOrder()->getIncrementId(). ' - ' .$e->getMessage()
                );
            }
        }

        if (!$consignmentIds) {
            $this->_redirect('adminhtml/sales_order/index');
            return $this;
        }

        $storeId = $shipment->getOrder()->getStoreId();
        /**
         * @var $api DMP_PostNL2014_Model_Api_PostNL
         */
        $start   = $this->getRequest()->getParam('postnl_print_labels_start', 1);
        $perpage = $helper->getConfig('print_orientation');
        $api     = Mage::getModel('dmp_postnl/api_postNL');
        $api->setStoreId($storeId)
            ->createSetupPdfsRequest($consignmentIds, $start, $perpage)
            ->sendRequest('GET');

        if ($api->getLabelDownloadUrl() == null) {
            $fileName = 'PostNL Shipping Labels '
                . date('Ymd-His', Mage::getSingleton('core/date')->timestamp())
                . '.pdf';

            $this->_preparePdfResponse($fileName, $api->getRequestResponse());

            /**
             * We need to check for warnings before the label download response.
             */
            $this->_checkForWarnings();
        }

        /**
         * Load the shipments and check if they are valid.
         * returns an array with shipment objects
         *
         * @var DMP_PostNL2014_Model_Shipment $shipment
         */
        $shipments = $this->_loadAndCheckShipments($shipmentIds, true, false);

        $apiInfo    = Mage::getModel('dmp_postnl/api_postNL');
        $apiInfo    ->setStoreId($storeId);
        $responseShipments = $apiInfo->getConsignmentsInfoData($consignmentIds);

        foreach($responseShipments as $responseShipment){
            $shipment = $shipments[$responseShipment->id];
            $shipment->updateStatus($responseShipment);
        }

        if ($api->getLabelDownloadUrl() != null) {
            echo $api->getLabelDownloadUrl();
            exit;
        }

        return $this;
    }

    /**
     * Print shipping labels for all selected shipments.
     *
     * @return $this
     *
     * @throws DMP_PostNL2014_Exception
     */
    public function massPrintShipmentLabelsAction()
    {
        $helper = Mage::helper('dmp_postnl');
        $shipmentIds = $this->_getShipmentIds();

        $shipmentCollection = Mage::getResourceModel('sales/order_shipment_collection')
            ->addFieldToSelect(array('entity_id','order_id'))
            ->addFieldToFilter('entity_id', array('in', $shipmentIds));

        $shipmentIds      = $shipmentCollection->getColumnValues('entity_id');

        Mage::register('dmp_postnl_consignment_options', array(
            'create_consignment' => '1',
            'type_consignment' => $this->getRequest()->getParam('type_consignment'),
        ));

        /**
         * Load the shipments and check if they are valid.
         * returns an array with shipment objects
         */
        $shipments = $this->_loadAndCheckShipments($shipmentIds, true, false, false);

        /**
         * Get the labels from CIF.
         *
         * @var DMP_PostNL2014_Model_Shipment $shipment
         */
        $consignmentIds = array();
        foreach ($shipments as $shipment) {
            try {
                if (!$shipment->hasConsignmentId()) {
                    $type = $this->getRequest()->getParam('type_consignment');

                    if($helper->getPgAddress($shipment->getOrder()) && $type != DMP_PostNL2014_Model_Shipment::TYPE_NORMAL && $type != 'default'){
                        $shipment_url = Mage::helper('adminhtml')->getUrl('adminhtml/sales_order_shipment/view',array('shipment_id' => $shipment->getShipment()->getId()));
                        throw new DMP_PostNL2014_Exception(
                            $helper->__('The selected shipment type cannot be used. Pakjegemak shipments can only be created with the normal shipment type.<br/> The Magento shipment has been created without a PostNL shipment, select a different shipment type or go to the shipment page to create a single PostNL shipment. <a target="_blank" href="%s">View shipment</a>',$shipment_url),
                            'MYPA-0023'
                        );
                    }

                    $consignmentOptions = array('shipment_type' => $type);
                    if (Mage::registry('dmp_postnl_consignment_options')) {
                        $consignmentOptions = array_merge(
                            $consignmentOptions,
                            Mage::registry('dmp_postnl_consignment_options')
                        );
                        Mage::unregister('dmp_postnl_consignment_options');
                    }
                    Mage::register('dmp_postnl_consignment_options', $consignmentOptions);
                    $shipment->setShipmentId($shipment->getShipment()->getId())
                        ->setConsignmentOptions($consignmentOptions)
                        ->createConsignment()
                        ->save();
                }

                $consignmentIds[] = $shipment->getConsignmentId();
            } catch (Exception $e) {
                $helper->logException($e);

                $helper->addSessionMessage(
                    'adminhtml/session',
                    null,
                    'error',
                    'Order: '.$shipment->getOrder()->getIncrementId(). ' - ' .$e->getMessage()
                );
            }
        }

        if (!$consignmentIds) {
            $this->_redirect('adminhtml/sales_shipment/index');
            return $this;
        }

        $storeId = $shipment->getOrder()->getStoreId();

        /** @var $api DMP_PostNL2014_Model_Api_PostNL */
        $api     = Mage::getModel('dmp_postnl/api_postNL');
        $api->setStoreId($storeId);
        $start   = $this->getRequest()->getParam('postnl_print_labels_start', 1);
        $perpage = $helper->getConfig('print_orientation');
        $pdfData = $api->createSetupPdfsRequest($consignmentIds, $start, $perpage)
            ->sendRequest('GET')
            ->getRequestResponse();

        $fileName = 'PostNL Shipping Labels '
            . date('Ymd-His', Mage::getSingleton('core/date')->timestamp())
            . '.pdf';

        $this->_preparePdfResponse($fileName, $pdfData);

        /**
         * We need to check for warnings before the label download response.
         */
        $this->_checkForWarnings();

        return $this;
    }

    /**
     * Print one shipping label.
     *
     * @return boolean
     */
    public function printShipmentLabelAction(){
        return $this->massPrintLabelsAction();
    }

    public function sendReturnMailAction()
    {
        /**
         * @var DMP_PostNL2014_Model_Api_PostNL $api
         */
        $helper = Mage::helper('dmp_postnl');
        $error = null;
        $message = &$error;
        $request = $this->getRequest();
        $name = $request->getParam('postnl_name');
        $email = $request->getParam('postnl_email');
        $labelDescription = $request->getParam('postnl_label_description');

        if (!$email)
            $error = $helper->__('You did not specify a email');

        if (!$name)
            $error = $helper->__('You did not specify a name');

        if ($error == null) {

            $data = array(
                'cc' => 'NL',
                'carrier' => 1,
                'email' => $email,
                'name' => $name,
                'options' => array(
                    'package_type' => 1,
                    'label_description' => $labelDescription
                )
            );

            $api = Mage::getModel('dmp_postnl/api_postNL');
            $response = $api->sendUnrelatedRetourmailRequest($data)
                ->sendRequest()
                ->getRequestResponse();
            $aResponse = json_decode($response, true);
            if ($aResponse) {
                $message = $helper->__('Mail send to') . ' ' . $email;
            } else {
                $error = 'Something goes wrong with your request. Please feel free to contact PostNL.';
            }
        }

        header('Content-Type: application/json');
        echo json_encode(array(
            'message' => $message
        ));
        exit;
    }

    /**
     * @return Mage_Core_Controller_Varien_Action
     * @throws DMP_PostNL2014_Exception
     */
    public function printPackingSlipAction(){

        $orderIds = $this->_getOrderIds();
        $flag = false;
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $shipments = Mage::getResourceModel('sales/order_shipment_collection')
                    ->setOrderFilter($orderId)
                    ->load();
                if ($shipments->getSize()) {
                    $flag = true;
                    if (!isset($pdf)){
                        $pdf = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                    } else {
                        $pages = Mage::getModel('sales/order_pdf_shipment')->getPdf($shipments);
                        $pdf->pages = array_merge ($pdf->pages, $pages->pages);
                    }
                }
            }
            if ($flag) {
                return $this->_prepareDownloadResponse(
                    'packingslip'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(),
                    'application/pdf'
                );
            } else {
                $this->_getSession()->addError($this->__('There are no printable documents related to selected orders.'));
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');

    }

    /**
     * Check if label pdf exists
     *
     * @throws DMP_PostNL2014_Exception
     */
    public function fileExistsAction()
    {
        $request = $this->getRequest();
        $url = $request->getParam('url');

        /**
         * @var DMP_PostNL2014_Model_Api_PostNL $api
         */
        $api = Mage::getModel('dmp_postnl/api_postNL');
        $response = $api->createFileExistsRequest($url)
            ->sendRequest('GET', false)
            ->getRequestResponse();

        header('Content-Type: application/json');
        echo (json_encode(preg_match("/^%PDF-1./", $response)));
        exit;
    }

    /**
     * Load an array of shipments based on an array of shipmentIds and check if they're shipped using PostNL
     *
     * @param array|int $shipmentIds
     * @param boolean   $loadPostNLShipments Flag that determines whether the shipments will be loaded as
     *                                         Mage_Sales_Model_Shipment or DMP_PostNL2014_Model_Shipment objects.
     * @param boolean   $throwException        Flag whether an exception should be thrown when loading the shipment fails.
     * @param bool $keyIsConsignmentId         When creating a new shipment there is no consignment_id. Other times it
     *                                         is necessary to use consignment_id as the key.
     *
     * @return array
     * @throws DMP_PostNL2014_Exception
     */
    protected function _loadAndCheckShipments($shipmentIds, $loadPostNLShipments = false, $throwException = true, $keyIsConsignmentId = true)
    {
        if (!is_array($shipmentIds)) {
            $shipmentIds = array($shipmentIds);
        }

        $shipments = array();
        foreach ($shipmentIds as $shipmentId) {
            /**
             * Load the shipment.
             *
             * @var Mage_Sales_Model_Order_Shipment|DMP_PostNL2014_Model_Shipment|boolean $shipment
             */
            $shipment = $this->_loadShipment($shipmentId, $loadPostNLShipments);

            if (!$shipment && $throwException) {
                throw new DMP_PostNL2014_Exception(
                    $this->__(
                        'This action is not available for shipment #%s, because it was not shipped using PostNL.',
                        $shipmentId
                    ),
                    'MYPA-0003'
                );
            } elseif (!$shipment) {
                $this->addWarning(
                    array(
                        'entity_id'   => $shipmentId,
                        'code'        => 'MYPA-0003',
                        'description' => $this->__(
                            'This action is not available for shipment #%s, because it was not shipped using PostNL.',
                            $shipmentId
                        ),
                    )
                );

                continue;
            }

            if ($keyIsConsignmentId) {
                $shipments[$shipment->getData('consignment_id')] = $shipment;
            } else {
                $shipments[] = $shipment;
            }
        }

        return $shipments;
    }

    /**
     * Load a shipment based on a shipment ID.
     *
     * @param int     $shipmentId
     * @param boolean $loadPostNLShipment
     *
     * @return boolean|Mage_Sales_Model_Order_Shipment|DMP_PostNL2014_Model_Shipment
     */
    protected function _loadShipment($shipmentId, $loadPostNLShipment)
    {
        if ($loadPostNLShipment === false) {
            /**
             * @var Mage_Sales_Model_Order_Shipment $shipment
             */
            $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
            if (!$shipment || !$shipment->getId()) {
                return false;
            }

            $shippingMethod = $shipment->getOrder()->getShippingMethod();
        } else {
            /**
             * @var DMP_PostNL2014_Model_Shipment $shipment
             */
            $shipment = $this->_getPostNLShipment($shipmentId);
            if (!$shipment || !$shipment->getId()) {
                $shipment->setShipmentId($shipmentId);
            }

            $shippingMethod = $shipment->getShipment()->getOrder()->getShippingMethod();
        }

        /**
         * Check if the shipping method used is allowed
         */
        if (!Mage::helper('dmp_postnl')->shippingMethodIsPostNL($shippingMethod) || $shipment->getShipment()->getOrder()->getIsVirtual()) {
            return false;
        }

        return $shipment;
    }

    /**
     * Gets the PostNL shipment associated with a Magento shipment.
     *
     * @param int $shipmentId
     *
     * @return DMP_PostNL2014_Model_Shipment
     */
    protected function _getPostNLShipment($shipmentId)
    {
        $postNLShipment = Mage::getModel('dmp_postnl/shipment')->load($shipmentId, 'shipment_id');

        return $postNLShipment;
    }

    /**
     * Creates a shipment of an order containing all available items
     *
     * @param int $orderId
     * @param boolean $returnShipment
     *
     * @return int|DMP_PostNL2014_Exception
     *
     *
     * @throws DMP_PostNL2014_Exception
     */
    protected function _createShipment($orderId, $returnShipment = false)
    {
        /**
         * @var Mage_Sales_Model_Order $order
         */
        $order = Mage::getModel('sales/order')->load($orderId);

        if($order->isCanceled()){
            throw new DMP_PostNL2014_Exception(
                $this->__('Order %s cannot be shipped, because it is cancelled.', $order->getIncrementId()),
                'MYPA-0004'
            );
        }

        if (!$order->canShip()) {
            throw new DMP_PostNL2014_Exception(
                $this->__('Order #%s cannot be shipped at this time.', $order->getIncrementId()),
                'MYPA-0004'
            );
        }

        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = Mage::getModel('sales/service_order', $order)
            ->prepareShipment($this->_getItemQtys($order));

        $shipment->register();
        $this->_saveShipment($shipment);

        if($returnShipment){
            return $shipment;
        }

        return $shipment->getId();
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     *
     * @return $this
     */
    protected function _saveShipment($shipment)
    {

        /**
         * @var DMP_PostNL2014_Helper_Data $helper
         */
        $helper = Mage::helper('dmp_postnl');
        if ($helper->getConfig('automatically_next_status', 'shipment') === '1') {
            $shipment->getOrder()->setIsInProcess(true);
        }

        Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        return $this;
    }

    /**
     * Checks if any warnings were received while processing the shipments and/or orders. If any warnings are found they
     * are added to the adminhtml session as a notice.
     *
     * @return $this
     */
    protected function _checkForWarnings()
    {
        /**
         * Check if any warnings were registered
         */
        $cifWarnings = Mage::registry('postnl_api_warnings');

        if (is_array($cifWarnings) && !empty($cifWarnings)) {
            $this->_addWarningMessages($cifWarnings, $this->__('PostNL replied with the following warnings:'));
        }

        $warnings = $this->getWarnings();

        if (!empty($warnings)) {
            $this->_addWarningMessages(
                $warnings,
                $this->__('The following shipments or orders could not be processed:')
            );
        }

        return $this;
    }

    /**
     * Add an array of warning messages to the adminhtml session.
     *
     * @param        $warnings
     * @param string $headerText
     *
     * @return $this
     * @throws DMP_PostNL2014_Exception
     */
    protected function _addWarningMessages($warnings, $headerText = '')
    {
        $helper = Mage::helper('dmp_postnl');

        /**
         * Create a warning message to display to the merchant.
         */
        $warningMessage = $headerText;
        $warningMessage .= '<ul class="postnl-warning">';

        /**
         * Add each warning to the message.
         */
        foreach ($warnings as $warning) {
            /**
             * Warnings must have a description.
             */
            if (!array_key_exists('description', $warning)) {
                continue;
            }

            /**
             * Codes are optional for warnings, but must be present in the array. If no code is found in the warning we
             * add an empty one.
             */
            if (!array_key_exists('code', $warning)) {
                $warning['code'] = null;
            }

            /**
             * Get the formatted warning message.
             */
            $warningText = $helper->getSessionMessage(
                $warning['code'],
                'warning',
                $this->__($warning['description'])
            );

            /**
             * Prepend the warning's entity ID if present.
             */
            if (!empty($warning['entity_id'])) {
                $warningText = $warning['entity_id'] . ': ' . $warningText;
            }

            /**
             * Build the message proper.
             */
            $warningMessage .= '<li>' . $warningText . '</li>';
        }

        $warningMessage .= '</ul>';

        /**
         * Add the warnings to the session.
         */
        $helper->addSessionMessage('adminhtml/session', null, 'notice',
            $warningMessage
        );

        return $this;
    }

    /**
     * Output the specified string as a pdf.
     *
     * @param string $filename
     * @param string $output
     *
     * @return $this
     * @throws Zend_Controller_Response_Exception
     */
    protected function _preparePdfResponse($filename, $output)
    {
        $this->getResponse()
             ->setHttpResponseCode(200)
             ->setHeader('Pragma', 'public', true)
             ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate', true)
             ->setHeader('Content-type', 'application/pdf', true)
             ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
             ->setHeader('Last-Modified', date('r'))
             ->setBody($output);

        return $this;
    }

    /**
     * Initialize shipment items QTY
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function _getItemQtys($order)
    {
        $itemQtys = array();

        /**
         * @var Mage_Sales_Model_Order_Item $item
         */
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            /**
             * the qty to ship is the total remaining (not yet shipped) qty of every item
             */
            $itemQty = $item->getQtyOrdered() - $item->getQtyShipped();

            $itemQtys[$item->getId()] = $itemQty;
        }

        return $itemQtys;
    }

}
