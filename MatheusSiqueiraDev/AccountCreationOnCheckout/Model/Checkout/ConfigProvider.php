<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MatheusSiqueiraDev\AccountCreationOnCheckout\Model\Config\CheckoutConfig;

/**
 * Class ConfigProvider
 *
 * Provides checkout configuration data, specifically password length and character requirements
 * to be used in the frontend.
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param AccountManagement $accountManagement
     * @param CheckoutConfig $checkoutConfig 
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private AccountManagement $accountManagement,
        private readonly CheckoutConfig $checkoutConfig 
    ) {}

    /**
     * Retrieve the checkout configuration data.
     *
     * @return array
     */
    public function getConfig(): array
    {
        if(!$this->checkoutConfig->isCustomerAccountCreateCheckout()) {
            return [];
        }

        return [
            'minimumPasswordLength' => $this->scopeConfig->getValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH),
            'requiredCharacterClassesNumber' => $this->scopeConfig->getValue(AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER),
        ];
    }
}
