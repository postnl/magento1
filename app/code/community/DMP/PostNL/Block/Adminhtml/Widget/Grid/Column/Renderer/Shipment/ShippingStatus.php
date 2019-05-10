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
class DMP_PostNL_Block_Adminhtml_Widget_Grid_Column_Renderer_Shipment_ShippingStatus
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Additional column names used
     */
    const SHIPPING_METHOD_COLUMN = 'shipping_method';
    const POSTCODE_COLUMN        = 'postcode';
    const COUNTRY_ID_COLUMN      = 'country_id';
    const BARCODE_COLUMN         = 'barcode';

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
        /**
         * The shipment was not shipped using PostNL
         */
        $shippingMethod = $row->getData(self::SHIPPING_METHOD_COLUMN);
        if (!Mage::helper('dmp_postnl')->shippingMethodIsPostNL($shippingMethod)) {
            return '';
        }

        /**
         * Check if any data is available.
         */
        $value = $row->getData($this->getColumn()->getIndex());
        if (!$value) {
            return '';
        }

        /**
         * Create a track & trace URL based on shipping destination
         */
        $countryCode = $row->getData(self::COUNTRY_ID_COLUMN);
        $postcode = $row->getData(self::POSTCODE_COLUMN);
        $destinationData = array(
            'countryCode' => $countryCode,
            'postcode'    => $postcode,
        );
        $html = '';
        $barcodeCollection = explode(",", $row->getData(self::BARCODE_COLUMN));
        foreach ($barcodeCollection as $barcode) {

            $barcodeUrl = Mage::helper('dmp_postnl')->getBarcodeUrl($barcode, $destinationData, false, true);
            if ($barcode) {
                $html .= "<a href='{$barcodeUrl}' target='_blank'>{$barcode}</a>";
            }
            $html .= "<small>" . $this->__('status_' . $value) . "</small><br>";

        }

       return $html;
    }
}
