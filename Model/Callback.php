<?php

declare(strict_types=1);

namespace Hutko\Hutko\Model;

use Hutko\Hutko\Api\CallbackInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\App\RequestInterface;
use Hutko\Hutko\Model\Config\ConfigProvider;
use Magento\Framework\Serialize\SerializerInterface;
use Hutko\Hutko\Model\Ipsp\Validator;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Hutko\Hutko\Model\Processor;
use Hutko\Hutko\Logger\Logger;
use PHPUnit\Exception;

class Callback implements CallbackInterface
{
    /**
     * @var Response
     */
    private Response $response;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $config;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var Validator
     */
    private Validator $signatureValidator;

    /**
     * @var OrderResourceInterface
     */
    private OrderResourceInterface $orderResource;

    /**
     * @var OrderInterfaceFactory
     */
    private OrderInterfaceFactory $orderFactory;

    /**
     * @var Processor
     */
    private Processor $paymentProcessor;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * Callback constructor
     *
     * @param Response $response
     * @param RequestInterface $request
     * @param ConfigProvider $config
     * @param SerializerInterface $serializer
     * @param Validator $signatureValidator
     * @param OrderResourceInterface $orderResource
     * @param OrderInterfaceFactory $orderFactory
     * @param Processor $paymentProcessor
     * @param Logger $logger
     */
    public function __construct(
        Response $response,
        RequestInterface $request,
        ConfigProvider $config,
        SerializerInterface $serializer,
        Validator $signatureValidator,
        OrderResourceInterface $orderResource,
        OrderInterfaceFactory $orderFactory,
        Processor $paymentProcessor,
        Logger $logger
    ) {
        $this->response = $response;
        $this->request = $request;
        $this->config = $config;
        $this->serializer = $serializer;
        $this->signatureValidator = $signatureValidator;
        $this->orderResource = $orderResource;
        $this->orderFactory = $orderFactory;
        $this->paymentProcessor = $paymentProcessor;
        $this->logger = $logger;
    }

    /**
     * Process callback data
     *
     * @return int|mixed|null
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $statusCode = 404;
        $responseData = ['message' => __('Order was not found.')];
        try {
            $json = file_get_contents('php://input');
            if ($json) {
                $requestData = $this->serializer->unserialize($json);
                if (isset($requestData['order_id'])) {
                    $order = $this->orderFactory->create();
                    $this->orderResource->load($order, $requestData['order_id'], OrderInterface::INCREMENT_ID);
                    if ($order->getId()) {
                        // Compare signature from request with new generated signature.
                        $this->signatureValidator->setMethod($order->getPayment()->getMethod());
                        if ($this->signatureValidator->validateSignature($requestData)) {
                            $this->paymentProcessor->process($order, $requestData);
                            $statusCode = 200;
                            $responseData = ['message' => __('Order was processed.')];
                        } else {
                            $statusCode = 403;
                            $responseData = ['message' => __('Signature is invalid. Permission denied.')];
                        }
                    }
                }
            } else {
                $statusCode = 500;
                $responseData = ['message' => __('Invalid JSON format.')];
            }

            $this->logger->info("Callback Status {$statusCode} : json from request -> {$json}");
        } catch (\Exception $e) {
            $statusCode = 500;
            $responseData = ['message' => __('Error during order processing.')];
            $this->logger->error("Callback Status {$statusCode} : json from request -> {$json}");
        }

        return $this->response->setHeader('Content-Type', 'application/json', true)
            ->setStatusCode($statusCode)
            ->setBody($this->serializer->serialize($responseData))
            ->sendResponse();
    }
}
