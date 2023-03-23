<?php
/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $_prozohelper;
	protected $_signModel;
	protected $_prozoAutosync;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\AriyaInfoTech\ProzoInt\Helper\Data $helper,
		\AriyaInfoTech\ProzoInt\Model\Auth\Signin $signModel,
		\AriyaInfoTech\ProzoInt\Model\Order\AutoSync $prozoAutosync
	){
		$this->_pageFactory = $pageFactory;
		$this->_prozohelper = $helper;
		$this->_signModel = $signModel;
		$this->_prozoAutosync = $prozoAutosync;
		return parent::__construct($context);
	}

	public function execute(){
		$this->_signModel->getAuthTokenDataCreate();
		$Username = $this->_prozohelper->getUsername();
		$AuthPass = $this->_prozohelper->getPassword();
		$this->_prozoAutosync->pushOrderDataTOProzoAccount(34);
		exit();
	}
}