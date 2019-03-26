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
class DMP_PostNL2014_Model_Adminhtml_Observer_SystemConfig
{
    /**
     * Adds a button to the system > config page for the PostNL section, allowing the admin to download all Postnl
     * debug logs.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event controller_action_layout_render_before_adminhtml_system_config_edit
     *
     * @observer postnl_add_download_log_button
     */
    public function addDownloadLogButton(Varien_Event_Observer $observer)
    {
        $section = Mage::app()->getRequest()->getParam('section');
        if ($section !== 'dmp_postnl') {
            return $this;
        }

        $configEditBlock = false;
        $contentBlocks = Mage::getSingleton('core/layout')->getBlock('content')->getChild();

        /**
         * @var Mage_Core_Block_Abstract $block
         */
        foreach ($contentBlocks as $block) {
            if ($block instanceof Mage_Adminhtml_Block_System_Config_Edit) {
                $configEditBlock = $block;
                break;
            }
        }

        if (!$configEditBlock) {
            return $this;
        }

        $helper = Mage::helper('dmp_postnl');

        $onClickUrl = $configEditBlock->getUrl('adminhtml/postnlAdminhtml_config/downloadLogs');
        $onClick = "setLocation('{$onClickUrl}')";

        $button = $configEditBlock->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(
            array(
                'label'   => $helper->__('Download log files'),
                'onclick' => $onClick,
                'class'   => 'download',
            )
        );

        $configEditBlock->setChild('download_logs_button', $button);
        $configEditBlock->setTemplate('DMP/PostNL2014/system/config/edit.phtml');

        return $this;
    }
}
