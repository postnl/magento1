<?php

    /* @var $installer DMP_PostNL_Model_Resource_Setup */
    $installer = $this;
    $installer->startSetup();
    $tableName = $installer->getTable('dmp_postnl/shipment');
    if (!$conn->tableColumnExists($tableName, 'is_xl')) {
        $conn->addColumn(
            $tableName,
            'is_xl',
            array(
                'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
                'nullable' => true,
                'default'  => null,
                'comment'  => 'Is xl consignment',
                'after'    => 'is_credit',
            )
        );
    }
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