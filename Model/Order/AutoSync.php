<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Model\Order;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class AutoSync
{
    protected $_curl;
    protected $_orderRepositoryInterface;
    protected $_orderRepository;
    protected $_orderItemRepository;
    protected $_productloader;
    protected $_collectionFactory;
    protected $_addressRenderer;
    protected $_prozoIntHelper;
    protected $_readWriteModel;
    protected $_signinModel;
    
    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Catalog\Model\ProductFactory $productloader,
        CollectionFactory $collectionFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \AriyaInfoTech\ProzoInt\Model\Filesystem\ReadWrite $readWriteModel,
        \AriyaInfoTech\ProzoInt\Model\Auth\Signin $signinModel
    ){
        $this->_curl = $curl;
        $this->_orderRepositoryInterface = $orderRepositoryInterface;
        $this->_orderRepository = $orderRepository;
        $this->_orderItemRepository = $orderItemRepository;
        $this->_productloader = $productloader;
        $this->_collectionFactory = $collectionFactory;
        $this->_addressRenderer = $addressRenderer;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_readWriteModel = $readWriteModel;
        $this->_signinModel = $signinModel;
    }

    public function pushOrderDataTOProzoAccount($orderId,$returnType = null){
        try{
            $this->_prozoIntHelper->createprozoLog("================== Start ==================");
            $this->_prozoIntHelper->createprozoLog("Order Id :- ". $orderId);
            $order = $this->_orderRepository->get($orderId);
            if($order->getStatus() == 'pending' || $order->getStatus() == 'processing'){
                $drop_pincodeshipping = $order->getShippingAddress()->getPostcode();
                $drop_pincodebilling = $order->getBillingAddress()->getPostcode();
                $billingaddress = $order->getBillingAddress();
                $shippingAddress = $order->getShippingAddress();
                $drop_streetshipping = $shippingAddress ? $shippingAddress->getStreet() : [];
                $drop_streetbilling = $billingaddress ? $billingaddress->getStreet() : [];
                $streetshipping = implode(" ", $drop_streetshipping);
                $streetbilling = implode(" ", $drop_streetbilling);
                $drop_cityshippinfaddress = $shippingAddress ? $shippingAddress->getCity() : [];
                $drop_citybillingaddress = $billingaddress ? $billingaddress->getCity() : [];
                $drop_stateshippinfaddress = $shippingAddress ? $shippingAddress->getRegion() : [];
                $drop_statebillingaddress = $billingaddress ? $billingaddress->getRegion() : [];
                $countrycodeshippinfaddress = $shippingAddress ? $shippingAddress->getCountryId() : [];
                $countrycodebillingaddress = $billingaddress ? $billingaddress->getCountryId() : [];
                $dropbillingaddress = $streetbilling.",".$drop_citybillingaddress.",".$drop_pincodebilling;
                $dropshippingaddress = $streetshipping.",".$drop_cityshippinfaddress.",".$drop_pincodeshipping;
                $totaltax = (int)$order->getBaseTaxAmount();
                if($totaltax == 0){
                    $taxesIncluded = false;
                }elseif($totaltax !== 0){
                    $taxesIncluded = true;
                }
                $paymentMethod = $order->getPayment()->getMethod();
                $prepaidpaymentMethod = $this->_prozoIntHelper->getPrepaidPaymentMethods();
                $prepaidpaymentMethods = explode(",", $prepaidpaymentMethod);
                $codPaymentMethod = $this->_prozoIntHelper->getCodPaymentMethods();
                $codPaymentMethods = explode(",", $codPaymentMethod);
                $paymentMethods = "COD";
                if(in_array($paymentMethod, $prepaidpaymentMethods)){
                    $paymentMethods = "Prepaid";
                }elseif(in_array($paymentMethod, $codPaymentMethods)){
                    $paymentMethods = "COD";
                }

                $billindaddress = array(
                    "first_name"=>$order->getCustomerFirstname(),
                    "last_name"=>$order->getCustomerLastname(),
                    "full_name"=>$order->getCustomerName(),
                    "address1"=>$dropbillingaddress,
                    "address2"=>"",
                    "phone"=>$order->getBillingAddress()->getTelephone(),
                    "city"=>$drop_citybillingaddress,
                    "state"=>$drop_statebillingaddress,
                    "province"=>$drop_statebillingaddress,
                    "zip"=>$drop_pincodebilling,
                    "country"=>$countrycodebillingaddress
                );
                $shippingaddress = array(
                    "first_name"=>$order->getCustomerFirstname(),
                    "last_name"=>$order->getCustomerLastname(),
                    "full_name"=>$order->getCustomerName(),
                    "address1"=>$dropshippingaddress,
                    "address2"=>"",
                    "phone"=>$order->getShippingAddress()->getTelephone(),
                    "city"=>$drop_cityshippinfaddress,
                    "state"=>$drop_stateshippinfaddress,
                    "province"=>$drop_stateshippinfaddress,
                    "zip"=>$drop_pincodeshipping,
                    "country"=>$countrycodeshippinfaddress
                );
                $customer = array(
                    "_id"=>(int)$order->getCustomerId(),
                    "email"=>$order->getCustomerEmail(),
                    "created_at"=>$order->getCustomerSince(),
                    "first_name"=>$order->getCustomerFirstname(),
                    "last_name"=>$order->getCustomerLastname()
                );
                $totalWeight = 0;
                $itemsordered = array();
                foreach($order->getAllVisibleItems() as $item){
                    $items = array();
                    $items['product_id'] = (int)$item->getId();
                    $items['title'] = $item->getName();
                    $items['fulfillable_quantity'] = (int)$item->getQtyOrdered();
                    $itemtax = (int)$item->getBaseTaxAmount();
                    if($itemtax == 0){
                        $taxable = false;
                    }else{
                        $taxable = true;
                    }
                    $items['taxable'] = $taxable;
                    $items['skuId'] = $item->getSku();
                    $items['units'] = (int)$item->getQtyOrdered();
                    $items['selling_price'] = (float)$item->getBasePriceInclTax();
                    $items['item_breadth'] = 20;
                    $items['weight'] = (float)$item->getWeight();
                    $items['item_length'] = 20;
                    $items['item_height'] = 20;
                    $totalWeight += $item->getWeight();
                    $itemsordered[] = $items;
                }
                $main = array(
                    "total_price"=>(int)$order->getGrandTotal(),
                    "total_weight"=>$totalWeight,
                    "channelPaymentMode"=>$paymentMethod,
                    "total_tax"=>$totaltax,
                    "cod"=>(int)$order->getBaseTotalDue(),
                    "total_discounts"=>(int)$order->getBaseDiscountAmount(),
                    "taxes_included"=>$taxesIncluded,
                    "currency"=>$order->getBaseCurrencyCode(),
                    "total_items_price"=>(int)$order->getBaseSubtotal(),
                    "checkout_id"=>(int)$order->getQuoteId(),
                    "order_number"=>(int)$order->getEntityId(),
                    "fulfillment_status"=>$order->getStatus(),
                    "financial_status"=>$order->getState(),
                    "total_shipping_price"=>$order->getBaseShippingAmount(),
                    "subtotal_price_after_discount"=>null,
                    "billing_address"=>$billindaddress,
                    "shipping_address"=>$shippingaddress,
                    "channelOrderId"=>$orderId,
                    "channel"=>"WOO_COMMERCE",
                    "channelOrderStatus"=>"CREATED",
                    "orderId"=>$order->getIncrementId(),
                    "orderDate"=>$order->getCreatedAt(),
                    "orderType"=>"B2C",
                    "paymentMode"=>$paymentMethods,
                    "customer"=>$customer,
                    "orderItems"=>$itemsordered
                );
                $maindata = json_encode($main);
                $this->_prozoIntHelper->createprozoLog($maindata);
                $returnOrderId = $this->prozoCurlDataPush($maindata);
                if($returnOrderId != null){
                    $order->setProzoOrderId($returnOrderId);
                    $order->save();
                }
                return true;
            }
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
        return false;
    }

    public function prozoCurlDataPush($postData){
        try{
            $recount = 0;
            if($recount < 2){
                $prozoUrl = $this->_prozoIntHelper->OrderSyncChanelURL();
                $authHeader = $this->_signinModel->withAuthHeaderCurlData();
                $this->_curl->setOption(CURLOPT_HEADER, 0);
                $this->_curl->setOption(CURLOPT_TIMEOUT, 0);
                $this->_curl->setOption(CURLOPT_FOLLOWLOCATION, true);
                $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->_curl->setHeaders($authHeader);
                $this->_curl->post($prozoUrl, $postData);
                $response = $this->_curl->getBody();
                $respocesData = json_decode($response, true);
                $this->_prozoIntHelper->createprozoLog($response);
                if(isset($respocesData['statusCode'])){
                    if($respocesData['statusCode'] == 401){
                        $this->_signinModel->getAuthTokenDataCreate();
                        $this->prozoCurlDataPush($postData);
                        $recount = 1;
                        $recount++;
                    }
                }
                if(isset($respocesData['orderId'])){
                    return $respocesData['orderId'];
                }
            }
            return true;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}