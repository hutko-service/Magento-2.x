<?php

declare(strict_types=1);

namespace Hutko\Hutko\Controller\Action;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

class GetEmbeddedData extends RequestAbstract implements HttpPostActionInterface
{
    public const API_URL = 'https://pay.hutko.org/api/checkout/token';

    /**
     * Execute action based on request and return result
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $data = [
            'status' => false
        ];

        try {
            $response = $this->makeCall(self::API_URL);
            if (isset($response['response']) && isset($response['response']['response_status']) && $response['response']['response_status'] === 'success') {
                $data['token'] = $response['response']['token'];
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
        }

        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData($data);
    }
}
