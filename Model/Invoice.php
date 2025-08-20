<?php

namespace Hutko\Hutko\Model;

use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;

class Invoice
{
    /**
     * @var InvoiceService
     */
    protected InvoiceService $invoiceService;

    /**
     * @var InvoiceSender
     */
    protected InvoiceSender $invoiceSender;

    /**
     * @var Transaction
     */
    protected Transaction $transaction;

    /**
     * Invoice constructor.
     *
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     */
    public function __construct(
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
    ) {
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
    }

    /**
     * Creates invoice for the order.
     *
     * @param $order
     * @return void
     * @throws \Exception
     */
    public function create($order): void
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();
            $this->invoiceSender->send($invoice);
            $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getIncrementId()))
                ->setIsCustomerNotified(true)
                ->save();
        }
    }
}
