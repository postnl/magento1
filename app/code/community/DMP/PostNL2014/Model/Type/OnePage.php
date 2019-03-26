<?php

/**
 * Short_description
 *
 * LICENSE: This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 *
 * If you want to add improvements, please create a fork in our GitHub:
 * https://github.com/postnl
 *
 * @author      Reindert Vetter <reindert@postnl.nl>
 * @copyright   2010-2016 PostNL
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US  CC BY-NC-ND 3.0 NL
 * @link        https://github.com/postnl/sdk
 * @since       File available since Release 0.1.0
 */

class DMP_PostNL2014_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{

    /**
     * Specify quote shipping method
     *
     * Rewrite from Mage_Checkout_Model_Type_Onepage
     *
     * @param   string $shippingMethod
     * @return  array
     */
    public function saveShippingMethod($shippingMethod)
    {
        // Save the PostNL data in quote
        if(Mage::getModel('dmp_postnl/checkout_service')->savePostNLShippingMethod() != true) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }

        /**
         * From Mage_Checkout_Model_Type_Onepage
         */
        if (empty($shippingMethod)) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $this->getQuote()->getShippingAddress()
            ->setShippingMethod($shippingMethod);

        $this->getCheckout()
            ->setStepData('shipping_method', 'complete', true)
            ->setStepData('payment', 'allow', true);

        return array();
    }
}