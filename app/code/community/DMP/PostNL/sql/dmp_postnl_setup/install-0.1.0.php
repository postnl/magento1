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
 * @copyright   Copyright (c) 2013 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

//add tables
$tableName = $installer->getTable('dmp_postnl/shipment');

if (!$conn->isTableExists($tableName)) {
    $tigPostnlShipmentTable = $installer->getConnection()
        ->newTable($tableName)
        /**
         * Entity ID
         */
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Entity Id')
        /**
         * Mage_Sales_Model_Order_Shipment ID
         */
        ->addColumn('shipment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
            'unsigned'  => true,
            'nullable'  => true,
        ), 'Shipment Id')
        /**
         * Mage_Sales_Model_Order ID
         */
        ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
            'unsigned'  => true,
            'nullable'  => true,
        ), 'Order Id')
        /**
         * PostNL consignment ID
         */
        ->addColumn('consignment_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 10, array(
            'unsigned'  => true,
            'nullable'  => true,
        ), 'Consignment Id')
        /**
         * Created at timestamp
         */
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'nullable'  => false,
        ), 'Created At')
        /**
         * Updated at timestamp
         */
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
            'nullable'  => false,
        ), 'Updated At')
        /**
         * the shipment's current confirm status
         */
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 64, array(
        ), 'Status')
        /**
         * the track and trace code from PostNL
         */
        ->addColumn('barcode', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Barcode')
        /**
         * Whether the shipment should only be delivered to the stated address.
         */
        ->addColumn('home_address_only', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => '0',
        ), 'Home address only')
        /**
         * Whether a signature is required on delivery.
         */
        ->addColumn('signature_on_receipt', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => '0',
        ), 'Signature on receipt')
        /**
         * Whether the shipment should be returned if no one is home.
         */
        ->addColumn('return_if_no_answer', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => '0',
        ), 'Return if no answer')
        /**
         * Whether the shipment is insured.
         */
        ->addColumn('insured', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => '0',
        ), 'Insured')
        /**
         * The amount for which the shipment is insured.
         */
        ->addColumn('insured_amount', Varien_Db_Ddl_Table::TYPE_INTEGER, 12, array(
            'unsigned' => true,
            'default'  => '0',
        ), 'Insured amount')
        /**
         * Whether the shipments is final
         */
        ->addColumn('is_final', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => 0,
        ), 'Is Final')
        /**
         *  Whether the barcode-email is send
         */
        ->addColumn('barcode_send', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => '0',
        ), 'Barcode Send')
        /**
         *  The retourlink
         */
        ->addColumn('retourlink', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Retourlink')
        /**
         *  Whether the consignment is credited
         */
        ->addColumn('is_credit', Varien_Db_Ddl_Table::TYPE_BOOLEAN, false, array(
            'unsigned' => true,
            'default'  => 0,
        ), 'Is Credit')
        ->addIndex($installer->getIdxName('dmp_postnl/shipment', array('shipment_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
            array('shipment_id'),
            array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
        ->addIndex($installer->getIdxName('dmp_postnl/shipment', array('order_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
            array('order_id'),
            array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX))
        ->addForeignKey($installer->getFkName('dmp_postnl/shipment', 'shipment_id', 'sales/shipment', 'entity_id'),
            'shipment_id', $installer->getTable('sales/shipment'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addForeignKey($installer->getFkName('dmp_postnl/shipment', 'order_id', 'sales/order', 'entity_id'),
            'order_id', $installer->getTable('sales/order'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_SET_NULL, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->setComment('DMP PostNL Shipment');

    $installer->getConnection()->createTable($tigPostnlShipmentTable);
}

$installer->endSetup();
