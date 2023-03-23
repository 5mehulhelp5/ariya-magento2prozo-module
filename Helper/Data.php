<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends AbstractHelper{

    const API_END_POINT = "https://proshipdev.prozo.com/";
    const CANCEL_URL = "api/channel/order/update";
    const LOGIN_URL = "api/auth/signin";
    const ORDER_CHANEL_SYNC = "api/channel/order/create";

	protected $_scopeConfig;
    protected $_productRepository;
    protected $orderFactory;
    protected $order;
    protected $_logger;
    protected $_serializer;
    protected $_timezone;

	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\Data\OrderInterface $orderinterface,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Psr\Log\LoggerInterface $logger,
        SerializerInterface $serializer,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ){
        $this->_scopeConfig = $scopeConfig;
        $this->orderinterface = $orderinterface;
        $this->order = $order;
        $this->_logger = $logger;
        $this->_productRepository = $productRepository;
        $this->_serializer = $serializer;
        $this->_timezone = $timezone;
        parent::__construct($context);
    }
    
    /*
    *get config value for enable/disable
    **/
	public function getConfigValue(){
        try{
            return $this->_scopeConfig->getValue('prozo/config_data/enable',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
	}

    /*
    *get config value for username
    **/
    public function getUsername(){
        try{
            return $this->_scopeConfig->getValue('prozo/config_data/user_name',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get config value for Payment Mode
    **/
    public function getPrepaidPaymentMethods(){
        try{
            return $this->_scopeConfig->getValue('prozo/config_data/payment_method',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get config value for Payment Mode
    **/
    public function getCodPaymentMethods(){
        try{
            return $this->_scopeConfig->getValue('prozo/config_data/payment_method_cod',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get config value for password
    **/
    public function getPassword(){
        try{
            return $this->_scopeConfig->getValue('prozo/config_data/auth_pass',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get Product By Id
    **/
    public function getProductById($id){
        try{
            return $this->_productRepository->getById($id);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get Product By SKU
    **/
    public function getProductBySku($sku){
        try{
            return $this->_productRepository->get($sku);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get Order By Increment Id
    **/
    public function getOrderByIncrementId($incrementId){
        try{
            return $this->orderinterface->loadByIncrementId($incrementId);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *get Order By Order Id
    **/
    public function getOrderDataByOrderId($orderId){
        try{
            return $this->order->load($orderId);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
    }

    /*
    *create Log
    */
    public function createprozoLog($info){
        return $this->_logger->info($info);
    }

    /*
    *Get Order SYNC Chanel URL
    */
    public function OrderSyncChanelURL(){
        $apiEndPoint = self::API_END_POINT;
        $apiChannelOrderCreate = self::ORDER_CHANEL_SYNC;
        return $apiEndPoint.$apiChannelOrderCreate;
    }

    /*
    *Get Order Cancel URL
    */
    public function OrderCancelURL(){
        $apiEndPoint = self::API_END_POINT;
        $apiChannelOrderCreate = self::CANCEL_URL;
        return $apiEndPoint.$apiChannelOrderCreate;
    }

    public function setSucessData($data){
        $response['status'] =  "SUCCESS";
        $response['statusCode'] =  "200";
        $response['message'] =  'Success';
        $response['data'] = $data;
        echo $this->setJsonEncode($response);
        exit;
    }

    public function setErrorMessage($errorMessage){
        $response['status'] =  "ERROR";
        $response['statusCode'] =  "300";
        $response['message'] = $errorMessage;
        echo $this->setJsonEncode($response);
        exit;   
    }

    public function setJsonEncode($data){
        return $this->_serializer->serialize($data);
    }

    public function setDateFormate($date){
        try{
            if($date != ''){
                return $this->_timezone->date(new \DateTime($date))->format('M d,Y H:i:s');
            }
            return true;
        }catch(\Exception $e){
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

}