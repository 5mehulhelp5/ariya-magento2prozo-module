<?php

/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Auth;

class Signin
{

    protected $_prozoIntHelper;

    protected $_readWriteModel;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \AriyaInfoTech\ProzoInt\Model\Filesystem\ReadWrite $readWriteModel,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper
    ){
        $this->curl = $curl;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_readWriteModel = $readWriteModel;
    }

    public function getAuthTokenDataCreate(){
        try{
            $loginApi = $this->_prozoIntHelper->authLoginChanelURL();
            $postData = $this->getAuthPostData();
            $authHewader = $this->getAuthHttpData();
            $this->curl->setOption(CURLOPT_HEADER, 0);
            $this->curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->setHeaders($authHewader);
            $this->curl->post($loginApi, $postData); 
            $response = $this->curl->getBody();
            $responcesData = json_decode($response, true);
            if($responcesData){
                if(isset($responcesData['accessToken'])){
                    $accessToken = $responcesData['accessToken'];
                    $this->_prozoIntHelper->createAuthTokenFile($accessToken);
                    return $accessToken;
                }
            }
            return false;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return false;
        }
    }

    /**
     *  get Prozo Auth Data
     */
    public function getAuthPostData(){
        try{
            $username =  $this->_prozoIntHelper->getUsername();
            $passWord =  $this->_prozoIntHelper->getPassword();
            $autharraydata = array(
                "username"=>$username,
                "password"=>$passWord
            );
            return json_encode($autharraydata);
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return false;
        }
    }

    public function getAuthHttpData(){
        $authheaders = ["Content-Type" => "application/json"];
        return $authheaders;
    }

    public function getTokenSessionId(){
        try{
            return $this->_prozoIntHelper->getTokenReadFile();
        }catch(\Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
        }
        return true;
    }

    public function withAuthHeaderCurlData(){
        $tokenData = $this->getTokenSessionId();
        if($this->getTokenSessionId() == null){
            $tokenData = $this->getAuthTokenDataCreate();
        }
        $headers = ["Content-Type" => "application/json","Authorization"=> 'Bearer '.$tokenData];
        return $headers;
    }

    public function getPutMethodTOkenHeaderData(){
        try{
            $header_array=array();
            $tokenData = "Bearer ".$this->getTokenSessionId();
            if($this->getTokenSessionId() == null){
                $tokenData = "Bearer ".$this->getAuthTokenDataCreate();
            }
            $getTokenadmin = $tokenData;
            $tokenCreate = 'Authorization:'.$getTokenadmin;
            $contecntType = 'Content-Type: application/json';
            array_push($header_array,$tokenCreate,$contecntType);
            return $header_array;
        }catch(\Exception $e){
            return false;
        }
    }
}