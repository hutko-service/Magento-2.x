<?php

namespace Hutko\Hutko\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class HutkoConfig implements ConfigProviderInterface
{
    public const CODE = 'hutko';

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
