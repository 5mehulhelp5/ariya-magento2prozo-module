<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Observer\Sales\Order;

class CancelAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    
    protected $_prozohelper;
    protected $_prozoCancelSync;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozohelper,
        \AriyaInfoTech\ProzoInt\Model\Order\CancelSync $prozoCancelSync
    ){
        $this->_prozohelper = $prozohelper;
        $this->_prozoCancelSync = $prozoCancelSync;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        try{
            $moduleStatus = $this->_prozohelper->isModuleEnable();
            if($moduleStatus){
                $order = $observer->getEvent()->getOrder();
                $this->_prozohelper->createprozoLog("--Order Cancelled Id".$order->getId());
                $this->_prozoCancelSync->orderCancelProzoData($order->getId());
            }
            return true;
        }catch(Exception $e){
            $this->_prozohelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}