<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Model;

class ConfigSync
{
    protected $_prozoIntHelper;
    protected $_signinModel;
    protected $_curl;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \AriyaInfoTech\ProzoInt\Model\Auth\Signin $signinModel
    ){
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_signinModel = $signinModel;
        $this->_curl = $curl;
    }

    public function configSyncStoreToProship(){
        try{
            $this->_prozoIntHelper->createprozoLog("==================== Start ====================");
            $subscribedEvents = array(
                "ORDER_PLACED",
                "PICKUP_PENDING",
                "OUT_FOR_DELIVERY",
                "PICKUP_PENDING",
                "PICKUP_FAILED",
                "PICKED_UP",
                "DELIVERED"
            );
            $configArray = array(
                "channelName"=>"MAGENTO",
                "merchantId"=>$this->_prozoIntHelper->getMerchantId(),
                "storeId"=>$this->_prozoIntHelper->getStoreId(),
                "webHookUrl"=>$this->_prozoIntHelper->getWebhookURL(),
                "isWebHookActive"=>true,
                "domainName"=>$this->_prozoIntHelper->domainName(),
                "fulfillmentApi"=>$this->_prozoIntHelper->shimentCreateFullfillment(),
                "invoiceCreate"=>$this->_prozoIntHelper->invoiceCreate(),
                "tokenCreate"=>$this->_prozoIntHelper->tokenCreate(),
                "subscribedEvents"=>$subscribedEvents,
                "username"=>$this->_prozoIntHelper->getMagentoAdminUsername(),
                "password"=>$this->_prozoIntHelper->getMagentoAdminPassword()
            );

            $configArrayData = json_encode($configArray); 
            $this->_prozoIntHelper->createprozoLog($configArrayData);
            $this->prozoConfigSynctoProship($configArrayData);
            return true;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
        return false;
    }
    public function prozoConfigSynctoProship($postData){
        try{
            $recount = 0;
            if($recount < 2){
                $prozoUrl = $this->_prozoIntHelper->configSyncToProship();
                $authHeader = $this->_signinModel->withAuthHeaderCurlData();
                $this->_curl->setOption(CURLOPT_HEADER, 0);
                $this->_curl->setOption(CURLOPT_TIMEOUT, 0);
                $this->_curl->setOption(CURLOPT_FOLLOWLOCATION, true);
                $this->_curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
                $this->_curl->setHeaders($authHeader);
                $this->_curl->post($prozoUrl, $postData);
                $response = $this->_curl->getBody();
                $responseData = json_decode($response, true);
                    $this->_prozoIntHelper->createprozoLog($response);
                    if(isset($responseData['statusCode'])){
                        if($responseData['statusCode'] == 401){
                            $this->_signinModel->getAuthTokenDataCreate();
                            $this->prozoConfigSynctoProship($postData);
                            $recount = 1;
                            $recount++;
                        }
                    }
                $this->_prozoIntHelper->createprozoLog($response);
            }
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}