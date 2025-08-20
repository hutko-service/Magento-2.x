<?php

declare(strict_types=1);

namespace Hutko\Hutko\Controller\Action;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

class GetRedirect extends RequestAbstract implements HttpPostActionInterface
{
    public const API_URL = 'https://pay.hutko.org/api/checkout/url/';

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $data = [
            'status' => false,
            'redirect_url' => '/checkout/cart'
        ];

        try {
            $response = $this->makeCall(self::API_URL);
            if (isset($response['response']) && isset($response['response']['checkout_url'])) {
                $data['status'] = true;
                $data['redirect_url'] = $response['response']['checkout_url'];
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
        }

        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData($data);
    }
}
