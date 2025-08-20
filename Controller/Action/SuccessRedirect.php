<?php

namespace Hutko\Hutko\Controller\Action;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Hutko\Hutko\Model\Ipsp\Validator;
use Hutko\Hutko\Logger\Logger;
use Psr\Log\LoggerInterface;

class SuccessRedirect implements HttpPostActionInterface, HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RedirectFactory
     */
    protected RedirectFactory $redirectFactory;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected OrderPaymentRepositoryInterface $paymentRepository;

    /**
     * @var OrderFactory
     */
    protected OrderFactory $orderFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var Validator
     */
    protected Validator $signatureValidator;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var Logger
     */
    protected Logger $paymentLogger;

    /**
     * SuccessRedirect constructor.
     *
     * @param RedirectFactory $redirectFactory
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param OrderFactory $orderFactory
     * @param RequestInterface $request
     * @param Validator $signatureValidator
     * @param LoggerInterface $logger
     * @param Logger $paymentLogger
     */
    public function __construct(
        RedirectFactory $redirectFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        OrderFactory $orderFactory,
        RequestInterface $request,
        Validator $signatureValidator,
        LoggerInterface $logger,
        Logger $paymentLogger
    ) {
        $this->redirectFactory = $redirectFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->paymentRepository = $paymentRepository;
        $this->orderFactory = $orderFactory;
        $this->request = $request;
        $this->signatureValidator = $signatureValidator;
        $this->logger = $logger;
        $this->paymentLogger = $paymentLogger;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    /**
     * Restore checkout and customer session if order data in request is valid, redirect customer to success page.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $status = 404;
        try {
            $paymentResponse = $this->request->getParams();
            $orderId = $paymentResponse['order_id'] ?? null;

            if (!$orderId) {
                throw new \Exception('Invalid payment response');
            }

            $order = $this->orderFactory->create()->loadByIncrementId($orderId);

            if (!$order->getId()) {
                throw new \Exception('Order not found');
            }

            $payment = $order->getPayment();
            $this->signatureValidator->setMethod($payment->getMethod());
            if (is_array($paymentResponse)
                && !empty($paymentResponse)
                && isset($paymentResponse['response_status'])
                && $this->signatureValidator->validateSignature($paymentResponse)
                && $paymentResponse['response_status'] === 'success'
            ) {
                $this->checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastSuccessQuoteId($order->getQuoteId())
                    ->setLastQuoteId($order->getQuoteId());

                $customerId = $order->getCustomerId();
                if ($customerId && !$this->customerSession->isLoggedIn()) {
                    $customer = $this->customerRepository->getById($customerId);
                    $this->customerSession->setCustomerDataAsLoggedIn($customer);
                    $this->customerSession->regenerateId();
                }

                $additionalInformation = $payment->getAdditionalInformation();
                $additionalInformation['success_redirect'] = $paymentResponse;
                $payment->setAdditionalInformation($additionalInformation);
                $this->paymentRepository->save($payment);

                $resultRedirect = $this->redirectFactory->create();
                $resultRedirect->setPath('checkout/onepage/success');
                $status = 200;
                $this->paymentLogger->info("SuccessRedirect {$status} : ", $paymentResponse);
                return $resultRedirect;
            } else {
                $status = 403;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
        $this->paymentLogger->info("SuccessRedirect {$status} : ", $paymentResponse);
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setPath('/');

        return $resultRedirect;
    }
}
