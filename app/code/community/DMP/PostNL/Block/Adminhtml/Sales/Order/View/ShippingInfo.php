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
class DMP_PostNL_Block_Adminhtml_Sales_Order_View_ShippingInfo extends Mage_Adminhtml_Block_Abstract
{
    /**
     * @var Mage_Sales_Model_Order|DMP_PostNL_Model_Sales_Order
     */
    protected $_order;

    /**
     * @var DMP_PostNL_Helper_Data
     */
    protected $_helper;
    protected $_postNLShipments;

    public function __construct()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $this->_order = Mage::getModel('sales/order')->load($orderId);
        $this->_helper = Mage::helper('dmp_postnl');

        $this->_postNLShipments = Mage::getModel('dmp_postnl/shipment')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->_order->getId());
    }

    /**
     * Collect options selected at checkout and calculate type consignment
     *
     * @return string
     */
    public function getCheckoutOptionsHtml()
    {

        $html = false;

        $pgAddress = $this->_helper->getPgAddress($this->_order);
        /** @var object $data Data from checkout */
        $data = $this->_order->getPostnlData() !== null ? json_decode($this->_order->getPostnlData(), true) : false;
        $shippingMethod = $this->_order->getShippingMethod();

        if ($pgAddress && $this->_helper->shippingMethodIsPakjegemak($shippingMethod))
        {
            if(is_array($data) && key_exists('location', $data)){

                $html .= $this->__('PostNL location: ');

                if ($pgAddress->getCountryId() != 'BE') {
                    $dateTime = date('d-m-Y H:i', strtotime($data['date'] . ' ' . $data['start_time']));
                    $html .= $dateTime . ', ';
                }

                if ($data['price_comment'] != 'retail') {
                    $html .= $this->__('TYPE_' . $data['price_comment']) . ', ';
                }

                $html .= $data['location']. ', ' . $data['city']. ' (' . $data['postal_code']. ')';
            } else {
                /** Old data from orders before version 1.6.0 */
                $html .= $this->__('PostNL location:') . ' ' . $pgAddress->getCompany() . ' ' . $pgAddress->getCity();
            }
        } else {

            $hasExtraOptions = $this->_helper->shippingHasExtraOptions($this->_order->getShippingMethod());
            // Get package type
            $html .= $this->_helper->getPackageType($this->_order->getAllVisibleItems(), $this->_order->getShippingAddress()->getCountryId(), true, $hasExtraOptions) . ' ';

            if(is_array($data) && key_exists('date', $data)){

                $dateTime = date('d-m-Y H:i', strtotime($data['date']. ' ' . $data['time'][0]['start']));
                $html .= $this->__('deliver:') .' ' . $dateTime;

                if($data['time'][0]['price_comment'] != 'standard')
                    $html .=  ', ' . $this->__('TYPE_' . $data['time'][0]['price_comment']);

                if(key_exists('home_address_only', $data) && $data['home_address_only'])
                    $html .=  ', ' . strtolower($this->__('Home address only'));

                if(key_exists('signed', $data) && $data['signed'])
                    $html .=  ', ' . strtolower($this->__('Signature on receipt'));
            }
        }

        if (is_array($data) && key_exists('browser', $data))
            $html = ' <span title="'.$data['browser'].'"">'.$html.'</span>';

            return $html !== false ? '<br>' . $html : '';
    }

    /**
     * Get all current PostNL options
     *
     * @return string
     */
    public function getCurrentOrderOptionsHtml()
    {
        $optionsHtml = '';
        /** @var $postNLShipment DMP_PostNL_Model_Shipment */
        foreach ($this->_postNLShipments as $postNLShipment) {
            $shipmentUrl = Mage::helper('adminhtml')->getUrl("*/sales_shipment/view", array('shipment_id'=>$postNLShipment->getShipment()->getId()));

            $barcodeCollection = explode(",", $postNLShipment->getBarcode());
            foreach ($barcodeCollection as $barcode) {
                $linkText = $barcode ?: $this->__('Shipment');
                $optionsHtml .= '<p><a href="'.$shipmentUrl.'">' . $linkText . '</a>: ' . $this->_helper->getCurrentOptionsHtml($postNLShipment) . '</p>';
            }
        }

        return $optionsHtml;
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
        $shippingMethod = $this->_order->getShippingMethod();

        if (!$this->_order
            || !$this->_helper->shippingMethodIsPostNL($shippingMethod)
            || $this->_order->getIsVirtual()
        ) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Check if the shipment is placed using Pakjegemak
     *
     * @return bool
     */
    public function getIsPakjeGemak()
    {

        $shipment = Mage::registry('current_shipment');

        return $this->_helper->shippingMethodIsPakjegemak($shipment->getOrder()->getShippingMethod());
    }
}
