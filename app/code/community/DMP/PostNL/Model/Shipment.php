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
 * @copyright   Copyright (c) 2019 DM Productions B.V. (https://www.dmp.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 * @method boolean hasShipmentId()
 * @method boolean hasOrderId()
 * @method boolean hasConsignmentId()
 * @method boolean hasCreatedAt()
 * @method boolean hasUpdatedAt()
 * @method boolean hasStatus()
 * @method boolean hasBarcode()
 * @method boolean hasIsFinal()
 * @method boolean hasShipment()
 * @method boolean hasOrder()
 * @method boolean hasShippingAddress()
 * @method boolean hasApi()
 * @method boolean hasShipmentIncrementId()
 * @method boolean hasBarcodeSend()
 * @method boolean hasShipmentType()
 * @method boolean hasIsXl()
 *
 * @method string getShipmentId()
 * @method string getTrackId()
 * @method string getConsignmentId()
 * @method string getCreatedAt()
 * @method string getUpdatedAt()
 * @method string getStatus()
 * @method string getBarcode()
 * @method string getIsFinal()
 * @method int    getHomeAddressOnly()
 * @method int    getSignatureOnReceipt()
 * @method int    getReturnIfNoAnswer()
 * @method int    getInsured()
 * @method int    getInsuredAmount()
 * @method int    getBarcodeSend()
 * @method int    getCustomsContentType()
 * @method string getShipmentType()
 * @method int    getIsXL()
 *
 * @method DMP_PostNL_Model_Shipment setShipmentId(int $value)
 * @method DMP_PostNL_Model_Shipment setOrderId(int $value)
 * @method DMP_PostNL_Model_Shipment setTrackId(int $value)
 * @method DMP_PostNL_Model_Shipment setConsignmentId(int $value)
 * @method DMP_PostNL_Model_Shipment setCreatedAt(string $value)
 * @method DMP_PostNL_Model_Shipment setUpdatedAt(string $value)
 * @method DMP_PostNL_Model_Shipment setStatus(string $value)
 * @method DMP_PostNL_Model_Shipment setBarcode(string $value)
 * @method DMP_PostNL_Model_Shipment setIsFinal(int $value)
 * @method DMP_PostNL_Model_Shipment setShipment(Mage_Sales_Model_Order_Shipment $value)
 * @method DMP_PostNL_Model_Shipment setOrder(Mage_Sales_Model_Order $value)
 * @method DMP_PostNL_Model_Shipment setShippingAddress(Mage_Sales_Model_Order_Address $value)
 * @method DMP_PostNL_Model_Shipment setApi(DMP_PostNL_Model_Api_PostNL $value)
 * @method DMP_PostNL_Model_Shipment setShipmentIncrementId(string $value)
 * @method DMP_PostNL_Model_Shipment setBarcodeSend(int $value)
 * @method DMP_PostNL_Model_Shipment setRetourlink(string $value)
 * @method DMP_PostNL_Model_Shipment setIsCredit(int $value)
 * @method DMP_PostNL_Model_Shipment setCustomsContentType(int $value)
 * @method DMP_PostNL_Model_Shipment setShipmentType(string $value)
 * @method DMP_PostNL_Model_Shipment setIsXl(int $value)
 *
 */
class DMP_PostNL_Model_Shipment extends Mage_Core_Model_Abstract
{
    /**
     * Carrier code used by PostNL.
     */
    const POSTNL_CARRIER_CODE = 'postnl';

    /**
     * Statusses used by PostNL shipments.
     */
    const STATUS_NEW        = 1;

    /**
     * Supported delivery types.
     */
    const ALIAS_PACKAGE_TYPE_MAILBOX    = 'letter_box';
    const ALIAS_PACKAGE_TYPE_NORMAL     = 'normal';
    const ALIAS_PACKAGE_TYPE_UNPAID     = 'unstamped';

    /**
     * Delivery types
     */
    const PACKAGE_TYPE  = 1;
    const MAILBOX_TYPE  = 2;
    const LETTER_TYPE   = 3;

    /**
     * @var DMP_PostNL_Helper_Data $helper
     */
    public $helper;

    /**
     * Initialize the shipment
     */
    public function _construct()
    {
        $this->_init('dmp_postnl/shipment');
        $this->helper = Mage::helper('dmp_postnl');
    }

    /**
     * Gets the Magento shipment associated with this PostNL shipment.
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        if ($this->hasShipment()) {
            return $this->_getData('shipment');
        }

        /**
         * @var Mage_Sales_Model_Order_Shipment $shipment
         */
        $shipmentId = $this->getShipmentId();
        $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);

        $this->setShipment($shipment);
        return $shipment;
    }

    /**
     * Gets this shipment's order ID.
     *
     * @return mixed
     */
    public function getOrderId()
    {
        if ($this->hasOrderId()) {
            return $this->_getData('order_id');
        }

        $orderId = $this->getShipment()->getOrderId();

        $this->setOrderId($orderId);
        return $orderId;
    }

    /**
     * Gets the Magento order associated with this PostNL shipment.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->hasOrder()) {
            return $this->_getData('order');
        }

        /**
         * @var Mage_Sales_Model_Order $order
         */
        $orderId = $this->getOrderId();
        $order = Mage::getModel('sales/order')->load($orderId);

        $this->setOrder($order);
        return $order;
    }

    /**
     * Gets the shipping address of this shipment.
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getShippingAddress()
    {
        if ($this->hasShippingAddress()) {
            return $this->_getData('shipping_address');
        }

        $shipment        = $this->getShipment();
        $shippingAddress = $shipment->getShippingAddress();

        $this->setShippingAddress($shippingAddress);
        return $shippingAddress;
    }

    /**
     * Gets the increment ID of this shipment's Magento shipment if available.
     *
     * @return null|string
     */
    public function getShipmentIncrementId()
    {
        if ($this->hasShipmentIncrementId()) {
            return $this->_getData('shipment_increment_id');
        }

        $shipment = $this->getShipment(false);
        if (!$shipment || !$shipment->getIncrementId()) {
            return null;
        }

        $incrementId = $shipment->getIncrementId();

        $this->setShipmentIncrementId($incrementId);
        return $incrementId;
    }

    /**
     * Calculates a shipment's base grand total based on it's shipment items
     *
     * @return float|null
     */
    public function getOrderTotal()
    {
        /**
         * Check if this PostNL shipment has a linked Mage_Sales_Model_Order_Shipment object
         */
        $shipment = $this->getShipment();
        if (!$shipment) {
            return null;
        }

        /**
         * Loop through all associated shipment items and add each item's row total to the shipment's total
         */
        $baseGrandTotal = 0;
        $shipmentItems = $shipment->getAllItems();

        /**
         * @var Mage_Sales_Model_Order_Shipment_Item $shipmentItem
         */
        foreach ($shipmentItems as $shipmentItem) {
            $qty = $shipmentItem->getQty();

            /**
             * The base price of a shipment item is only available through it's associated order item
             */
            $basePrice = $shipmentItem->getOrderItem()->getBasePrice();

            /**
             * Calculate and add the shipment item's row total
             */
            $totalBasePrice = $basePrice * $qty;
            $baseGrandTotal += $totalBasePrice;
        }
        return $baseGrandTotal;
    }

    /**
     * Gets the PostNL API object.
     *
     * @return DMP_PostNL_Model_Api_PostNL
     */
    public function getApi()
    {
        if ($this->hasApi()) {
            return $this->_getData('api');
        }

        $storeId = $this->getShipment()->getStoreId();
        $api     = Mage::getModel('dmp_postnl/api_postNL',array('store_id' => $storeId));

        $this->setApi($api);
        return $api;
    }

    public function isHomeAddressOnly()
    {

        $checkoutData = $this->getShipment()->getOrder()->getPostnlData();
        if($checkoutData !== null) {
            $aData = json_decode($checkoutData, true);
            if(key_exists('home_address_only', $aData) && $aData['home_address_only']){
                return 1;
            }
        }

        return $this->getHomeAddressOnly();
    }

    public function isSignatureOnReceipt()
    {

        $checkoutData = $this->getShipment()->getOrder()->getPostnlData();
        if($checkoutData !== null) {
            $aData = json_decode($checkoutData, true);
            if(key_exists('signed', $aData) && $aData['signed']){
                return 1;
            }
        }

        return $this->getSignatureOnReceipt();
    }

    public function isXL()
    {
        $consignmentOption = 'is_xl';
        $orderIsXl = $this->getIsXL();

        if($orderIsXl === null) {
            $storeId = $this->getOrder()->getStoreId();
            $orderTotalShipped = $this->getOrderTotal();

            $configValue = $this->helper->getConfig($consignmentOption, 'shipment', $storeId);
            if (!empty($configValue) && $configValue > 0) {
                if ($orderTotalShipped >= $configValue) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        } else {
            if($orderIsXl == '1') {
                return 1;
            } else {
                return 0;
            }
        }
    }


    /**
     * @return array
     */
    public function getHomeAddressOnlyOption()
    {
        $consignmentOption = 'home_address_only';

        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $this->helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => 0,
        );

    }

    /**
     * @return array
     */
    public function getSignatureOnReceiptOption()
    {
        $consignmentOption = 'signature_on_receipt';

        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $this->helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => 0,
        );
    }

    /**
     * @return array
     */
    public function getXlOption()
    {
        $consignmentOption = 'is_xl';

        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $this->helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => null,
        );
    }

    /**
     * @return array
     */
    public function getReturnIfNoAnswerOption()
    {
        $consignmentOption = 'return_if_no_answer';

        $storeId = $this->getOrder()->getStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $this->helper->getConfig($consignmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return array(
                    'option' => $consignmentOption,
                    'selected' => 1,
                );
            }
        }
        return array(
            'option' => $consignmentOption,
            'selected' => 0,
        );
    }

    /**
     * get the insured amount
     *
     * @return array
     */
    public function getInsuredOption()
    {
        //load helper, store id and orderTotal
        $helper            = $this->helper;
        $storeId           = $this->getOrderStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        //get the insured values
        $insuredType100    = $helper->getConfig('insured_100','shipment',$storeId);
        $insuredType250    = $helper->getConfig('insured_250','shipment',$storeId);
        $insuredType500    = $helper->getConfig('insured_500','shipment',$storeId);

        //check if the values are not empty/zero.
        $insuredType100    = (!empty($insuredType100) && $insuredType100 > 0)? $insuredType100 : false;
        $insuredType250    = (!empty($insuredType250) && $insuredType250 > 0)? $insuredType250 : false;
        $insuredType500    = (!empty($insuredType500) && $insuredType500 > 0)? $insuredType500 : false;


        if(false !== $insuredType500 && $orderTotalShipped > $insuredType500){
            $insuredValue = 500;
        }elseif(false !== $insuredType250 && $orderTotalShipped > $insuredType250){
            $insuredValue = 250;
        }elseif(false !== $insuredType100 && $orderTotalShipped > $insuredType100){
            $insuredValue = 100;
        }else{
            $insuredValue = 0;
        }

        $returnArray = array(
            'option'         => 'insured',
            'selected'       => 0,
            'insured_amount' => 0,
        );

        if($insuredValue > 0){
            $returnArray = array(
                'option'         => 'insured',
                'selected'       => 1,
                'insured_amount' => $insuredValue,
            );
        }

        return $returnArray;
    }

    /**
     * @return $this
     */
    public function calculateConsignmentOptions()
    {
        $homeAddressOnly     = $this->getHomeAddressOnlyOption();
        $signatureOnReceipt = $this->getSignatureOnReceiptOption();
        $returnIfNoAnswer    = $this->getReturnIfNoAnswerOption();
        $xl                  = $this->getXlOption();
        $insured             = $this->getInsuredOption();

        $this->setDataUsingMethod($homeAddressOnly['option'], $homeAddressOnly['selected']);
        $this->setDataUsingMethod($signatureOnReceipt['option'], $signatureOnReceipt['selected']);
        $this->setDataUsingMethod($returnIfNoAnswer['option'], $returnIfNoAnswer['selected']);
        $this->setDataUsingMethod($xl['option'], $xl['selected']);
        $this->setDataUsingMethod($insured['option'], $insured['selected']);
        $this->setDataUsingMethod('insured_amount', $insured['insured_amount']);

        return $this;
    }

    /**
     * Sets an array of consignment options. If any options were set in the registry, those will be used as well.
     *
     * @param array $consignmentOptions
     *
     * @return $this
     */
    public function setConsignmentOptions($consignmentOptions = array())
    {
        /**
         * If any consignment options were set in the registry, those will be added as well.
         */
        $filteredOptions = $registryOptions = Mage::registry('dmp_postnl_consignment_options');
        $hasExtraOptions = $this->helper->shippingHasExtraOptions($this->getShipment()->getOrder()->getShippingMethod());

        unset($filteredOptions['create_consignment']);
        unset($filteredOptions['type_consignment']);

        if (!empty($filteredOptions) && is_array($filteredOptions)) {
            $consignmentOptions = array_merge($consignmentOptions, $registryOptions);
        }

        if (!key_exists('type_consignment', $registryOptions) || $registryOptions['type_consignment'] == null || $registryOptions['type_consignment'] == 'default') {
            if ($this->helper->getPackageType($this->getShipment()->getItemsCollection(), $this->getShippingAddress()->getCountryId(), false, $hasExtraOptions) == 1) {
            	$type = self::ALIAS_PACKAGE_TYPE_NORMAL;
            } else {
                $type = self::ALIAS_PACKAGE_TYPE_MAILBOX;
            }
        } else {
            $type = $registryOptions['type_consignment'];
			if ($type == self::ALIAS_PACKAGE_TYPE_MAILBOX && $this->helper->shippingMethodIsPakjegemak($this->getShipment()->getOrder()->getShippingMethod())) {
                $type = self::ALIAS_PACKAGE_TYPE_NORMAL;
            }
        }

        /**
         * Is only empty when the PostNL shipment is created in a mass-action
         */
        if(empty($consignmentOptions) && empty($filteredOptions)){
            $this->calculateConsignmentOptions();
            $this->setDataUsingMethod('shipment_type', $type);
            return $this;
        }

        /**
         * Add the options.
         */
        foreach ($consignmentOptions as $option => $value) {
            /**
             * The insured_amount option is dependant on the 'insured' option.
             */
            if ($option == 'insured_amount'
                && (!isset($registryOptions['insured'])
                    || $registryOptions['insured'] != '1'
                )
            ) {
                continue;
            }

            if ($option == 'shipment_type') {
                if (!$this->_isValidType($value)) {
                    $value = self::ALIAS_PACKAGE_TYPE_NORMAL;
                }
            }

            $this->setDataUsingMethod($option, $value);
        }

        return $this;
    }

    /**
     * Checks if a consignment can be created for this current shipment.
     *
     * @return bool
     */
    public function canCreateConsignment()
    {

        if ($this->hasConsignmentId()) {
            return false;
        }

        return true;
    }

    /**
     * Create a consignment using the PostNL API.
     *
     * @returns $this
     *
     * @throws DMP_PostNL_Exception
     */
    public function createConsignment()
    {
        $storeId = $this->getOrder()->getStoreId();
        if (!$this->canCreateConsignment()) {
            throw new DMP_PostNL_Exception(
                $this->helper->__('The createConsignment action is currently unavailable.'),
                'MYPA-0011'
            );
        }

        /**
         * Send the createConsignment request using the PostNL API.
         *
         * @var DMP_PostNL_Model_Api_PostNL $api
         */
        $api = $this->getApi();
        $response = $api->createConsignmentRequest($this)
                        ->setStoreId($this->getOrder()->getStoreId())
                        ->sendRequest()
                        ->getRequestResponse();

        $aResponse = json_decode($response);

        /**
         * Validate the response.
         */
        if (!is_object($aResponse)
            || !isset($aResponse->data)
            || !is_numeric($aResponse->data->ids[0]->id)
        ) {
            throw new DMP_PostNL_Exception(
                $this->helper->__('Invalid createConsignment response: %s', $api->getRequestErrorDetail()),
                'MYPA-0012'
            );
        }

        /**
         * Get the consignment ID and set it.
         */
        $consignmentId = (int) $aResponse->data->ids[0]->id;

        $apiInfo    = Mage::getModel('dmp_postnl/api_postNL');
        $responseShipments = $apiInfo->getConsignmentsInfoData(array($consignmentId));
        $responseShipment = $responseShipments[0];

        $consignmentIds[] = $responseShipment->id;
        foreach ($responseShipment->secondary_shipments as $secondaryShipments) {
            $consignmentIds[] = $secondaryShipments->id;
        }
        $consignmentIds = implode(';', $consignmentIds);

        if($responseShipment){
            $this->updateStatus($responseShipment);
        }

        $this->setConsignmentId($consignmentIds);

        return $this;
    }

    /**
     * Send barcode mail and set status history comment
     *
     * @param array $responseShipment
     *
     * @return bool
     * @throws DMP_PostNL_Exception
     */
    public function updateStatus($responseShipment)
    {
        $barcodeCollection[] = $responseShipment->barcode;

        foreach ($responseShipment->secondary_shipments as $secondaryShipments) {
            array_push($barcodeCollection, $secondaryShipments->barcode);
        }

        $barcodes = implode(',', $barcodeCollection);

        if (is_object($responseShipment)) {

            $this->setStatus($responseShipment->status);

            if($responseShipment->status > 6){
                $this->setIsFinal('1');
            }

            /**
             * check if barcode is available
             */
            if ($this->getBarcode() === null && $barcodes != $this->getBarcode() && (int)$this->getBarcodeSend() == false && !empty($responseShipment->barcode)) {

                $this->setBarcode($barcodes);

                if ($barcodes) {
                    $this->addTrackingCodeToShipment($barcodes);
                }

                $isSend = $this->helper->sendBarcodeEmail($barcodeCollection, $this);

                //add comment to order-comment history
                $shippingAddress = $this->getShippingAddress();
                $barcodeUrl = $this->helper->getBarcodeUrl($barcodes, $shippingAddress);
                if ($isSend) {
                    //add comment to order-comment history
                    $comment = $this->helper->__('Track&amp;Trace e-mail is sent: %s', $barcodeUrl);

                    // flag the postnl shipment that barcode is send
                    $this->setBarcodeSend(true);

                } else {
                    $comment = $this->helper->__('Track&amp;Trace link: %s', $barcodeUrl);
                }

                $this->helper->log($comment);

                /** @var Mage_Sales_Model_Order $order */
                $order = $this->getOrder();
                $order->addStatusHistoryComment($comment)
                    ->setIsVisibleOnFront(false)
                    ->setIsCustomerNotified(true);
                $order->save();
                $this->setOrder($order);
            }

            if($this->hasDataChanges()){
                $this->save();
            }

            return true;

        } else {
            return false;
        }
    }

    /**
     * Adds Magento tracking information to the order containing the previously retrieved barcode.
     *
     * @param string $trackAndTraceCode
     *
     * @return $this
     *
     * @throws DMP_PostNL_Exception
     */
    public function addTrackingCodeToShipment($trackAndTraceCode)
    {
        $shipment = $this->getShipment();

        if (!$shipment || !$trackAndTraceCode) {
            throw new DMP_PostNL_Exception(
                $this->helper->__(
                    'Unable to add tracking info: no track&amp;trace code or shipment available.'
                ),
                'MYPA-0013'
            );
        }

        $carrierCode = self::POSTNL_CARRIER_CODE;
        $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/name', $shipment->getStoreId());

        $trackingCode =  explode(",", $trackAndTraceCode);

        foreach ($trackingCode as $trackAndTraceCode) {

            $data = array(
                'carrier_code' => $carrierCode,
                'title'        => $carrierTitle,
                'number'       => $trackAndTraceCode,
            );

            /**
             * @var Mage_Sales_Model_Order_Shipment_Track $track
             */
            $track = Mage::getModel('sales/order_shipment_track')->addData($data);
            $shipment->addTrack($track);

            /**
             * Save the Mage_Sales_Order_Shipment object
             *
             * @var Mage_Core_Model_Resource_Transaction $transaction
             */
            $transaction = Mage::getModel('core/resource_transaction');
            $transaction->addObject($shipment)
                        ->save();
        }

        return $this;
    }

    /**
     * Check if this shipment's destination is the Netherlands.
     *
     * @return bool
     */
    public function isDutchShipment()
    {
        $shippingAddress = $this->getShippingAddress();
        $country = $shippingAddress->getCountryId();

        if ($country == 'NL') {
            return true;
        }

        return false;
    }

    /**
     * Checks if the given shipment type is supported by this extension.
     *
     * @param $type
     *
     * @return bool
     */
    protected function _isValidType($type)
    {
        $isValid = false;
        switch ($type) {
            case self::ALIAS_PACKAGE_TYPE_NORMAL: //no break
            case self::ALIAS_PACKAGE_TYPE_UNPAID:
                $isValid = true;
                break;
             case self::ALIAS_PACKAGE_TYPE_MAILBOX:
                if ($this->isDutchShipment()) {
                    $isValid = true;
                }
                break;
            //no default
        }

        return $isValid;
    }

    /**
     * @return $this
     */
    protected function _beforeSave()
    {
        /**
         * If this object is new and does not yet have a status, set the 'new' status.
         */
        if (!$this->getId() && $this->isObjectNew() && !$this->hasStatus()) {
            $this->setStatus(self::STATUS_NEW);
        }

        return parent::_beforeSave();
    }

    /**
     * Get total weight
     *
     * @return float|int
     */
    private function getTotalWeight()
    {
        $totalWeight = 0;
        /** @var Mage_Sales_Model_Resource_Order_Shipment_Item $shipmentItem */
        $shipmentItems = $this->getShipment()->getItemsCollection();
        foreach ($shipmentItems as $shipmentItem) {
            $totalWeight += (float)$shipmentItem->getData('weight') * $shipmentItem->getData('qty');
        }
        return $totalWeight;
    }
}
