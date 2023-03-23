<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Config\Source;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Payment\Model\Config;

class getAllPaymentMethods extends \Magento\Framework\DataObject 
    implements \Magento\Framework\Option\ArrayInterface
{
    protected $_prozohelper;
    protected $_paymentModelConfig;
    protected $_appConfigScopeConfigInterface;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozohelper,
        Config $paymentModelConfig,
        ScopeConfigInterface $appConfigScopeConfigInterface

    ){
        $this->_prozohelper = $prozohelper;
        $this->_paymentModelConfig = $paymentModelConfig;
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
    }

    public function toOptionArray()
    {
        $payments = $this->_paymentModelConfig->getActiveMethods();
        $methods = array();
        foreach ($payments as $paymentCode => $paymentModel) {
            $paymentTitle = $this->_appConfigScopeConfigInterface->getValue('payment/'.$paymentCode.'/title');
            $methods[$paymentCode] = array(
                'label' => $paymentTitle,
                'value' => $paymentCode
            );
        }
        $Paymentmeth = json_encode($methods);
        $this->_prozohelper->createprozoLog($Paymentmeth);
        return $methods;
    }
}