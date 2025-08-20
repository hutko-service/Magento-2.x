<?php

namespace Hutko\Hutko\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class HutkoDirectConfig implements ConfigProviderInterface
{
    public const CODE = 'hutko_direct';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [];
    }
}
