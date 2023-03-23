<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Order\ShipmentCreate;

use Magento\Sales\Api\OrderRepositoryInterface;

class ShipmentCreateManagement implements \AriyaInfoTech\ProzoInt\Api\ShipmentCreateManagementInterface
{
    protected $_request;
    protected $_prozoIntHelper;
    protected $_orderRepository;
    protected $_convertOrder;
    protected $_trackFactory;
    protected $_shipmentNotifier;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
    ) {
        $this->_request = $request;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_convertOrder = $convertOrder;
        $this->_orderRepository = $orderRepository;
        $this->_trackFactory = $trackFactory;
        $this->_shipmentNotifier = $shipmentNotifier;
    }


    /**
     * {@inheritdoc}
     */
    public function postShipmentCreate(){
        $requestData = $this->_request->getRequestData();
        return $this->shipmentCreate($requestData);
    }

    public function shipmentCreate($requestData){
        try{
            if(isset($requestData['order_id'])){
                if($requestData['order_id'] == null){
                    return $this->_prozoIntHelper->setErrorMessage("OrderId Empty!");
                }
            }else{
                return $this->_prozoIntHelper->setErrorMessage("OrderId Empty!");
            }
            $order = $this->getOrder($requestData['order_id']);
            if(!$order){
                return $this->_prozoIntHelper->setErrorMessage("Order not found please try again");
            }
            if($order->canShip() && ($order->getStatus() == 'pending' || $order->getStatus() == 'processing')){
                $shipmentData=array();
                $shipment = $this->_convertOrder->toShipment($order);
                $shipedItemDetails = $this->getShipedItemDetails($requestData);
                $itemcount = 0;
                foreach($order->getAllVisibleItems() as $orderItem){
                    $qtyShipped = 0;
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    if (in_array($orderItem->getId(), $shipedItemDetails)){
                        if(isset($shipedItemDetails[$orderItem->getId()])){
                            $qtyShipped = $shipedItemDetails[$orderItem->getId()];
                        }
                    }
                    $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                    $shipment->addItem($shipmentItem);
                }
                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);
                $trackkingInformaction = $this->setTracKingInformaction($requestData);
                if($trackkingInformaction){
                    $shipment->addTrack($trackkingInformaction);
                }
                if(isset($requestData['prozo_ship_id'])){
                    $shipment->setProzoShipmentId($requestData['prozo_ship_id']);
                }
                $shipment->save();
                $shipment->getOrder()->save();
                $this->_shipmentNotifier->notify($shipment);
                $shipmentData['shipmentId'] = $shipment->getId();
                $shipmentData['shipmentIncrementId'] = $shipment->getIncrementId();
                $shipmentData['orderId'] = $order->getId();
                $shipmentData['orderIncrementId'] = $order->getIncrementId();
                return $this->_prozoIntHelper->setSucessData($shipmentData);
            }else{
                return $this->_prozoIntHelper->setErrorMessage("Order Status Not Pending Or Processing");
            }
        }catch(Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }

    public function setTracKingInformaction($requestData){
        try{
            $trackingURL = '';
            $number = '';
            $company = '';
            if(isset($requestData['fulfillment']['tracking_info']['url'])){
                $trackingURL = $requestData['fulfillment']['tracking_info']['url'];
            }
            if(isset($requestData['fulfillment']['tracking_info']['number'])){
                $number = $requestData['fulfillment']['tracking_info']['number'];
            }
            if(isset($requestData['fulfillment']['tracking_info']['company'])){
                $company = $requestData['fulfillment']['tracking_info']['company'];
            }
            $track = $this->_trackFactory->create();
            $track->setNumber($number);
            $track->setTitle($company);
            $track->setCarrierCode($company);
            $track->setTrackingLink($trackingURL);
            return $track;
         }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return false;
        }
    }

    public function getOrder($orderId){
        try{
            return $this->_orderRepository->get($orderId);
        }catch(Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }  

    public function getShipedItemDetails($requestData){
        try{
            if(isset($requestData['fulfillment']['line_items'])){
                $lineItemData=array();
                $shipedItemData = $requestData['fulfillment']['line_items'];
                if(is_array($shipedItemData)){
                    foreach($shipedItemData as $shippedData){
                        $lineItemData[$shippedData['product_id']] = $shippedData['fulfillable_quantity'];
                    }
                    return $lineItemData;
                }
            }
            return $this->_prozoIntHelper->setErrorMessage("Line Items empty!");
        }catch(Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }
}