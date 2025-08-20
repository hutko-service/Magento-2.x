<?php

declare(strict_types=1);

namespace Hutko\Hutko\Model\Config;

use Magento\Checkout\Model\ConfigProviderInterface;
use Hutko\Hutko\Model\Config\ConfigProvider;

class CheckoutConfig implements ConfigProviderInterface
{
    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * CheckoutConfig constructor.
     *
     * @param \Hutko\Hutko\Model\Config\ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Configs for checkout FE
     *
     * @return array
     */
    public function getConfig(): array
    {
        $additionalVariables = [];
        $hutko = [
            'is_active' => $this->configProvider->isHutkoEnabled(),
            'description' => $this->configProvider->getDescription()
        ];
        $additionalVariables['hutko'] = $hutko;
        $hutkoDirect = [
            'is_active' => $this->configProvider->isHutkoDirectEnabled(),
            'description' => $this->configProvider->getDirectDescription()
        ];
        $additionalVariables['hutko_direct'] = $hutkoDirect;

        return $additionalVariables;
    }
}
