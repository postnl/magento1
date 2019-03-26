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
class DMP_PostNL2014_Model_Adminhtml_Observer_ViewShipment extends Varien_Object
{
    const RETOURMAIL_ROUTE = 'adminhtml/postnlAdminhtml_config/generateRetourmail';
    const RETOURLINK_ROUTE = 'adminhtml/postnlAdminhtml_config/generateRetourlink';
    const CREDIT_CONSIGNMENT_ROUTE = 'adminhtml/postnlAdminhtml_config/creditConsignment';

    /**
     * Adds a button to the view-shipment page, allowing the merchant to create a PostNL-consignment.
     * The button is not showed when a consignment is already present.
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     *
     * @event    adminhtml_widget_container_html_before
     *
     * @observer dmp_postnl_adminhtml_view_shipment
     */
    public function adminhtmlWidgetContainerHtmlBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Block_Widget_Container $block ; */
        $block = $observer->getBlock();

        /** @var DMP_PostNL2014_Helper_Data $helper */
        $helper = Mage::helper('dmp_postnl');


        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View) {

            $shipmentId = $block->getRequest()->getParam('shipment_id');

            $shippingMethod = Mage::getModel('sales/order_shipment')
                ->load($shipmentId)
                ->getOrder()
                ->getShippingMethod();

            if (!$helper->shippingMethodIsPostNL($shippingMethod)) {
                return;
            }

            $postNLShipment = Mage::getModel('dmp_postnl/shipment')->load($shipmentId, 'shipment_id');

            if (!$postNLShipment->hasConsignmentId()) {
                $block->addButton('postnl_create_consignment', array(
                    'label' => $helper->__('Create PostNL Consignment'),
                    'id' => 'createPostNLConsignment',
                    'class' => 'go',
                ));
                // remove Send Tracking Information button
                $block->removeButton('save');
            } else if (in_array($postNLShipment->getShipment()->getShippingAddress()->getCountry(), $helper->getReturnCountries())) {
                $mailRetournMailAction = $block->getUrl(self::RETOURMAIL_ROUTE, array('shipment_id' => $shipmentId,));
                $block->addButton('postnl_mail_return_label', array(
                    'label' => $helper->__('Mail return label'),
                    'class' => 'go',
                    'onclick' => "setLocation('" . $mailRetournMailAction . "')",
                ));

                $retourLinkAction = $block->getUrl(self::RETOURLINK_ROUTE, array('shipment_id' => $shipmentId,));
                $block->addButton('postnl_create_return_url', array(
                    'label' => $helper->__('Get retour label url'),
                    'class' => 'go',
                    'onclick' => "setLocation('" . $retourLinkAction . "')",
                ));
            }
        }
    }
}