<?php

namespace Hutko\Hutko\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Hutko\Hutko\Model\Ui\HutkoConfig;
use Hutko\Hutko\Model\Ui\HutkoDirectConfig;

class OrderStatus implements ObserverInterface
{
    /**
     * Set status for created order before payment was not processed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $order = $observer->getData('order');
        $method = $order->getPayment()->getMethod();
        if ($method === HutkoConfig::CODE || $method === HutkoDirectConfig::CODE) {
            $status = Order::STATE_PENDING_PAYMENT;
            $order->setState($status)->setStatus($status);
            $order->save();
        }
    }
}
