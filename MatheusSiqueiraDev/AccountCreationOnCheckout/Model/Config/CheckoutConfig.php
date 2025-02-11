<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CheckoutConfig
{
    private const XML_PATH_CUSTOMER_ACCOUNT_CREATE_CHECKOUT = 'checkout_matheussiqueiradev/options_matheussiqueiradev/allow_customer_creation_checkout';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(readonly private ScopeConfigInterface $scopeConfig)
    {}

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isCustomerAccountCreateCheckout(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CUSTOMER_ACCOUNT_CREATE_CHECKOUT, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
