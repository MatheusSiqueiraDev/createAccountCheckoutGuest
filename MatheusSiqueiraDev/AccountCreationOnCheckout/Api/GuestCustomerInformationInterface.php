<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Api;

interface GuestCustomerInformationInterface
{
    /**
     * Save guest information customer in quote
     *
     * @param string $cartId
     * @param string $password
     * @param string $dob
     * @return bool
     */
    public function saveInformation(string $cartId, string $password, string $dob): bool;
}
