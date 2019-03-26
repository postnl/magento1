<?php
class DMP_PostNL2014_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{

    /**
     * Get consignment id's from all shipments in the order list
     *
     * @return $this
     */
    protected function _afterLoadCollection()
    {
        /**
         * @var Mage_Sales_Model_Order $order
         * @var DMP_PostNL2014_Model_Shipment $postNLShipment
         */
        $consignmentIds = array();
        $postNLShipments = array();

        $collection = Mage::getResourceModel('dmp_postnl/shipment_collection');
        $collection->getSelect();
        if ($this->getCollection()->getAllIds())
            $collection->addFieldToFilter('order_id', array('in' => array($this->getCollection()->getAllIds())));

        foreach ($collection as $postNLShipment){
            if($postNLShipment->hasConsignmentId()){
                $consignmentId = $postNLShipment->getConsignmentId();
                $consignmentIds[] = $consignmentId;
                $postNLShipments[$consignmentId] = $postNLShipment;
            }
        }

        $apiInfo    = Mage::getModel('dmp_postnl/api_postNL');
        $responseShipments = $apiInfo->getConsignmentsInfoData($consignmentIds);

        if($responseShipments){
            foreach($responseShipments as $responseShipment){
                $postNLShipment = $postNLShipments[$responseShipment->id];
                $postNLShipment->updateStatus($responseShipment);
            }
        }

        return $this;
    }
}
