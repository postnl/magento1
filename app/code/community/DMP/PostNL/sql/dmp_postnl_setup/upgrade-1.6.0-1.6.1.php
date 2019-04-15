<?php
    $tableName = $installer->getTable('sales/quote');
    if (!$conn->tableColumnExists($tableName, 'postnl_data')) {
        $conn->addColumn(
            $tableName,
            'postnl_data',
            array(
                'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Checkout PostNL data',
            )
        );
    }
    $tableName = $installer->getTable('sales/order');
    if (!$conn->tableColumnExists($tableName, 'postnl_data')) {
        $conn->addColumn(
            $tableName,
            'postnl_data',
            array(
                'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Checkout PostNL data',
            )
        );
    }
    $installer->endSetup();