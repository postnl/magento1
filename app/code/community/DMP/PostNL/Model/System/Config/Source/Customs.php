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
 * @copyright   Copyright (c) 2014 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class DMP_PostNL_Model_System_Config_Source_Customs
{
    /**
     * Source model for customs setting.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('dmp_postnl');

        $array = array(
             array(
                'value' => 1,
                'label' => $helper->__('Commercial Goods'),
             ),
             array(
                'value' => 2,
                'label' => $helper->__('Commercial Sample'),
             ),
             array(
                'value' => 3,
                'label' => $helper->__('Documents'),
             ),
             array(
                'value' => 4,
                'label' => $helper->__('Gift'),
             ),
             array(
                'value' => 5,
                'label' => $helper->__('Returned Goods'),
             ),
        );
        return $array;
    }
}
