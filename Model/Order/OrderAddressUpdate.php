<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Model\Order;

class OrderAddressUpdate{

    protected $_prozoIntHelper;
    protected $_orderRepository;
    protected $_signinModel;
    protected $_curl;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \AriyaInfoTech\ProzoInt\Model\Auth\Signin $signinModel
    ){
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_orderRepository = $orderRepository;
        $this->_signinModel = $signinModel;
        $this->_curl = $curl;
    }

    public function OrderCustomerAddressUpdate($orderId){
        try{
            $this->_prozoIntHelper->createprozoLog($orderId);
            $order = $this->_orderRepository->get($orderId);
            if($order){
                $billingaddress = $order->getBillingAddress();
                $drop_streetbilling = $billingaddress ? $billingaddress->getStreet() : [];
                $streetbilling = implode(" ", $drop_streetbilling);
                $drop_citybillingaddress = $billingaddress ? $billingaddress->getCity() : [];
                $drop_pincodebilling = $billingaddress->getPostcode();
                $dropbillingaddress = $streetbilling.",".$drop_citybillingaddress.",".$drop_pincodebilling;
                $drop_statebillingaddress = $billingaddress ? $billingaddress->getRegion() : [];
                $countrycodebillingaddress = $billingaddress ? $billingaddress->getCountryId() : [];
                $shippingAddress = $order->getShippingAddress();
                $drop_streetshipping = $shippingAddress ? $shippingAddress->getStreet() : [];
                $streetshipping = implode(" ", $drop_streetshipping);
                $drop_cityshippinfaddress = $shippingAddress ? $shippingAddress->getCity() : [];
                $drop_pincodeshipping = $shippingAddress->getPostcode();
                $dropshippingaddress = $streetshipping.",".$drop_cityshippinfaddress.",".$drop_pincodeshipping;
                $drop_stateshippinfaddress = $shippingAddress ? $shippingAddress->getRegion() : [];
                $countrycodeshippinfaddress = $shippingAddress ? $shippingAddress->getCountryId() : [];

                $billingAddress = array(
                    "first_name"=>$order->getCustomerFirstname(),
                    "last_name"=>$order->getCustomerLastname(),
                    "full_name"=>$order->getCustomerName(),
                    "address1"=>$dropbillingaddress,
                    "address2"=>"",
                    "phone"=>$billingaddress->getTelephone(),
                    "city"=>$drop_citybillingaddress,
                    "state"=>$drop_statebillingaddress,
                    "province"=>$drop_statebillingaddress,
                    "zip"=>$drop_pincodebilling,
                    "country"=>$countrycodebillingaddress
                );

                $shippingAddress = array(
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

                $OrderAddressUpdate = array(
                    "channelOrderId"=>$orderId,
                    "merchantOId"=>$this->_prozoIntHelper->getMerchantId(),
                    "channel"=>"MAGENTO",
                    "billing_address"=>$billingAddress,
                    "shipping_address"=>$shippingAddress
                );

                $OrderAddressUpdatedata = json_encode($OrderAddressUpdate);
                $this->_prozoIntHelper->createprozoLog($OrderAddressUpdatedata);
                $this->curlAddressUpdate($OrderAddressUpdatedata);
                return true;
            }
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
        return false;
    }
    public function curlAddressUpdate($postData){
        try{
            $trycahcle = 0;
            $authHeader = $this->_signinModel->getPutMethodTOkenHeaderData();
            $prozoAddressUpdateUrl = $this->_prozoIntHelper->addressUpdateURL();
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $prozoAddressUpdateUrl,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_SSL_VERIFYPEER => false,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'PUT',
              CURLOPT_POSTFIELDS =>$postData,
              CURLOPT_HTTPHEADER => $authHeader,
            ));
            $response = curl_exec($curl);
            $this->_prozoIntHelper->createprozoLog($response);
            $responsearray = $this->_prozoIntHelper->setJsonDecode($response);
            if(isset($responsearray['statusCode'])){
                if($responsearray['statusCode'] == '401' || $responsearray['statusCode'] == 401 && $trycahcle == 0){
                    $this->_signinModel->getAuthTokenDataCreate();
                    $this->curlAddressUpdate($postData);
                    $trycahcle++;
                }
            }
            return true;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}