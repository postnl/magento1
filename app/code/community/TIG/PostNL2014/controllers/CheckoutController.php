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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class TIG_PostNL2014_CheckoutController extends Mage_Core_Controller_Front_Action
{
    const CC_BE = 'BE';
    const CC_NL = 'NL';

    /**
     * Generate data in json format for checkout
     */
    public function infoAction()
    {
        /** @var TIG_PostNL2014_Helper_AddressValidation $helper */
        $helper = Mage::helper('tig_postnl/addressValidation');
        /**
         * @var Mage_Sales_Model_Quote $quote
         * @var Mage_Sales_Model_Quote_Item $item
         * @var Mage_Sales_Model_Quote_Address $address
         * @var Mage_Sales_Model_Quote_Address_Rate $rate
         */
        $quote = Mage::getModel('checkout/cart')->getQuote();

        $free = false;
        foreach ($quote->getItemsCollection() as $item) {
            $free = $item->getData('free_shipping') == '1' ? true : false;
            break;
        }

        $basePrice = 0;
        $_incl = 0;
        if(!$free) {
            $address = $quote->getShippingAddress();
            $address->requestShippingRates();

            foreach ($address->getShippingRatesCollection() as $rate) {
                if ($rate->getCarrier() == 'postnl' &&
                    ($rate->getMethod() == 'flatrate' || $rate->getMethod() == 'tablerate') &&
                    key_exists('rate_id', $rate->getData()) && $rate->getData('rate_id') !== null
                ) {

                    $_excl = $this->getShippingPrice($rate->getPrice(), $quote);
                    $_incl = $this->getShippingPrice($rate->getPrice(), $quote, true);
                    if (Mage::helper('tax')->displayShippingBothPrices() && $_incl != $_excl) {
                        $basePrice = $_incl;
                    } else {
                        $basePrice = $_excl;
                    }
                }
            }
        }
        Mage::getSingleton('core/session')->setPostNLBasePrice($_incl);

        $data = array();

        $data['address'] = $helper->getQuoteAddress($quote);

        $general['base_price'] =                    $basePrice;
        $general['cutoff_time'] =                   str_replace(',', ':', $helper->getConfig('cutoff_time', 'checkout'));
        if ($data['address']['country'] == 'NL') {
            $general['deliverydays_window'] = (int)$helper->getConfig('deliverydays_window', 'checkout') == 'hide' ? 0 : $helper->getConfig('deliverydays_window', 'checkout');
        } else {
            $general['deliverydays_window'] = 1;
        }
        $general['dropoff_days'] =                  str_replace(',', ';', $helper->getConfig('dropoff_days', 'checkout'));
        $general['monday_delivery_active'] =        (int)$helper->getConfig('monday_delivery_active', 'checkout');
        $general['saturday_cutoff_time'] =          str_replace(',', ':', $helper->getConfig('saturday_cutoff_time', 'checkout'));
        $general['dropoff_delay'] =                 $helper->getConfig('dropoff_delay', 'checkout');
        $general['base_color'] =                    $helper->getConfig('base_color', 'checkout');
        $general['select_color'] =                  $helper->getConfig('select_color', 'checkout');
        $data['general'] = (object)$general;

        if ($data['address']['country'] == self::CC_BE) {
            $delivery['delivery_title'] =               $helper->getConfig('belgium_delivery_title', 'belgium_delivery');
            $delivery['standard_delivery_titel'] =      $helper->getConfig('belgium_standard_delivery_titel', 'belgium_delivery');
        } else {
            $delivery['delivery_title'] =               $helper->getConfig('delivery_title', 'delivery');
            $delivery['standard_delivery_titel'] =      $helper->getConfig('standard_delivery_titel', 'delivery');
        }
        $delivery['standard_delivery_active'] =        $helper->getConfig('standard_delivery_active', 'delivery') == "1";

        $delivery['only_recipient_active'] =        $helper->getConfig('only_recipient_active', 'delivery') == "1" && $data['address']['country'] == 'NL' ? true : false;
        $delivery['only_recipient_title'] =         $helper->getConfig('only_recipient_title', 'delivery');
        $delivery['only_recipient_fee'] =           $this->getShippingPrice($helper->getConfig('only_recipient_fee', 'delivery'), $quote);
        $delivery['signature_active'] =             $helper->getConfig('signature_active', 'delivery') == "1" && $data['address']['country'] == 'NL' ? true : false;
        $delivery['signature_title'] =              $helper->getConfig('signature_title', 'delivery');
        $delivery['signature_fee'] =                $this->getShippingPrice($helper->getConfig('signature_fee', 'delivery'), $quote);
        $delivery['signature_and_only_recipient_fee'] =                $this->getShippingPrice($helper->getConfig('signature_and_only_recipient_fee', 'delivery'), $quote);
        $data['delivery'] = (object)$delivery;

        $morningDelivery['active'] =                $helper->getConfig('morning_delivery_active', 'morning_delivery') == "1" && $helper->getConfig('age_check', 'delivery') != "1" && $data['address']['country'] == 'NL' ? true : false;
        $morningDelivery['morning_delivery_titel'] = $helper->getConfig('morning_delivery_titel', 'morning_delivery');
        $morningDelivery['fee'] =                   $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('morning_delivery_fee', 'morning_delivery'), $quote));
        $data['morningDelivery'] = (object)$morningDelivery;

        $eveningDelivery['active'] =                $helper->getConfig('eveningdelivery_active', 'eveningdelivery') == "1" && $helper->getConfig('age_check', 'delivery') != "1" &&  $data['address']['country'] == 'NL' ? true : false;
        $eveningDelivery['eveningdelivery_titel'] = $helper->getConfig('eveningdelivery_titel', 'eveningdelivery');
        $eveningDelivery['fee'] =                   $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('eveningdelivery_fee', 'eveningdelivery'), $quote));
        $data['eveningDelivery'] = (object)$eveningDelivery;

        if ($data['address']['country'] == self::CC_BE) {
            $pickup['active'] = $helper->getConfig('pickup_belgium_active', 'pickup_belgium') == "1" ? true : false;
            $pickup['title'] = $helper->getConfig('pickup_belgium_title', 'pickup_belgium');
            $pickup['fee'] = $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('pickup_belgium_fee', 'pickup_belgium'), $quote));
        } else {
            $pickup['active'] = $helper->getConfig('pickup_active', 'pickup') == "1" ? true : false;
            $pickup['title'] = $helper->getConfig('pickup_title', 'pickup');
            $pickup['fee'] = $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('pickup_fee', 'pickup'), $quote));
        }
        $data['pickup'] = (object)$pickup;

        $pickupExpress['active'] =                  $helper->getConfig('pickup_express_active', 'pickup_express') == "1" && $data['address']['country'] == self::CC_NL ? true : false;
        $pickupExpress['fee'] =                     $this->getExtraPrice($basePrice, $this->getShippingPrice($helper->getConfig('pickup_express_fee', 'pickup_express'), $quote));
        $data['pickupExpress'] = (object)$pickupExpress;

        $info = array(
            'version' => (string) Mage::getConfig()->getModuleConfig("TIG_PostNL2014")->version,
            'data' => (object)$data
        );

        header('Content-Type: application/json');
        echo(json_encode($info));
        exit;
    }
    

    /**
     * Save the PostNL data in quote
     */
    public function save_shipping_methodAction()
    {
        Mage::getModel('tig_postnl/checkout_service')->savePostNLShippingMethod();
    }

    /**
     * For testing the cron
     */
    public function cronAction()
    {
        $cronController = new TIG_PostNL2014_Model_Observer_Cron;
        $cronController->checkStatus();
    }

    /**
     * Get extra price. Check if total shipping price is not below 0 euro
     *
     * @param $basePrice
     * @param $extraPrice
     *
     * @return float
     */
    private function getExtraPrice($basePrice, $extraPrice)
    {
        if ($basePrice + $extraPrice < 0) {
            return 0;
        }
        return (float)$basePrice + $extraPrice;
    }

    /**
     * Get shipping price
     *
     * @param $price
     * @param $quote
     * @param $flag
     *
     * @return mixed
     */
    private function getShippingPrice($price, $quote, $flag = false)
    {
        $flag = $flag ? true : Mage::helper('tax')->displayShippingPriceIncludingTax();
        return (float)Mage::helper('tax')->getShippingPrice($price, $flag, $quote->getShippingAddress());
    }

}
