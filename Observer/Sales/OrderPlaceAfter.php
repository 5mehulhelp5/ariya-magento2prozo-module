<?php
/**
 * Copyright © Prozo (Developed BY Ariya InfoTech) All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Observer\Sales;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */

    protected $_prozohelper;
    protected $_prozoautosync;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozohelper,
        \AriyaInfoTech\ProzoInt\Model\Order\AutoSync $prozoAutosync
    ){
        $this->_prozohelper = $prozohelper;
        $this->_prozoautosync = $prozoAutosync;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ){
        try{
            $order = $observer->getEvent()->getData('order');
            $orderId = $observer->getOrder()->getId();
            $this->_prozoautosync->pushOrderDataTOProzoAccount($orderId);
            return true;
        }catch(Exception $e){
            $this->_prozohelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}