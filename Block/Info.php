<?php

declare(strict_types=1);

namespace Hutko\Hutko\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Hutko\Hutko\Model\Ui\HutkoConfig;
use Hutko\Hutko\Model\Ui\HutkoDirectConfig;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field): Phrase
    {
        return __($field);
    }

    /**
     * Prepare payment info
     *
     * @param $transport
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        /**
         * @var \Magento\Sales\Model\Order\Payment\Interceptor
         */
        $info = $this->getInfo();

        $displayData = [];
        $frontDisplayData = [];

        $additionalInformation = $info->getAdditionalInformation();
        if (isset($additionalInformation['raw_details_info'])) {

            $pageData = $additionalInformation['raw_details_info'];

            if (isset($pageData['payment_id'])) {
                $label = __('Payment Id')->getText();
                $displayData[$label] = $pageData['payment_id'];
                $frontDisplayData[$label] = $pageData['payment_id'];
            }

            if (isset($pageData['order_id'])) {
                $label = __('Order Id')->getText();
                $displayData[$label] = $pageData['order_id'];
            }

            if (isset($pageData['order_status'])) {
                $label = __('Status')->getText();
                $displayData[$label] = $pageData['order_status'];
                $frontDisplayData[$label] = $pageData['order_status'];
            }

            if (isset($pageData['rrn'])) {
                $displayData['RRN'] = $pageData['rrn'];
            }

            if (isset($pageData['response_status'])) {
                $label = __('Response status')->getText();
                $displayData[$label] = $pageData['response_status'];
            }

            if (isset($pageData['masked_card'])) {
                $displayData['Card number'] = $pageData['masked_card'];
                $frontDisplayData['Card number'] = $pageData['masked_card'];
            }

            if (isset($pageData['order_time'])) {
                $label = __('Transaction time')->getText();
                $displayData[$label] = $pageData['order_time'];
                $frontDisplayData[$label] = $pageData['order_time'];
            }
        }

        if ($this->getArea() != 'adminhtml') {
            return $transport->setData($frontDisplayData);
        }

        return $transport->setData($displayData);
    }
}
