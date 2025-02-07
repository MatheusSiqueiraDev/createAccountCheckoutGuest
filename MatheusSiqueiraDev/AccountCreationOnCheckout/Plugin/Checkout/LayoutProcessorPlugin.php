<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use MatheusSiqueiraDev\AccountCreationOnCheckout\Model\Config\CheckoutConfig;

class LayoutProcessorPlugin
{
    /**
     * @param CheckoutConfig $config
     */
    public function __construct(
        private CheckoutConfig $config
    ) {
    }

    /**
     * Modifica o jsLayout removendo o componente se a configuração estiver desativada
     *
     * @param LayoutProcessor $subject
     * @param callable $proceed
     * @param array $jsLayout
     * @return array
     */
    public function aroundProcess(LayoutProcessor $subject, callable $proceed, array $jsLayout): array
    {
        $jsLayout = $proceed($jsLayout);

        if (!$this->config->isCustomerAccountCreateCheckout()) {
            unset(
                $jsLayout['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']['before-form']['children']['customer-information']
            );
        }

        return $jsLayout;
    }
}
