<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class Data extends AbstractHelper{

    const API_END_POINT = "https://proship.prozo.com/";
    const CANCEL_URL = "api/channel/order/update";
    const LOGIN_URL = "api/auth/signin";
    const ORDER_CHANEL_SYNC = "api/channel/order/create";
    const ORDER_UPDATE = "api/channel/order/update";
    const CONFIG_SYNC = "api/channel/config/add";
    const STORE_ID = "ariya-prozoint";
    const SHIP_CREATE_FULFILLMENT = "shipmentcreate";
    const DOMAIN_NAME = "rest/V1";
    const FULFILLMENT_STATUS_UPDATE = "/ariya-prozoint/shipmentstatusupdate";
    const CREATE_INVOICE = "ariya-prozoint/createinvoice?order_id=";
    const TOKEN_CREATE = "integration/admin/token";
    const PROZO_FILE_NAME = 'prozotoken.txt';

	protected $_scopeConfig;
    protected $_productRepository;
    protected $orderFactory;
    protected $order;
    protected $_logger;
    protected $_serializer;
    protected $_timezone;
    protected $_storeManager;
    protected $_readWrite;
    protected $_filesystem;
    protected $_directoryList;
    protected $_file;
    protected $_priceHelper;

	public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Api\Data\OrderInterface $orderinterface,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Psr\Log\LoggerInterface $logger,
        SerializerInterface $serializer,
        StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \AriyaInfoTech\ProzoInt\Model\Filesystem\ReadWrite $readWrite,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        File $file,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
    ){
        $this->_scopeConfig = $scopeConfig;
        $this->orderinterface = $orderinterface;
        $this->order = $order;
        $this->_logger = $logger;
        $this->_productRepository = $productRepository;
        $this->_serializer = $serializer;
        $this->_timezone = $timezone;
        $this->_readWrite = $readWrite;
        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_priceHelper = $priceHelper;
        parent::__construct($context);
    }
    
    /*
    *get config value for enable/disable
    **/
	public function isModuleEnable(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/enable',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
	}

    /*
    *get config value for username
    **/
    public function getUsername(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/user_name',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get config value for Payment Mode
    **/
    public function getPrepaidPaymentMethods(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/payment_method',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get config value for Payment Mode
    **/
    public function getCodPaymentMethods(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/payment_method_cod',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get config value for Payment Mode
    **/
    public function getMagentoAdminUsername(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/mage_user',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get config value for Payment Mode
    **/
    public function getMagentoAdminPassword(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/mage_pass',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get config value for password
    **/
    public function getPassword(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/auth_pass',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    public function getOrderCronDate(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/cron_date_set',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get config value for password
    **/
    public function getMerchantId(){
        try{
            return $this->_scopeConfig->getValue('prozo/general/merchant_id',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *get Base URL
    **/
    public function getBaseUrl(){
        try{
            return $this->_storeManager->getStore()->getBaseUrl();
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
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
        return true;
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
        return true;
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
        return true;
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
        return true;
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
        try{
            $apiEndPoint = self::API_END_POINT;
            $apiChannelOrderCreate = self::ORDER_CHANEL_SYNC;
            return $apiEndPoint.$apiChannelOrderCreate;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get CONFIG SYNC Chanel URL
    */
    public function configSyncToProship(){
        try{
            $apiEndPoint = self::API_END_POINT;
            $apiChannelConfigSync = self::CONFIG_SYNC;
            return $apiEndPoint.$apiChannelConfigSync;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }
    /*
    *Get Order Address Update URL
    */
    public function addressUpdateURL(){
        try{
            $apiEndPoint = self::API_END_POINT;
            $orderUpdate = self::ORDER_UPDATE;
            return $apiEndPoint.$orderUpdate;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }
    /*
    *Get Shipment Create Fullfillment URL
    */
    public function shimentCreateFullfillment(){
        try{
            $shipmentcreatefulfillment = self::SHIP_CREATE_FULFILLMENT;
            return $shipmentcreatefulfillment;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }
    /*
    *Get Store Id
    */
    public function getStoreId(){
        try{
            $store_id = self::STORE_ID;
            return $store_id;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get Domain Name
    */
    public function domainName(){
        try{
            $domainName = self::DOMAIN_NAME;
            return $this->getBaseUrl().$domainName;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get Shpment Update Fullfillment URL
    */
    public function getWebhookURL(){
        try{
            $domainName = self::DOMAIN_NAME;
            $shipmentupdate = self::FULFILLMENT_STATUS_UPDATE;
            return $this->getBaseUrl().$domainName.$shipmentupdate;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get Invoice Create URL
    */
    public function invoiceCreate(){
        try{
            $invoicecreate = self::CREATE_INVOICE;
            return $invoicecreate;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get Token Create URL
    */
    public function tokenCreate(){
        try{
            $tokencreate = self::TOKEN_CREATE;
            return $tokencreate;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get LOGIN_URL URL
    */
    public function authLoginChanelURL(){
        try{
            $apiEndPoint = self::API_END_POINT;
            $apiChannelLogin = self::LOGIN_URL;
            return $apiEndPoint.$apiChannelLogin;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    /*
    *Get Order Cancel URL
    */
    public function OrderCancelURL(){
        try{
            $apiEndPoint = self::API_END_POINT;
            $apiChannelOrderCancel = self::CANCEL_URL;
            return $apiEndPoint.$apiChannelOrderCancel;
        }catch(Exception $e){
            $this->_logger->createprozoLog($e->getMessage());
        }
        return true;
    }

    public function setSucessData($data){
        $response['status'] =  "SUCCESS";
        $response['statusCode'] =  "200";
        $response['message'] =  'Success';
        $response['data'] = $data;
        echo $this->setJsonEncode($response);
		exit();
    }

    public function setErrorMessage($errorMessage){
        $response['status'] =  "ERROR";
        $response['statusCode'] =  "300";
        $response['message'] = $errorMessage;
        echo $this->setJsonEncode($response);
		exit();
        
    }

    public function setJsonEncode($data){
        return $this->_serializer->serialize($data);
    }

    public function setJsonDecode($data){
        return $this->_serializer->unserialize($data);   
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

    /**
     * create custom folder and write text file
     *
     * @return bool
     */

    public function createAuthTokenFile($fileData){
        $varDirectory = $this->_filesystem->getDirectoryWrite(
            DirectoryList::VAR_DIR
        );
        $varPath = $this->_directoryList->getPath('var');
        $fileName = self::PROZO_FILE_NAME;
        $path = $varPath . '/prozo/' . $fileName;

        // Write Content
        $this->_readWrite->write($varDirectory, $path, $fileData);
    }

    public function getTokenReadFile(){
        try {
            $varDirectory = $this->_filesystem->getDirectoryWrite(
                DirectoryList::VAR_DIR
            );
            $varPath = $this->_directoryList->getPath('var');
            $fileName = self::PROZO_FILE_NAME;
            $path = $varPath . '/prozo/' . $fileName;
            return $this->_file->fileGetContents($path);
        }catch(FileSystemException $e) {
            $this->createprozoLog($e->getMessage());
            return false;
        }
    }
    public function getProductPrice($price){
        return $this->_priceHelper->currency($price, false, false);
    }
}