<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class AutoSyncOrderCron
{

    protected $logger;
    protected $_prozoIntHelper;
    protected $_prozoModelAutoSync;
    protected $_collectionFactory;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \AriyaInfoTech\ProzoInt\Model\Order\AutoSync $prozoModelAutoSync,
        CollectionFactory $collectionFactory
    ){
        $this->logger = $logger;
        $this->_prozoModelAutoSync = $prozoModelAutoSync;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_collectionFactory = $collectionFactory;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute(){
        try{
            $orderAutoEnable = $this->_prozoIntHelper->isModuleEnable();
            if($orderAutoEnable == 1 || $orderAutoEnable == '1'){
                $orderDate = $this->_prozoIntHelper->getOrderCronDate();
                if($orderDate != ''){
                    $startDate = date("Y-m-d h:i:s",strtotime($orderDate));
                    $collection = $this->_collectionFactory->create()->addAttributeToSelect('*')->addFieldToFilter('prozo_order_id',  array('null' => true));
                    $collection->addFieldToFilter('created_at', array('gteq' => $startDate));
                    foreach($collection as $order){
                        if($order->getState() != 'holded' || $order->getStatus() == 'pending' || $order->getStatus() == 'processing'){
                            $this->_prozoModelAutoSync->pushOrderDataTOProzoAccount($order->getId());
                        }
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return true;
        }
    }
}