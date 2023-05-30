<?php

namespace Snapcommerce\Snapmint\Controller\Payment;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Controller\ResultFactory;

class Webhook extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Sales\Model\OrderFactory $_order **/
    protected $_order;

    /** @var \Psr\Log\LoggerInterface $_logger **/
    protected $_logger;

    /**
     * @var \Snapcommerce\Snapmint\Helper\Data $_helper
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_stockManagement
     */
    protected $_stockManagement;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $_stockIndexerProcessor
     */
    protected $_stockIndexerProcessor;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

     /**
      * @param \Magento\Framework\App\Action\Context $context
      * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
      * @param \Psr\Log\LoggerInterface $logger
      * @param \Magento\Sales\Model\OrderFactory $order
      * @param \Snapcommerce\Snapmint\Helper\Data $helper
      * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
      * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
      * @param \Magento\Framework\Event\ManagerInterface $eventManager
      */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $order,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {

        $this->_logger                  = $logger;
        $this->_helper                  = $helper;
        $this->_order                   = $order;
        $this->_scopeConfig             = $scopeConfig;
        $this->_resultJsonFactory       = $resultJsonFactory;
        $this->_eventManager            = $eventManager;
        $this->_quoteRepository         = $quoteRepository;
        return parent::__construct($context);
    }

    /**
     * Create Order
     */

    public function execute()
    {
        $this->_logger->info("In Snapmint Webhook.");

        $result = $this->_resultJsonFactory->create();

        $response = [];
        $response['order_placed'] = 0;

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isEnabled = $this->_scopeConfig->getValue('payment/snapmint/enable_webhook', $storeScope);

        if ($isEnabled) {

            $this->_logger->info("Snapmint Webhook Started.");
            
            $content = $this->getRequest()->getContent();

            $post = (array) json_decode($content);

            $this->_logger->info('Webhook post data');
            $this->_logger->info(print_r($post, true));//phpcs:ignore

            if (!isset($post['status'])) {
                $post = $this->getRequest()->getPostValue();
            }
 
            $order_id = $post['order_id'];

            if (isset($post['status']) && ($post['status'] == 'Success') || ($post['status'] == 'success')) {

                $orderid                    = $post['order_id'];
                $status                     = $post['status'] ;
                $postdata_hash              = $post['checksum_hash'];
                $email                      = $post['email'];
                $mobile                     = $post['mobile'];
                $order_value                = $post['order_value'];
                $fullname                   = $post['full_name'];
                
                $this->_logger->info("Order Webhook Processing for ". $order_id);

                //check if order exists
                $order = $this->_order->create()->loadByIncrementId($order_id);
                //  echo $order->getIncrementId();exit;
                if ($order->getId()) {
                    $response = $this->_helper->moveOrderToProcessing($order, $post);
                } else {

                    $merchant_key = $this->_helper->getConfig('payment/snapmint/merchantkey');
                    $merchant_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');

                    $redirectchecksum = hash('sha512', $merchant_key.'|'.$orderid.'|'.$order_value.'|'.$fullname.'|'.$email.'|'.$merchant_token, false); //phpcs:ignore 

                    $snapmintOrderLink = $this->_objectManager->get(\Snapcommerce\Snapmint\Model\SnapmintOrder::class)
                                                    ->getCollection()
                                                    ->addFilter('snapmint_order_id', $orderid)
                                                    ->addFilter('snapmint_signature', $redirectchecksum);
                    
                    $snapmintOrderLink = $snapmintOrderLink->getFirstItem();

                    if (!empty($snapmintOrderLink->getId())) {
                        $quote_id = $snapmintOrderLink->getQuoteId();

                        $quote = $this->_quoteRepository->get($quote_id);

                        if (!$quote->getIsActive()) {

                            $message = 'Quote is inactive for quoteID:'. $quote_id;

                            $response['message'] = $message;
                            $response['order_placed'] = 0;

                            $this->_logger->error($message);

                            return $response;
                        }

                        //skip order update and creatin on  grand total mismatch
                        $quote_grandtotal = round($quote->getGrandTotal(), 2);
                        $order_value = round($order_value, 2);

                        $this->_logger->info('---quote_grandtotal---'. $quote_grandtotal);
                        $this->_logger->info('---order_value---'. $order_value);
                        
                        if ($quote_grandtotal != $order_value) {
                            $this->_logger->info('---Cart Total Mismatch---');
                            $response['order_placed'] = 0;
                            $response['message'] = 'Snapmint Order : Cart Total Mismatch issue';
                            return $result->setData($response);
                        }

                        $placeOrder = $this->_helper->placeOrder($post, 'by_webhook', $snapmintOrderLink);
                        $order = $placeOrder['order'];
                        $response['order_placed'] = $placeOrder['order_placed'];
                        if (!$quote->getIsActive()) {
                            //set cart to active because webhook is getting hit first everytime
                            //$quote->setIsActive(true);
                            //$quote->save();
                        }

                        $this->_eventManager->dispatch(
                            'snapmint_quote_submit_success',
                            [
                                'order' => $order,
                                'quote' => $quote
                            ]
                        );
                    } else {
                        $this->_logger->info('---Signature Mismatch---');
                        $this->_logger->info('---Spostdata_hash---'. $postdata_hash);
                        $this->_logger->info('---email---'. $email);
                        $this->_logger->info('---mobile---'. $mobile);
                        $this->_logger->info('---order Id---'. $orderid);
                    }
                }
            } else {
                $message = 'Snapmint Order : '.$order_id .' Status : '. $post['status'];
                $response['order_placed'] = 0;
                $response['message'] = $message;
                $this->_logger->info($message);
            }
        } else {
            $response['order_placed'] = 0;
            $message = 'Snapmint Order : Webhook Not Enabled';
            $response['message'] = $message;
            $this->_logger->info($message);
        }

        if ($response['order_placed'] == 0) {
            $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR);
        }
        $this->_logger->info("In Snapmint Webhook End.");

        return $result->setData($response);
    }
}
