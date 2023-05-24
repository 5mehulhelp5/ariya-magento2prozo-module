<?php

/**
 * Copyright © Ariya InfoTech(Yuvraj Raulji) All rights reserved.
 * See COPYING.txt for license details.
 */

namespace AriyaInfoTech\ProzoInt\Block\Adminhtml\Sales\Order\Edit;

use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

/**
 * Class OrderPushProzo
 */
class OrderPushProzo extends Container
{
    const ADMIN_RESOURCE = 'Magento_Sales::actions_edit';

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @param Context  $context
     * @param Registry $registry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Block constructor adds buttons
     *
     * @return void
     */
    protected function _construct()
    {
        $order = $this->getOrder();
        if ($order->getProzoOrderId() == null || $order->getProzoOrderId() == 0 || $order->getProzoOrderId() == 1) {
            $this->addButton(
                'order_push_prozo',
                $this->getButtonData()
            );
        }
        parent::_construct();
    }

    /**
     * Return button attributes array
     */
    public function getButtonData(): array
    {
        $message = $this->escapeJs(__('Are you sure you want to completely Order Push To ProShip ?'));

        return [
            'label'   => __('Push To ProShip'),
            'class'   => 'order-push-order primary',
            'onclick' => 'confirmSetLocation(\'' . $message . '\', \'' . $this->getOrderUrl() . '\')'
        ];
    }

    /**
     * @return string
     */
    private function getOrderUrl(): string
    {
        return $this->getUrl('ariyainfotech_prozoint/orderpush/adminorderpush', ['order_id' => $this->getOrderId()]);
    }

    /**
     * @return int|null
     */

    public function getOrder(){
      return $this->coreRegistry->registry('current_order');
    }
    private function getOrderId(){
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->coreRegistry->registry('current_order');
        if (!$order) {
            return null;
        }
        return (int)$order->getId();
    }
}