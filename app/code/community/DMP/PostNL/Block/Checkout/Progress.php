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
 * @copyright   Copyright (c) 2019 DM Productions B.V.
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class DMP_PostNL_Block_Checkout_Progress extends Mage_Checkout_Block_Onepage_Progress
{
    /**
     * @return string|false
     */
    public function getPakjeGemakAddress()
    {
        $quote =$this->getCheckout()->getQuote();

        $pakjeGemakAddress = false;
        /** @var Mage_Sales_Model_Quote_Address $address */
        foreach ($quote->getAllAddresses() as $address) {
            if ($address->getAddressType() == 'pakje_gemak') {
                $pakjeGemakAddress = $address;
                break;
            }
        }

        if (!$pakjeGemakAddress) {
            return false;
        }

        return $pakjeGemakAddress->format('html');
    }
}