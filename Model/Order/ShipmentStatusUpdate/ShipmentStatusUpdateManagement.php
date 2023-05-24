<?php

/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Order\ShipmentStatusUpdate;

class ShipmentStatusUpdateManagement implements \AriyaInfoTech\ProzoInt\Api\ShipmentStatusUpdateManagementInterface
{
    protected $_request;
    protected $_prozoIntHelper;
    protected $_orderRepository;
    protected $_trackFactory;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ){
        $this->_request = $request;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_orderRepository = $orderRepository;
        $this->_trackFactory = $trackFactory;
    }

    /**
     * Shipment Status Update
     */

    public function postShipmentStatusUpdate()
    {
        try{
            $requestData = $this->_request->getRequestData();
            if(isset($requestData['reference'])){
                if($requestData['reference'] == null){
                    return $this->_prozoIntHelper->setErrorMessage("OrderId Empty!");
                }else{
                    $order = $this->getOrder($requestData['reference']);
                    if(!$order){
                        return $this->_prozoIntHelper->setErrorMessage("Order not found please try again");
                    }else{
                        $shipmentCollection = $order->getShipmentsCollection();
                        if($shipmentCollection){
                            foreach ($shipmentCollection as $shipment){
                                $shipment->setProzoStatus($requestData['remark']);
                                $shipment->save();
                                $shipmentId = $shipment->getEntityId();
                                $refNumber = $requestData['reference'];
                                $shipmentStatus = $requestData['remark'];
                                $shipmentincreid = $shipment->getIncrementId();
                                $orderid = $shipment->getOrderId();
                                $orderincrementid = $order->getIncrementId();
                                $shipmentData = array(
                                    "shipment_id"=>$shipmentId,
                                    "ref_number"=>$refNumber,
                                    "shipment_increment_id"=>$shipmentincreid,
                                    "order_id"=>$orderid,
                                    "order_increment_id"=>$orderincrementid,
                                    "shipment_status"=>$shipmentStatus
                                );
                                return $this->_prozoIntHelper->setSucessData($shipmentData);
                            }
                        }
                    }
                }
            }
        }catch(Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }

    /*
    *Get Order Data From Order Id
    */
    public function getOrder($orderId){
        try{
            return $this->_orderRepository->get($orderId);
        }catch(Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }
}