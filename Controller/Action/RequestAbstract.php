<?php

namespace Hutko\Hutko\Controller\Action;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Hutko\Hutko\Model\Ipsp\Validator;
use Hutko\Hutko\Model\Config\ConfigProvider;
use Psr\Log\LoggerInterface;

abstract class RequestAbstract
{
    /**
     * @var JsonFactory
     */
    protected JsonFactory $jsonFactory;

    /**
     * @var ManagerInterface
     */
    protected ManagerInterface $messageManager;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var Validator
     */
    protected Validator $signatureValidator;

    /**
     * @var ConfigProvider
     */
    protected ConfigProvider $configProvider;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param JsonFactory $jsonFactory
     * @param ManagerInterface $messageManager
     * @param CheckoutSession $checkoutSession
     * @param Validator $signatureValidator
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonFactory $jsonFactory,
        ManagerInterface $messageManager,
        CheckoutSession $checkoutSession,
        Validator $signatureValidator,
        Curl $curl,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->signatureValidator = $signatureValidator;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * Makes call to get checkout link
     *
     * @return false|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function makeCall($url)
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order->getId()) {
            $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
            $this->signatureValidator->setMethod($paymentMethodCode);
            $orderData = $this->signatureValidator->prepareOrderData($order);
            $orderData['signature'] = $this->signatureValidator->generateSignature($orderData);
            $orderData = [
                'request' => $orderData,
            ];
            $curl = $this->curl;
            $curl->setHeaders([
                'User-Agent' => 'Magento 2 CMS',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);
            $curl->post($url, json_encode($orderData, JSON_UNESCAPED_SLASHES));

            if ($response = $curl->getBody()) {
                $responseData = json_decode($response, true);
                return $responseData;
            }
        }

        return false;
    }
}
