<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_PostNL2014_Model_Observer_SaveShipment
{
    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     * @event controller_action_predispatch_adminhtml_sales_order_shipment_save
     * @observer tig_postnl_shipment_save
     */
    public function registerConsignmentOption(Varien_Event_Observer $observer)
    {
        /**
         * Retrieve and register the chosen option, if any.
         *
         * @var Mage_Core_Controller_Varien_Front $controller
         */
        $controller                 = $observer->getControllerAction();
        $selectedConsignmentOptions = $controller->getRequest()->getParam('tig_postnl', array());

        if (!empty($selectedConsignmentOptions['shipment_type'])) {
            $shipmentType = $selectedConsignmentOptions['shipment_type'];
            if ($shipmentType != TIG_PostNL2014_Model_Shipment::TYPE_NORMAL) {
                if(isset($selectedConsignmentOptions['create_consignment'])){
                    $selectedConsignmentOptions = array(
                        'shipment_type' => $shipmentType,
                        'create_consignment' => '1',
                    );
                }
            }
        }
        if(key_exists('is_xl', $selectedConsignmentOptions) && $selectedConsignmentOptions['is_xl'] == null)
            $selectedConsignmentOptions['is_xl'] = 0;

        /**
         * Add the selected options to the registry.
         *
         * This registry value will be checked when the PostNL shipment entity is saved.
         */
        if (!empty($selectedConsignmentOptions)) {
            if(!isset($selectedConsignmentOptions['create_consignment'])){
                return $this;
            }
            Mage::register('tig_postnl_consignment_options', $selectedConsignmentOptions);
        }

        return $this;
    }

    /**
     * Saves the chosen consignment options and creates a PostNL shipment for the current shipment.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     * @throws Exception
     * @event sales_order_shipment_save_after
     * @observer tig_postnl_shipment_save_after
     */
    public function saveConsignmentOption(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('tig_postnl');

        /**
         * @var Mage_Sales_Model_Order_Shipment $shipment
         */
        $shipment = $observer->getShipment();

        /**
         * check if order is placed with Postnl
         */
        $shippingMethod = $shipment->getOrder()->getShippingMethod();
        if (!$helper->shippingMethodIsPostNL($shippingMethod) || $shipment->getOrder()->getIsVirtual()) {
            return $this;
        }

        /**
         * check if the current shipment already has a postnl shipment
         */
        if($helper->hasPostNLShipment($shipment->getId())){
            return $this;
        }

        /**
         * check if a new consignment must me made
         */
        $registryOptions = Mage::registry('tig_postnl_consignment_options');
        if(empty($registryOptions) || !isset($registryOptions['create_consignment'])){
            return $this;
        }

        /**
         * check if consignment option matches the Magento shipment
         */
        if (false !== $helper->getPgAddress($shipment->getOrder())
            && (!isset($registryOptions['shipment_type']) ||
                $registryOptions['shipment_type'] != TIG_PostNL2014_Model_Shipment::TYPE_NORMAL
            )
        )
        {
            return $this;
        }

        /**
         * @var TIG_PostNL2014_Model_Shipment $postNLShipment
         */
        $postNLShipment = Mage::getModel('tig_postnl/shipment')->load($shipment->getId());

        $consignmentOptions = $registryOptions;
        if (Mage::registry('tig_postnl_consignment_options')) {
            $consignmentOptions = array_merge($consignmentOptions, Mage::registry('tig_postnl_consignment_options'));
            Mage::unregister('tig_postnl_consignment_options');
        }
        Mage::register('tig_postnl_consignment_options', $consignmentOptions);
        
        $postNLShipment->setShipmentId($shipment->getId())
                       ->setConsignmentOptions()
                       ->createConsignment()
                       ->save();

        $multicolloAmount = (int) $postNLShipment['multi_collo_amount'];

        $i = 1;
        $multicolloAmount--;
        while ($i <= $multicolloAmount) {

            $barcode = $postNLShipment->getBarcode();
            if ($barcode) {
                $carrierCode = TIG_PostNL2014_Model_Shipment::POSTNL_CARRIER_CODE;

                $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/name', $shipment->getStoreId());
                //if the other carrier-method is used, get the title
                if ($helper->getPgAddress($postNLShipment)) {
                    $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/pakjegemak_title', $shipment->getStoreId());
                }

                $data = array(
                    'carrier_code' => $carrierCode,
                    'title'        => $carrierTitle,
                    'number'       => $barcode,
                );

                /**
                 * @var Mage_Sales_Model_Order_Shipment_Track $track
                 */
                $track = Mage::getModel('sales/order_shipment_track')->addData($data);
                $shipment->addTrack($track);
                $trackCollection = $shipment->getTracksCollection();

                foreach ($trackCollection as $track) {
                    $track->save();
                }
            }
            $i++;
        }

        return $this;
    }

}
