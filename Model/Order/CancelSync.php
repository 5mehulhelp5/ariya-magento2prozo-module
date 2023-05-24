<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Model\Order;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class CancelSync
{

    protected $_curl;
    protected $_prozoIntHelper;
    protected $_signinModel;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \AriyaInfoTech\ProzoInt\Model\Auth\Signin $signinModel
    ){
        $this->_curl = $curl;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_signinModel = $signinModel;
    }

    public function orderCancelProzoData($chanelOrderId){
        try{
            $marchentId = $this->_prozoIntHelper->getMerchantId();
            $main = array(
                "channelOrderId"=>$chanelOrderId,
                "merchantOId"=>$marchentId,
                "channel"=>"MAGENTO",
                "channelOrderStatus"=>"CANCELLED"
            );
            $mainData = json_encode($main);
            $this->newCurlRequestOrderCancelSync($mainData);
            return true;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }

    public function newCurlRequestOrderCancelSync($postData){
        try{
            $trycahcle = 0;
            $authHeader = $this->_signinModel->getPutMethodTOkenHeaderData();
            $trycahcle = 0;
            $prozocancelUrl = $this->_prozoIntHelper->OrderCancelURL();
            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $prozocancelUrl,
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
            $responsaearray = $this->_prozoIntHelper->setJsonDecode($response);
            $this->_prozoIntHelper->createprozoLog($response);
            if(isset($responsaearray['opertaion'])){
                if($responsaearray['opertaion'] != 'CHANNEL_ORDER_CANCEL' && $responsaearray['opertaion'] != 'SUCCESS'){
                    $this->_prozoIntHelper->createprozoLog("ST--");
                    $this->_prozoIntHelper->createprozoLog($response);
                    $this->_prozoIntHelper->createprozoLog("End--");
                }
            }
            if(isset($responsaearray['statusCode'])){
                if($responsaearray['statusCode'] == '401' || $responsaearray['statusCode'] == 401 && $trycahcle == 0){
                    $this->_signinModel->getAuthTokenDataCreate();
                    $this->newCurlRequestOrderCancelSync($postData);
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