<?php
class TIG_PostNL2014_Block_Adminhtml_Sales_Shipment_Grid extends Mage_Adminhtml_Block_Sales_Shipment_Grid
{

    /**
     * Get consignment id's from all shipments in the order list
     *
     * @return $this
     */
    protected function _afterLoadCollection()
    {
        /**
         * @var Mage_Sales_Model_Order_Shipment $shipment
         * @var TIG_PostNL2014_Model_Shipment $postNLShipment
         */
        $shipmentIds = array();
        $consignmentIds = array();
        $postNLShipments = array();

        if ($this->getCollection()->count() == 0) {
            return $this;
        }

        foreach($this->getCollection() as $shipment)
        {
            $shipmentIds[] = $shipment->getId();
        }

        $collection = Mage::getResourceModel('tig_postnl/shipment_collection');
        $collection->getSelect();
        $collection->addFieldToFilter('shipment_id', array('in' => array($shipmentIds)));

        foreach ($collection as $postNLShipment){
            if($postNLShipment->hasConsignmentId()){
                $consignmentId = $postNLShipment->getConsignmentId();
                $consignmentIds[] = $consignmentId;
                $postNLShipments[$consignmentId] = $postNLShipment;
            }
        }


        $apiInfo    = Mage::getModel('tig_postnl/api_postNL');
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
