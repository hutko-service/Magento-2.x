<?php

declare(strict_types=1);

namespace Hutko\Hutko\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Hutko\Hutko\Model\Config\ConfigProvider;
use Hutko\Hutko\Model\Ui\HutkoConfig;
use Hutko\Hutko\Model\Ui\HutkoDirectConfig;
use Hutko\Hutko\Model\Invoice;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Psr\Log\LoggerInterface;

class Processor
{
    /**
     * @var OrderPaymentRepositoryInterface
     */
    private OrderPaymentRepositoryInterface $orderPaymentRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * @var Invoice
     */
    private Invoice $invoice;

    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $transactionBuilder;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Processor constructor.
     *
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ConfigProvider $configProvider
     * @param Invoice $invoice
     * @param BuilderInterface $builderInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        OrderRepositoryInterface $orderRepository,
        ConfigProvider $configProvider,
        Invoice $invoice,
        BuilderInterface $builderInterface,
        LoggerInterface $logger
    ) {
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->orderRepository = $orderRepository;
        $this->configProvider = $configProvider;
        $this->invoice = $invoice;
        $this->transactionBuilder = $builderInterface;
        $this->logger = $logger;
    }

    /**
     * Process order
     *
     * @param $order
     * @param $callBackData
     * @return void
     */
    public function process($order, $callBackData) : void
    {
        try {
            $status = false;
            $payment = $order->getPayment();
            $method = $payment->getMethodInstance()->getCode();
            $payment->setAdditionalInformation($callBackData);
            $payment->setCcLast4(substr($callBackData['masked_card'], -4));
            $payment->setCcType($callBackData['card_type']);
            $payment->setCcApproval($callBackData['order_status']);
            $this->orderPaymentRepository->save($payment);
            if ($method === HutkoConfig::CODE) {
                $status = $this->configProvider->getHutkoOrderStatus();
            } elseif ($method === HutkoDirectConfig::CODE) {
                $status = $this->configProvider->getHutkoDirectOrderStatus();
            }
            if ($status && $callBackData['order_status'] === 'approved') {
                $this->createTransaction($order, $callBackData);
                $this->invoice->create($order);
                $order->setState(Order::STATE_PAYMENT_REVIEW)->setStatus($status);
                $this->orderRepository->save($order);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * Create transaction
     *
     * @param null $order
     * @param array $paymentData
     * @return mixed
     */
    public function createTransaction($order = null, $paymentData = [])
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();

            $payment->setLastTransId($paymentData['payment_id']);
            $payment->setTransactionId($paymentData['payment_id']);
            $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $paymentData]);
            $formatedPrice = $order->getOrderCurrency()->formatTxt($order->getGrandTotal());

            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans = $this->transactionBuilder;
            $transaction = $trans->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($paymentData['payment_id'])
                ->setAdditionalInformation([Transaction::RAW_DETAILS => $paymentData])
                ->setFailSafe(true)
                //build method creates the transaction and returns the object
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

            return $transaction->save()->getTransactionId();

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }
}
