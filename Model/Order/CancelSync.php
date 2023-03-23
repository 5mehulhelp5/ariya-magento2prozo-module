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

    public function orderCancelProzoData($chanelOrderId,$orderId){
        try{
            $main = array(
                "channelOrderId"=>$chanelOrderId,
                "channelOrderStatus"=>"CANCELLED",
                "order_type"=>"B2C",
                "order_number"=>$orderId
            );
            $mainData = json_encode($main);
            $this->prozoCurlCancelOrderDataPush($mainData);
            return true;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }

    public function prozoCurlCancelOrderDataPush($postData){
        try{
            $prozocancelUrl = $this->_prozoIntHelper->OrderCancelURL();
            $authHeader = $this->_signinModel->withAuthHeaderCurlData();
            $this->_curl->setOption(CURLOPT_HEADER, 0);
            $this->_curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->_curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->_curl->setHeaders($authHeader);
            $this->_curl->post($prozocancelUrl, $postData);
            $response = $this->_curl->getBody();
            $this->_prozoIntHelper->createprozoLog($response);
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}