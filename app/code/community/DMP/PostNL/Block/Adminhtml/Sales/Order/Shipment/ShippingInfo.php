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
 */
class DMP_PostNL_Block_Adminhtml_Sales_Order_Shipment_ShippingInfo extends Mage_Adminhtml_Block_Template
{
    /**
     * @var Mage_Sales_Model_Order_Shipment
     */
    protected $_shipment;

    /**
     * @var DMP_PostNL_Model_Shipment
     */
    protected $_postNLShipment;

    public function __construct()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $this->_shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
        $this->_postNLShipment = Mage::getModel('dmp_postnl/shipment')->load($shipmentId, 'shipment_id');
    }

    /**
     * Do a few checks to see if the template should be rendered before actually rendering it.
     *
     * @return string
     *
     * @see Mage_Adminhtml_Block_Abstract::_toHtml()
     */
    protected function _toHtml()
    {
        $helper = Mage::helper('dmp_postnl');

        if (!$this->_shipment
            || !$helper->shippingMethodIsPostNL($this->_shipment->getOrder()->getShippingMethod())
            || $this->getShipment()->getOrder()->getIsVirtual()
        ) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Get html for the PGaddress
     *
     * @return string
     */
    public function getPgAddressHtml()
    {
        $res = array();
        $pgAddress = Mage::helper('dmp_postnl')->getPgAddress($this->_shipment->getOrder());

        if($pgAddress)
        {
            $res = array(
                $pgAddress->getCompany(),
                implode(' ', $pgAddress->getStreet()),
                $pgAddress->getPostcode() . ' ' . $pgAddress->getCity() . ' (' . $pgAddress->getCountry() . ')',
            );
        }

        return empty($res) ? '' : '<p>' . implode('<br/>', $res) . '</p>';
    }

    public function isCredited()
    {
        return false;
    }

    public function getCreditText()
    {
        $helper = Mage::helper('dmp_postnl');
        return $helper->__('Credit Consignment');
    }

    public function getRetourlinkUrl()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        return $this->getUrl('adminhtml/postnlAdminhtml_config/generateRetourlink',array('shipment_id'=>$shipmentId));
    }

    public function getCreditUrl()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        return $this->getUrl('adminhtml/postnlAdminhtml_config/creditConsignment',array('shipment_id'=>$shipmentId));
    }

    /**
     * Calculates a shipment's base grand total based on it's shipment items
     *
     * @return float|null
     */
    public function getOrderTotal()
    {
        if ($this->hasShipmentBaseGrandTotal()) {
            return $this->_getData('shipment_base_grand_total');
        }

        /**
         * Check if this Postnl shipment has a linked Mage_Sales_Model_Order_Shipment object
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

        $this->setShipmentBaseGrandTotal($baseGrandTotal);
        return $baseGrandTotal;
    }

    /**
     * Check if shipment country needs customs
     *
     * @return bool
     */
    public function countryNeedsCustoms()
    {
        $shipment = $this->getShipment();
        $helper = Mage::helper('dmp_postnl');

        $shippingAddress = $shipment->getShippingAddress();

        $countryCode = $shippingAddress->getCountry();

        return $helper->countryNeedsCustoms($countryCode);

    }

    /**
     * Get this shipment's country of destination;
     *
     * @return mixed
     */
    public function getDestinationCountry()
    {
        if ($this->hasDestinationCountry()) {
            $countryCode = $this->_getData('destination_country');
            return $countryCode;
        }

        $shipment = $this->getShipment();

        $shippingAddress = $shipment->getShippingAddress();

        $countryCode = $shippingAddress->getCountry();

        $this->setDestinationCountry($countryCode);
        return $countryCode;
    }

    /**
     * get storeid from where the order was placed
     *
     * @return int
     */
    public function getOrderStoreId()
    {
        $shipment = $this->getShipment();

        return $shipment->getOrder()->getStoreId();
    }

    /**
     * get the customs type array
     *
     * @return array
     */
    public function getCustomsTypeOptions()
    {
        return Mage::getModel('dmp_postnl/system_config_source_customs')->toOptionArray();
    }

    public function getIsSelected($shipmentOption)
    {
        $helper = Mage::helper('dmp_postnl');
        $storeId = $this->getOrderStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        $configValue = $helper->getConfig($shipmentOption,'shipment',$storeId);
        if(!empty($configValue) && $configValue > 0){
            if($orderTotalShipped >= $configValue){
                return 'checked="checked"';
            }
        }
        return '';
    }

    /**
     * @return string
     */
    public function getIsHomeSelected()
    {
        return $this->getIsSelected('home_address_only');

    }

    /**
     * @return string
     */
    public function getIsSignatureOnReceipt()
    {
        return $this->getIsSelected('signature_on_receipt');
    }

    /**
     * @return string
     */
    public function getIsReturnOnNoAnswer()
    {
        return $this->getIsSelected('return_if_no_answer');
    }

    /**
     * @return string
     */
    public function getIsInsured()
    {
        //load helper, store id and orderTotal
        $helper            = Mage::helper('dmp_postnl');
        $storeId           = $this->getOrderStoreId();
        $orderTotalShipped = $this->getOrderTotal();

        //get the insured values
        $insuredType100    = $helper->getConfig('insured_100',  'shipment', $storeId);
        $insuredType250    = $helper->getConfig('insured_250', 'shipment', $storeId);
        $insuredType500    = $helper->getConfig('insured_500', 'shipment', $storeId);

        //check if the values are not empty/zero
        $insuredType100     = (!empty($insuredType100) && $insuredType100 > 0)? $insuredType100 : false;
        $insuredType250    = (!empty($insuredType250) && $insuredType250 > 0)? $insuredType250 : false;
        $insuredType500    = (!empty($insuredType500) && $insuredType500 > 0)? $insuredType500 : false;

        //if nothing is filled in, then set the default values, but do not pre-select
        $selected = 'checked="checked"';
        if(
            false === $insuredType100 &&
            false === $insuredType250 &&
            false === $insuredType500
        ){
            $insuredType100 = 100;
            $insuredType250 = 250;
            $insuredType500 = 500;
            $selected = 0;
        }

        if(false !== $insuredType500 && $orderTotalShipped > $insuredType500){
            $insuredValue = $insuredType500;
            $insuredUpTo = 500;
        }elseif(false !== $insuredType250 && $orderTotalShipped > $insuredType250){
            $insuredValue = $insuredType250;
            $insuredUpTo = 250;
        }elseif(false !== $insuredType100 && $orderTotalShipped > $insuredType100){
            $insuredValue = $insuredType100;
            $insuredUpTo = 100;
        }else{
            $insuredValue = 0;
            $insuredUpTo = 0;
            $selected = 0;
        }

        $returnArray = array(
            'option'         => 'insured',
            'selected'       => $selected,
            'insuredAmount' => 0,
            'insuredUpTo'   => 0,
        );

        if($insuredValue > 0){
            $returnArray = array(
                'option'        => 'insured',
                'selected'      => $selected,
                'insuredAmount' => $insuredValue,
                'insuredUpTo'   => $insuredUpTo,
            );
        }

        return $returnArray;
    }

    /**
     * Get current shipment
     *
     * @return Mage_Sales_Model_Order_Shipment.
     */
    public function getShipment()
    {
        if ($this->hasShipment()) {
            return $this->_getData('shipment');
        }

        $shipment = Mage::registry('current_shipment');

        $this->setShipment($shipment);
        return $shipment;
    }

    /**
     * Check if the shipment is placed using Pakjegemak
     *
     * @return bool
     */
    public function getIsPakjeGemak()
    {
        $helper   = Mage::helper('dmp_postnl');
        $shipment = Mage::registry('current_shipment');

        if($helper->getPgAddress($shipment->getOrder())){
            return true;
        }
        return false;
    }
}
