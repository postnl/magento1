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
 * @method boolean hasQuote()
 * @method DMP_PostNL_Model_Observer_SavePgAddress setQuote(Mage_Sales_Model_Quote $quote)
 */
class DMP_PostNL_Model_Observer_SavePgAddress extends Varien_Object
{
    /**
     * Get the current quote.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if ($this->hasQuote()) {
            return $this->_getData('quote');
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $this->setQuote($quote);
        return $quote;
    }

    /**
     * Copies a PakjeGemak address from the quote to the newly placed order.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @throws Exception
     *
     * @event sales_order_place_after
     *
     * @observer dmp_postnl_copy_pg_address
     */
    public function copyAddressToOrder(Varien_Event_Observer $observer)
    {
        /**
         * @var Mage_Sales_Model_Order $order
         * @var DMP_PostNL_Helper_Data $helper
         */
        $order  = $observer->getEvent()->getOrder();
        $helper = Mage::helper('dmp_postnl');

        /**
         * @var Mage_Sales_Model_Quote $quote
         */
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if (!$quote || !$quote->getId()) {
            $quote = $order->getQuote();
        }

        if (!$quote || !$quote->getId()) {
            return $this;
        }

        /**
         * Set PostNL json data from checkout
         */
        $postNLData = $quote->getPostnlData();
        $postNLData = $postNLData == null ? array() : json_decode($postNLData, true);
        $postNLData['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $order->setPostnlData(json_encode($postNLData));

        $aPostNLData = $postNLData;
        if (key_exists('date', $aPostNLData)) {
            $dateTime = strtotime($aPostNLData['date'] . ' 00:00:00');
            $dropOffDate = $helper->getDropOffDay($dateTime);
            $sDropOff = date("Y-m-d", $dropOffDate);

            if ($usePgAddress = $helper->getConfig('show_delivery_date_on_invoice') === '1' &&
                $helper->getConfig('deliverydays_window', 'checkout') != 'hide' &&
                $order->getShippingAddress()->getCountryId() != 'BE'
            ) {
                $methodDescription = $order->getShippingDescription();
                $methodDescription .= ' ' . date("d-m-Y", $dateTime);

                $time = $aPostNLData['time'][0];
                if (!empty($time)) {
                    $hasEndTime = key_exists('end', $time);
                    if ($hasEndTime)
                        $methodDescription .= ' van';

                    $methodDescription .= ' ' . substr($time['start'], 0, -3);

                    if ($hasEndTime)
                        $methodDescription .= ' tot ' . substr($time['end'], 0, -3);
                }

                $order->setShippingDescription($methodDescription);
            }

            $order->setPostNLSendDate($sDropOff);
        }

        /**
         * Get the PakjeGemak address for this quote.
         * If no PakjeGemak address was found we don't need to do anything else.
         */
        $pakjeGemakAddress = $helper->getPgAddress($quote);
        if($postNLData === null || !key_exists('location', $postNLData) || !$pakjeGemakAddress){
            Mage::getModel('dmp_postnl/checkout_service')->removePgAddress($quote);
            return $this;
        }

        Mage::getModel('dmp_postnl/checkout_service')->copyAddressToOrder($order, $pakjeGemakAddress);
        return $this;
    }

}

