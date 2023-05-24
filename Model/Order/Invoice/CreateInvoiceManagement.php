<?php
/**
 * Copyright © Developed By Ariya InfoTech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AriyaInfoTech\ProzoInt\Model\Order\Invoice;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderFactory;

class CreateInvoiceManagement implements \AriyaInfoTech\ProzoInt\Api\CreateInvoiceManagementInterface
{

    /**
     * {@inheritdoc}
     */

    protected $_request;
    protected $_prozoIntHelper;
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;

    public function __construct(
        \AriyaInfoTech\ProzoInt\Helper\Data $prozoIntHelper,
        \Magento\Framework\Webapi\Rest\Request $request,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        OrderFactory $orderFactory
    ){

        $this->_prozoIntHelper = $prozoIntHelper;
        $this->_request = $request;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->orderFactory = $orderFactory;
    }
    

    public function getCreateInvoice(){
        $errorDataMessage = array();
        $errorDataMessage['Type'] = 'Invoice Create';
        $orderId = '';
        $orderIncId = '';
        try {
            $requestData = $this->_request->getRequestData();
            $orderId = $requestData['order_id'];
            $requestDataJson = json_encode($requestData);
            $order = $this->orderRepository->get($orderId);
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->invoiceSender->send($invoice);
                $order->setStatus('processing')->setState('processing');
                $order->addCommentToStatusHistory(
                    __('Notified customer about invoice creation #%1.', $invoice->getId())
                )->setIsCustomerNotified(true)->save();
                $order->save();
                $returnData['invoice_id'] = $invoice->getId();
                $returnData['orderId'] = $order->getId();
                $returnData['orderIncrementId'] = $order->getIncrementId();
                $returnData['increment_id'] = $invoice->getIncrementId();
                $orderId = $order->getId();
                $orderIncId = $order->getIncrementId();
                $returnDatacd = json_encode($returnData);
                return $this->_prozoIntHelper->setSucessData($returnData);
            }
            $invoiceData = $this->getAllInvoice($orderId);
            return $this->_prozoIntHelper->setSucessData($invoiceData);
        }catch (\Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }

    public function getAllInvoice($orderId){
        try{
            $order = $this->orderFactory->create()->load($orderId);
            $invoiceCollection = $order->getInvoiceCollection();
            $invoiceId = '';
            $invoiceIncrementId = '';
            foreach ($invoiceCollection as $invoice) {
                $invoiceId = $invoice->getId();
                $invoiceIncrementId = $invoice->getIncrementId();
            }
            $returnData['invoice_id'] = $invoiceId;
            $returnData['increment_id'] = $invoiceIncrementId;
            return $returnData;
        }catch (\Exception $e){
            return $this->_prozoIntHelper->setErrorMessage($e->getMessage());
        }
    }

    public function invoiceCreate($orderId){
        try{
            $order = $this->orderRepository->get($orderId);
            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->invoiceSender->send($invoice);
                $order->setStatus('processing')->setState('processing');
                $order->addCommentToStatusHistory(
                    __('Notified customer about invoice creation #%1.', $invoice->getId())
                )->setIsCustomerNotified(true)->save();
                $order->save();
                $returnData['invoice_id'] = $invoice->getId();
                $returnData['orderId'] = $order->getId();
                $returnData['orderIncrementId'] = $order->getIncrementId();
                $returnData['increment_id'] = $invoice->getIncrementId();
                $orderId = $order->getId();
                $orderIncId = $order->getIncrementId();
                $returnDatacd = json_encode($returnData);
                $this->_prozoIntHelper->setSucessData($returnDatacd);
            }
            return true;
        }catch (\Exception $e){
            return true;
        }
        return true;
    }
}
