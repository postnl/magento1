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
 *
 * @method boolean hasQuote()
 * @method DMP_PostNL2014_Model_Observer_SavePgAddress setQuote(Mage_Sales_Model_Quote $quote)
 */
class DMP_PostNL2014_Model_Observer_SaveConfig extends Varien_Object
{
    /**
     * Saves the verssion numbers of the extension, store and php
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event controller_action_postdispatch_adminhtml_system_config_save
     *
     * @observer dmp_postnl_save_config
     */
    public function registerConfig(Varien_Event_Observer $observer)
    {
        /**
         * @var Mage_Core_Controller_Varien_Front $controller
         */
        $controller    = $observer->getControllerAction();
        $section = $controller->getRequest()->getParam('section');

        if($section == 'postnl')
        {
            $api = Mage::getModel('dmp_postnl/api_postNL');

            $api->createRegisterConfigRequest()->sendRequest(); // no getRequestResponse()
        }

        return $this;
    }
}
