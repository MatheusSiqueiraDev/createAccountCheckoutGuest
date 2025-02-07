<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Model\CustomerFactory;
use MatheusSiqueiraDev\AccountCreationOnCheckout\Model\Config\CheckoutConfig;

class CreateCustomer implements ObserverInterface
{
    /**
     * @param CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param Session $checkoutSession
     * @param QuoteFactory $quoteFactory
     * @param CustomerFactory $customerFactoryModel
     * @param CheckoutConfig $checkoutConfig 
     */
    public function __construct(
        private readonly CustomerInterfaceFactory $customerFactory,
        private readonly AccountManagementInterface $accountManagement,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly RegionInterfaceFactory $regionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EncryptorInterface $encryptor,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly Session $checkoutSession,
        private readonly QuoteFactory $quoteFactory,
        private readonly CustomerFactory $customerFactoryModel,
        private readonly CheckoutConfig $checkoutConfig 
    ) {}

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        if(!$this->checkoutConfig->isCustomerAccountCreateCheckout()) {
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order && $this->shouldCreateCustomer($order)) {
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());
            $email = $quote->getCustomerEmail();
            $encryptedPassword = $quote->getData('encrypted_password');
            $dob = $quote->getData('dob');

            $customer = $this->getCustomerByEmail($email);
            if ($customer) {
                $this->assignOrderToCustomer($customer, $order);
                return;
            }

            if (!$encryptedPassword) {
                return;
            }

            $password = $this->encryptor->decrypt($encryptedPassword);

            try {
                $customer = $this->createCustomer(
                    email: $email,
                    firstname: $quote->getBillingAddress()->getFirstname(),
                    lastname: $quote->getBillingAddress()->getLastname(),
                    password: $password,
                    dob: $dob,
                    storeId: $quote->getStoreId()
                );

                $this->assignOrderToCustomer($customer, $order);
            } catch (\Exception $e) {
                $this->logError('Error creating customer account', $e, $email, $quote->getId());
            }
        }
    }

    /**
     * Verifica se o cliente já existe e o retorna.
     *
     * @param string $email
     * @return CustomerInterface|null
     */
    private function getCustomerByEmail(string $email): ?CustomerInterface
    {
        try {
            return $this->customerRepository->get($email);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Cria um novo cliente.
     *
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $password
     * @param string|null $dob
     * @param int $storeId
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function createCustomer(
        string $email,
        string $firstname,
        string $lastname,
        string $password,
        ?string $dob,
        int $storeId
    ): CustomerInterface {
        try {
            $customer = $this->customerFactory->create();
            $customer->setEmail($email)
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setDob($dob)
                ->setGroupId(1)
                ->setStoreId($storeId)
                ->setWebsiteId($this->storeManager->getStore($storeId)->getWebsiteId());

            return $this->accountManagement->createAccount($customer, $password);
        } catch (\Exception $e) {
            $this->logError('Failed to create client', $e, $email, 0);
            throw new LocalizedException(__('Unable to create customer account.'));
        }
    }

    /**
     * Associa o pedido ao cliente e salva o endereço.
     *
     * @param CustomerInterface $customer
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function assignOrderToCustomer(CustomerInterface $customer, $order): void
    {
        try {
            if ($order->getId()) {
                $order->setCustomerId($customer->getId())
                    ->setCustomerIsGuest(false);
                $this->orderRepository->save($order);

                // Salva o endereço de entrega
                $this->saveAddress($order->getShippingAddress(), $customer);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error associating customer with order:' . $e->getMessage(), [
                'customer_id' => $customer->getId()
            ]);
        }
    }

    /**
     * Salva o endereço do cliente.
     *
     * @param OrderAddress|null $orderAddress
     * @param CustomerInterface $customer
     * @return string|null
     */
    private function saveAddress(?OrderAddress $orderAddress, CustomerInterface $customer): ?string
    {
        if (!$orderAddress) {
            return null;
        }

        $address = $this->addressFactory->create();
        $address->setFirstname($orderAddress->getFirstname() ?? $customer->getFirstname())
            ->setLastname($orderAddress->getLastname() ?? $customer->getLastname())
            ->setCountryId($orderAddress->getCountryId() ?? '')
            ->setRegionId($orderAddress->getRegionId() ?? 0)
            ->setCity($orderAddress->getCity() ?? '')
            ->setStreet($orderAddress->getStreet() ?? [])
            ->setPostcode($orderAddress->getPostcode() ?? '')
            ->setTelephone($orderAddress->getTelephone() ?? '')
            ->setVatId($orderAddress->getVatId() ?? '')
            ->setMiddlename($orderAddress->getMiddlename() ?? '')
            ->setCustomerId($customer->getId())
            ->setIsDefaultBilling(true)
            ->setIsDefaultShipping(true);

        if ($orderAddress->getCustomAttributes()) {
            foreach ($orderAddress->getCustomAttributes() as $attributeCode => $value) {
                $address->setData($attributeCode, $value);
            }
        }

        if ($orderAddress->getRegion()) {
            $region = $this->regionFactory->create();
            $region->setRegion($orderAddress->getRegion())
                ->setRegionId($orderAddress->getRegionId())
                ->setRegionCode($orderAddress->getRegionCode());
            $address->setRegion($region);
        }

        $savedAddress = $this->addressRepository->save($address);
        return $savedAddress->getId();
    }

    /**
     * Verifica se o pedido é de um guest.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function shouldCreateCustomer($order): bool
    {
        return (bool) $order->getCustomerIsGuest();
    }

    /**
     * Log de erros.
     *
     * @param string $message
     * @param \Exception $e
     * @param string $email
     * @param int $quoteId
     * @return void
     */
    private function logError(string $message, \Exception $e, string $email, int $quoteId): void
    {
        $this->logger->error($message . ': ' . $e->getMessage(), [
            'exception' => $e,
            'email' => $email,
            'quote_id' => $quoteId
        ]);
    }
}