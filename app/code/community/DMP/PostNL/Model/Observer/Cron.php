<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2016 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class DMP_PostNL_Model_Observer_Cron
{

    /** @var DMP_PostNL_Helper_Data $helper */
    public $helper;

    /**
     * Init
     */
    public function __construct()
    {
        $this->helper = Mage::helper('dmp_postnl');
    }

    /**
     * @return $this
     */
    public function checkStatus()
    {
        $this->_checkEUShipments();
        $this->_checkCDShipments();

        return $this;
    }

    protected function _checkEUShipments()
    {
        $resource   = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('dmp_postnl/shipment_collection');

        $collection->getSelect()->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.entity_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array());

        $collection->addFieldToFilter('shipping_address.country_id', array(
                'in' => array($this->helper->whiteListCodes()))
        );
        $collection->addFieldToFilter('main_table.is_final', array('eq' => '0'));
        $collection->addFieldToFilter('main_table.created_at', array(
                'gt' => date('Y-m-d', strtotime('-21 day')))
        );

        $this->_checkCollectionStatus($collection);
    }

    protected function _checkCDShipments()
    {
        $resource   = Mage::getSingleton('core/resource');
        $collection = Mage::getResourceModel('dmp_postnl/shipment_collection');

        $collection->getSelect()->joinLeft(
            array('shipping_address' => $resource->getTableName('sales/order_address')),
            "main_table.entity_id=shipping_address.parent_id AND shipping_address.address_type='shipping'",
            array());

        $collection->addFieldToFilter('main_table.is_final', array('eq' => '0'));
        $collection->addFieldToFilter('shipping_address.country_id', array(
                'nin' => array($this->helper->whiteListCodes()))
        );
        $collection->addFieldToFilter('main_table.created_at', array(
                'gt' => date('Y-m-d', strtotime('-2 months')))
        );

        $this->_checkCollectionStatus($collection);
    }

    /**
     * Retrieve shipment status from Postnl
     *
     * @param $collection
     *
     * @throws Exception
     */
    protected function _checkCollectionStatus($collection)
    {
        /**
         * @var Mage_Sales_Model_Order_Shipment $shipment
         * @var DMP_PostNL_Model_Shipment $postNLShipment
         */
        $consignmentIds = array();
        $postNLShipments = array();

        foreach ($collection as $postNLShipment){
            if($postNLShipment->hasConsignmentId()){
                $consignmentId = $postNLShipment->getConsignmentId();
                $consignmentIds[] = $consignmentId;
                $postNLShipments[$consignmentId] = $postNLShipment;
            }
        }


        $apiInfo    = Mage::getModel('dmp_postnl/api_postNL');
        $responseShipments = $apiInfo->getConsignmentsInfoData($consignmentIds);

        if($responseShipments){
            foreach($responseShipments as $responseShipment){
                $postNLShipment = $postNLShipments[$responseShipment->id];
                $postNLShipment->updateStatus($responseShipment);
            }
        }
    }
}
