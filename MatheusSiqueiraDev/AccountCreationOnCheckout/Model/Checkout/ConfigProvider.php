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

/**
 * Class ConfigProvider
 *
 * Provides checkout configuration data, specifically password length and character requirements
 * to be used in the frontend.
 */
class ConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private ScopeConfigInterface $_scopeConfig,
        private AccountManagement $accountManagement
    ) {}

    /**
     * Retrieve the checkout configuration data.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'minimumPasswordLength' => $this->_scopeConfig->getValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH),
            'requiredCharacterClassesNumber' => $this->_scopeConfig->getValue(AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER),
        ];
    }
}
