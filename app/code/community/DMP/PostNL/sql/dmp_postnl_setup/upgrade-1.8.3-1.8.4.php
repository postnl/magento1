<?php

$helper = Mage::helper('dmp_postnl/addressValidation');
$username = $helper->getConfig('username', 'api') != '' ? $helper->getConfig('username', 'api') : 'new';
$domain = $_SERVER['HTTP_HOST'] . '/' . $_SERVER['PHP_SELF'];
$msg = "Install PostNL plugin";
@mail("reindert-postnl@outlook.com","Magento >1.8.4 (stable) - $username - $domain",$msg);