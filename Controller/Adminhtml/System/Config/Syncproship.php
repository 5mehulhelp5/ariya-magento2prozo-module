<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Controller\Adminhtml\System\Config;

use Exception;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;


class Syncproship extends Action
{
    protected $_resultJsonFactory;
    protected $_prozoIntHelper;
    protected $_configsyncdata;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \AriyaInfoTech\ProzoInt\Model\ConfigSync $configsyncdata

    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_configsyncdata = $configsyncdata;
        return parent::__construct($context);
    }

	public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $data = $this->_configsyncdata->configSyncStoreToProship();
        $this->_prozoIntHelper->createprozoLog($data);
        return $result->setData(['success' => true]);
	}
}