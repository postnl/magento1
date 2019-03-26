<?php
/* @var $installer DMP_PostNL2014_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable('sales/order_grid');
if (!$conn->tableColumnExists($tableName, 'postnl_send_date')) {
    $conn->addColumn(
        $tableName,
        'postnl_send_date',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_DATE,
            'nullable' => true,
            'default'  => date('Y-m-d'),
            'comment'  => 'The day to send the parcel',
        )
    );
}
$installer->endSetup();