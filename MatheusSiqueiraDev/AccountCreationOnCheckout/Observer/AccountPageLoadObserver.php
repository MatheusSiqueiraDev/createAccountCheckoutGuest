<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection\Interceptor;

/**
 * Observer para associar pedidos a um cliente quando ele acessar a página de "Minha Conta".
 */
class AccountPageLoadObserver implements ObserverInterface
{
    /**
     * Construtor do observer.
     *
     * @param Session $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        readonly private Session $customerSession,
        readonly private OrderRepositoryInterface $orderRepository,
        readonly private SearchCriteriaBuilder $searchCriteriaBuilder
    ) {}

    /**
     * Executa a lógica para associar pedidos ao cliente.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->customerSession->isLoggedIn()) {
            return;
        }

        $customerEmail = $this->customerSession->getCustomer()->getEmail();
        $customerId = $this->customerSession->getCustomerId();

        // Buscar pedidos do cliente com email e sem customer_id associado
        $orders = $this->getOrdersByEmailAndNullCustomerId($customerEmail);

        foreach ($orders->getItems() as $order) {
            $this->associateOrderToCustomer($order, $customerId);
        }
    }

    /**
     * Obtém os pedidos com o email do cliente e sem customer_id.
     *
     * @param string $email
     * @return Interceptor
     */
    private function getOrdersByEmailAndNullCustomerId(string $email): Interceptor
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_email', $email)
            ->addFilter('customer_id', null, 'null')  // Filtro para pedidos sem customer_id
            ->create();

        return $this->orderRepository->getList($searchCriteria);
    }

    /**
     * Associa um pedido ao cliente.
     *
     * @param OrderInterface $order
     * @param int $customerId
     * @return void
     */
    private function associateOrderToCustomer(OrderInterface $order, string $customerId): void
    {
        if (!$order->getCustomerId()) {
            $order->setCustomerId($customerId)->setCustomerIsGuest(false);
            $this->orderRepository->save($order);
        }
    }
}
