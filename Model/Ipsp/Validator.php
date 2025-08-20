<?php

namespace Hutko\Hutko\Model\Ipsp;

use Hutko\Hutko\Model\Ipsp\Signature;
use Hutko\Hutko\Model\Config\ConfigProvider;

class Validator
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $config;

    /**
     * @var string
     */
    private string $merchantId;

    /**
     * @var string
     */
    private string $secretKey;

    /**
     * Validator constructor
     *
     * @param ConfigProvider $config
     */
    public function __construct(ConfigProvider $config)
    {
        $this->config = $config;
    }

    /**
     * Set method to assign credentials
     *
     * @param $method
     * @return void
     */
    public function setMethod($method)
    {
        if ($method === 'hutko' || $method === 'hutko_direct') {
            if ($method === 'hutko') {
                $this->merchantId = $this->config->getMerchantId();
                $this->secretKey = $this->config->getSecretKey();
            } else {
                $this->merchantId = $this->config->getDirectMerchantId();
                $this->secretKey = $this->config->getDirectSecretKey();
            }
            Signature::merchant($this->merchantId);
            Signature::password($this->secretKey);
        }
    }

    /**
     * Generate signature entry point
     *
     * @param array $data
     * @return string
     */
    public function generateSignature(array $data): string
    {
        return Signature::generate($data);
    }

    /**
     * Validate signature entry point
     *
     * @param array $response
     * @return bool
     */
    public function validateSignature(array $response): bool
    {
        return Signature::check($response);
    }

    /**
     * Prepare array with order data
     *
     * @param $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareOrderData($order)
    {
        $orderItemNames = [];
        foreach ($order->getItems() as $item) {
            $orderItemNames[] = $item->getName();
        }

        if ($order->getPayment()->getMethodInstance()->getCode() === 'hutko_direct') {
            return [
                "server_callback_url" => $this->config->getCallbackUrl(),
                "order_id" => $order->getIncrementId(),
                "currency" => $order->getOrderCurrencyCode(),
                "merchant_id" => $this->merchantId,
                "order_desc" => implode(", ", $orderItemNames),
                "lifetime" => 999999,
                "amount" => (int)round($order->getGrandTotal() * 100)
            ];
        }

        return [
            "order_id" => $order->getIncrementId(),
            "order_desc" => implode(", ", $orderItemNames),
            "currency" => $order->getOrderCurrencyCode(),
            "amount" => (int)round($order->getGrandTotal() * 100),
            "merchant_id" => $this->merchantId,
            'server_callback_url' => $this->config->getCallbackUrl(),
            'response_url' => $this->config->getSuccessUrl()
        ];
    }
}
