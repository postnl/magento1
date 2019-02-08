<?php
/* @var $installer TIG_PostNL2014_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();
$attribute  = array(
    'type'                       => 'int',
    'label'                      => 'Show PostNL options',
    'input'                      => 'select',
    'source'                     => 'catalog/product_status',
    'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
    'visible'                    =>  true,
    'user_defined'               =>  true,
    'searchable'                 => true,
    'used_in_product_listing'    => true,
    'required'                   => false,
    'default'                    => 1,
    'group'                      => "General"
);
$installer->addAttribute('catalog_product', 'show_postnl_options', $attribute);
$installer->endSetup();