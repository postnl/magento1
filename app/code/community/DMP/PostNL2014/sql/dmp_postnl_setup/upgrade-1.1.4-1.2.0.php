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

/* @var $installer DMP_PostNL2014_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tableName = $installer->getTable('dmp_postnl/shipment');

if (!$conn->tableColumnExists($tableName, 'shipment_type')) {
    $conn->addColumn(
        $tableName,
        'shipment_type',
        array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'   => '16',
            'nullable' => false,
            'default'  => DMP_PostNL2014_Model_Shipment::TYPE_NORMAL,
            'comment'  => 'Shipment Type',
            'after'    => 'updated_at',
        )
    );
}

$settingsToMove = array(
    'carriers/dmp_postnl/active'              => 'carriers/postnl/active',
    'carriers/dmp_postnl/title'               => 'carriers/postnl/title',
    'carriers/dmp_postnl/name'                => 'carriers/postnl/name',
    'carriers/dmp_postnl/rate_type'           => 'carriers/postnl/rate_type',
    'carriers/dmp_postnl/type'                => 'carriers/postnl/type',
    'carriers/dmp_postnl/price'               => 'carriers/postnl/price',
    'carriers/dmp_postnl/condition_name'      => 'carriers/postnl/condition_name',
    'carriers/dmp_postnl/handling_type'       => 'carriers/postnl/handling_type',
    'carriers/dmp_postnl/handling_fee'        => 'carriers/postnl/handling_fee',
    'carriers/dmp_postnl/pakjegemak_active'   => 'carriers/postnl/pakjegemak_active',
    'carriers/dmp_postnl/pakjegemak_fee'      => 'carriers/postnl/pakjegemak_fee',
    'carriers/dmp_postnl/sallowspecific'      => 'carriers/postnl/sallowspecific',
    'carriers/dmp_postnl/specificcountry'     => 'carriers/postnl/specificcountry',
    'carriers/dmp_postnl/sort_order'          => 'carriers/postnl/sort_order',
);

foreach ($settingsToMove as $from => $to) {
    $installer->moveConfigSettingInDb($from, $to);
}

$installer->endSetup();
