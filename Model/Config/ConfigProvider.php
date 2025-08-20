<?php

declare(strict_types=1);

namespace Hutko\Hutko\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class ConfigProvider
{
    public const HUTKO_MERCHANT_ID = 'payment/hutko/hutko_merchant_id';
    public const HUTKO_SECRET_KEY = 'payment/hutko/hutko_secret_key';
    public const HUTKO_DIRECT_MERCHANT_ID = 'payment/hutko_direct/hutko_merchant_id';
    public const HUTKO_DIRECT_SECRET_KEY = 'payment/hutko_direct/hutko_secret_key';
    public const CALLBACK_URI = 'rest/V1/hutko/callback';
    public const SUCCESS_URL = 'hutko/action/successredirect';
    public const HUTKO_STATUS = 'payment/hutko/order_status';
    public const HUTKO_DIRECT_STATUS = 'payment/hutko_direct/order_status';
    public const HUTKO_ENABLED = 'payment/hutko/active';
    public const HUTKO_DIRECT_ENABLED = 'payment/hutko_direct/active';
    public const HUTKO_DESCRIPTION = 'payment/hutko/description';

    public const HUTKO_DIRECT_DESCRIPTION = 'payment/hutko_direct/description';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * ConfigProvider constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    }

    /**
     * Is Hutko enabled
     *
     * @return bool
     */
    public function isHutkoEnabled(): bool
    {
        return (bool)$this->getConfig(self::HUTKO_ENABLED);
    }

    /**
     * Is Hutko Direct enabled
     *
     * @return bool
     */
    public function isHutkoDirectEnabled(): bool
    {
        return (bool)$this->getConfig(self::HUTKO_DIRECT_ENABLED);
    }

    /**
     * Provide Hutko description
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->getConfig(self::HUTKO_DESCRIPTION);
    }

    /**
     * Provide Hutko Direct description
     *
     * @return mixed
     */
    public function getDirectDescription()
    {
        return $this->getConfig(self::HUTKO_DIRECT_DESCRIPTION);
    }

    /**
     * Get Hutko Merchant Id
     *
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->getConfig(self::HUTKO_MERCHANT_ID);
    }

    /**
     * Get Hutko secret key
     *
     * @return false|string
     */
    public function getSecretKey() : false | string
    {
        $password = $this->getConfig(self::HUTKO_SECRET_KEY);
        if ($password) {
            return $this->encryptor->decrypt($password);
        }

        return false;
    }

    /**
     * Get Hutko Direct Merchant Id
     *
     * @return mixed
     */
    public function getDirectMerchantId() : string
    {
        return $this->getConfig(self::HUTKO_DIRECT_MERCHANT_ID);
    }

    /**
     * Get Hutko Direct secret key
     *
     * @return false|string
     */
    public function getDirectSecretKey() : false | string
    {
        $password = $this->getConfig(self::HUTKO_DIRECT_SECRET_KEY);
        if ($password) {
            return $this->encryptor->decrypt($password);
        }
        return false;
    }

    /**
     * Get callback url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCallbackUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl() . self::CALLBACK_URI;
    }

    /**
     * Get success url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSuccessUrl(): string
    {
        $store = $this->storeManager->getStore();
        return $store->getBaseUrl() . self::SUCCESS_URL;
    }

    /**
     * Get hutko order status
     *
     * @return mixed
     */
    public function getHutkoOrderStatus()
    {
        return $this->getConfig(self::HUTKO_STATUS);
    }

    /**
     * Get Hutko Direct order status
     *
     * @return mixed
     */
    public function getHutkoDirectOrderStatus()
    {
        return $this->getConfig(self::HUTKO_DIRECT_STATUS);
    }

    /**
     * Get config method
     *
     * @param $path
     * @return mixed
     */
    public function getConfig($path): mixed
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get store manager
     *
     * @return StoreManagerInterface
     */
    public function getStoreManager(): StoreManagerInterface
    {
        return $this->storeManager;
    }
}
