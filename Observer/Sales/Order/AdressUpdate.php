<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Observer\Sales\Order;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AdressUpdate implements ObserverInterface
{
    protected $_prozoIntHelper;
    protected $_orderAddressUpdate;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \AriyaInfoTech\ProzoInt\Model\Order\OrderAddressUpdate $orderAddressUpdate
    ){
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_orderAddressUpdate = $orderAddressUpdate;
    }
 
    public function execute(EventObserver $observer)
    {
        try{
            $order_id = $observer->getOrderId();
            $this->_orderAddressUpdate->OrderCustomerAddressUpdate($order_id);
            return true;
        }catch(Exception $e){
            $this->_prozoIntHelper->createprozoLog($e->getMessage());
            return true;
        }
    }
}