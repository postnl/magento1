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
class DMP_PostNL_Block_Adminhtml_Widget_Grid_Column_Renderer_ShippingStatus
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Additional column names used
     */
    const SHIPPING_METHOD_COLUMN = 'shipping_method';
    const POSTCODE_COLUMN = 'postcode';
    const COUNTRY_ID_COLUMN = 'country_id';
    const BARCODE_COLUMN = 'barcode';
    const STATUS_COLUMN = 'status';

    /**
     * Renders the barcode column. This column will be empty for non-PostNL shipments.
     * If the shipment has been confirmed, it will be displayed as a track& trace URL.
     * Otherwise the bare code will be displayed.
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        /** @var DMP_PostNL_Helper_Data $helper */
        $helper = $this->helper('dmp_postnl');
        $html = '';

        $order = Mage::getModel('sales/order')->load($row->getId());
        $postNLShipments = Mage::getModel('dmp_postnl/shipment')
            ->getCollection()
            ->addFieldToFilter('order_id', $row->getId());

        /** @var Mage_Sales_Model_Order $order */
        $shippingMethod = $order->getShippingMethod();
        if($shippingMethod === null)
            return '';

        /**
         * The shipment was not shipped using PostNL
         */
        if (!$helper->shippingMethodIsPostNL($shippingMethod) || $order->getIsVirtual())
            return '';

        $countryCode = $order->getShippingAddress()->getCountryId();

        /**
         * Check if any data is available.
         * If not available, show send link and country code
         */
        if (count($postNLShipments) > 0) {
            /**
             * Create a track & trace URL based on shipping destination
             */
            $postcode = $order->getShippingAddress()->getPostcode();
            $destinationData = array(
                'countryCode' => $countryCode,
                'postcode' => $postcode,
            );

            /** @var DMP_PostNL_Model_Shipment $postNLShipment */
            $i = 0;
            foreach ($postNLShipments as $postNLShipment) {

                if ($i++ == 1)
                    $html .= "<br />";

                $barcodeCollection = explode(",", $postNLShipment->getBarcode());
                foreach ($barcodeCollection as $barcode) {

                    $barcodeUrl = $helper->getBarcodeUrl($barcode, $destinationData, false);
                    if ($postNLShipment->getBarcode()) {
                        $html .= "<a href='{$barcodeUrl}' target='_blank'>{$barcode}</a>";
                    }

                    if ($postNLShipment->getConsignmentId() && $postNLShipment->getShipment()->getShippingAddress() && in_array($postNLShipment->getShipment()->getShippingAddress()->getCountry(), $helper->getReturnCountries())) {
                        $shipmentUrl = Mage::helper('adminhtml')->getUrl("*/sales_shipment/view", array('shipment_id' => $postNLShipment->getShipment()->getId()));
                        $html        .= " <a href='{$shipmentUrl}' style='text-decoration:none;' title='{$helper->__('Shipment')}'><img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAMAAABFjsb+AAAAe1BMVEUAAADqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgHqdgEq7PRIAAAAKHRSTlMAAQIHCA8RFRkeIy5BRE9fYWNmaWxveYKLjJWanZ6qsLnBzM/X6O3xz27B/QAAAHdJREFUGFd1zssSwUAYROEe4i5CiGtMCOK8/xNaTGqU+ctZdX2rlv7k/NEStaXUnAe6pvGX/WrmvhR7rweSan57ZFKZGHdJVYqLHp9FsalOLQAHBey/ZDugVcD4bwldWOU23r3xUlrO1dic3NiEobHp2ZBGY2uSPuP7Ek2Y8RzhAAAAAElFTkSuQmCC' style='height: 15px; margin-bottom: -4px;'></a>";
                    }
                    $html .= '&nbsp; <small>' . $this->__('status_' . $postNLShipment->getStatus()) . "</small><br>";
                }

            }
        } elseif ($order->canShip()) {

            $sendText = strtolower($this->__('Send'));

            // Only show send link color if status is nog pending or processing
            if (strpos('pending', $row->getData('status')) === null && $row->getData('status') != 'processing') {
                $colorSendText = 'color:green';
            } else {
                $colorSendText = '';
            }

            $orderSendUrl = Mage::helper('adminhtml')->getUrl("adminhtml/sales_order_shipment/start", array('order_id' => $row->getId()));
            $data = json_decode($order->getPostnlData(), true);
            if ($data !== null && key_exists('date', $data) && $data['date'] !== null && $countryCode != 'BE') {
                $dateTime = strtotime($data['date'] . ' 00:00:00');
                $dropOffDate = $helper->getDropOffDay($dateTime);
                $sDropOff = Mage::app()->getLocale()->date($dropOffDate)->toString('d MMM');

                /**
                 * Show info text plus link to send
                 */
                if (date('Ymd') == date('Ymd', $dropOffDate)) {
                    $actionHtml = '<a class="scalable go" href="' . $orderSendUrl . '" style="' . $colorSendText . '">' . $this->__('Today') . ' ' . $sendText . '</a> ';
                } else if (date('Ymd') > date('Ymd', $dropOffDate)) {
                    $actionHtml = $sDropOff . ' <a class="scalable go" href="' . $orderSendUrl . '" style="' . $colorSendText . '">' . $sendText . '</a> <span style="color:red;font-size: 115%;">&#x2757;</span>';
                } else {
                    $actionHtml = $sDropOff . ' <span style="font-size: 115%;">&#8987;</span>';
                }
            } else {
                if ($countryCode == 'BE') {
                    $sendText = $this->__('Today') . ' ' . $sendText;
                }
                $actionHtml = ' <a class="scalable go" href="' . $orderSendUrl . '" style="' . $colorSendText . '">' . $sendText . '</a>';
            }

            $html .= '<small>';

            // Letterbox or normal package
            $shippingMethod = $order->getShippingMethod();
            if ($helper->shippingMethodIsPakjegemak($shippingMethod)) {
                $html .= $this->__('Normal') . ' ';
            } else {
                $type = $helper->getPackageType($order->getAllVisibleItems(), $order->getShippingAddress()->getCountryId(), true);
                $html .= $type . ' ';
            }

            $html .= $countryCode . ' - </small>' . $actionHtml;
        }

        if (!$html)
            $html = $countryCode;

        return $html;
    }
}
