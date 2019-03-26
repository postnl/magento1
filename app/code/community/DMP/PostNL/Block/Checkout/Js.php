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
 * @copyright   Copyright (c) 2019 DM Productions B.V. (http://www.p.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
class DMP_PostNL_Block_Checkout_Js extends Mage_Core_Block_Template
{
    /**
     * Xpath to the 'pakjegemak_active' setting.
     */
    const XPATH_PAKJEGEMAK_ACTIVE = 'carriers/postnl/pakjegemak_active';

    /**
     * Check if PakjeGemak is active before rendering the template.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $pakjeGemakActive = Mage::getStoreConfigFlag(self::XPATH_PAKJEGEMAK_ACTIVE, Mage::app()->getStore()->getId());
        if (!$pakjeGemakActive) {
            return '';
        }

        return parent::_toHtml();
    }
}