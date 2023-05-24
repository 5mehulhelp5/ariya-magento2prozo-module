<?php
/**
 * Copyright © JayRam All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Controller\Adminhtml\OrderPush;

class Adminorderpush extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $_prozoAutoSyncModel;
    protected $_prozoIntHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \AriyaInfoTech\ProzoInt\Model\Order\AutoSync $prozoAutoSyncModel,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_prozoAutoSyncModel = $prozoAutoSyncModel;
        $this->_prozoIntHelper = $prozoIntHelper;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view/order_id/'.$order_id);
        if($order_id != ''){
            $returnData = $this->_prozoAutoSyncModel->pushOrderDataTOProzoAccount($order_id,'rtncontol');
            if($returnData != null){
                $this->getMessageManager()->addSuccess(__('Order Push Successfully ProShip Order Id is:'.$returnData));
            }else{
                $this->getMessageManager()->addError(__('Please Try Again'));
            }
        }else{
            $this->getMessageManager()->addError(__('Order Id Not Found! Please Try Again'));
        }
        return $resultRedirect;
    }
}