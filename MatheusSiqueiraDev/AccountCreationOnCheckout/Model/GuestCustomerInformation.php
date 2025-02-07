<?php
/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MatheusSiqueiraDev\AccountCreationOnCheckout\Model;

use MatheusSiqueiraDev\AccountCreationOnCheckout\Api\GuestCustomerInformationInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

/**
 * Classe responsável por salvar as informações do cliente convidado durante o checkout.
 */
class GuestCustomerInformation implements GuestCustomerInformationInterface
{
    /**
     * Construtor da classe.
     *
     * @param CartRepositoryInterface $quoteRepository Repositório de cotações (quotes).
     * @param EncryptorInterface $encryptor Serviço de criptografia.
     * @param LoggerInterface $logger Logger para registrar erros.
     * @param QuoteIdMaskFactory $quoteIdMaskFactory Fábrica para recuperar a relação entre maskedId e quoteId.
     * @param DateTimeFactory $dateTimeFactory 
     */
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly EncryptorInterface $encryptor,
        private readonly LoggerInterface $logger,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly DateTimeFactory $dateTimeFactory //
    ) {}

    /**
     * Salva as informações de um cliente convidado associada a um carrinho.
     *
     * @param string $cartId Identificador mascarado do carrinho (maskedId).
     * @param string $password Senha que será salva.
     * @param string $dob Data de Nascimento que será salva.
     * @return bool Retorna true em caso de sucesso.
     * @throws LocalizedException Se houver erro ao salvar as informações.
     */
    public function saveInformation(string $cartId, string $password, string $dob): bool
    {
        try {
            $quote = $this->getQuoteByMaskedId($cartId);

            $this->updatePassword($quote, $password);

            $formattedDob = $this->formatDate($dob);
            $this->updateDob($quote, $formattedDob);

            $this->quoteRepository->save($quote);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error saving guest informations: ' . $e->getMessage());
            throw new LocalizedException(__('An error occurred while saving the information.'));
        }
    }

    /**
     * Obtém a cotação (quote) ativa com base no ID mascarado do carrinho.
     *
     * @param string $maskedId Identificador mascarado do carrinho.
     * @return CartInterface Retorna a cotação ativa.
     * @throws LocalizedException Se o carrinho não for encontrado ou for inválido.
     */
    private function getQuoteByMaskedId(string $maskedId): CartInterface
    {
        $quoteId = $this->quoteIdMaskFactory->create()->load($maskedId, 'masked_id')->getQuoteId();
        
        if (!$quoteId) {
            throw new LocalizedException(__('Invalid cart ID.'));
        }

        $quote = $this->quoteRepository->getActive($quoteId);
        
        if (!$quote->getId()) {
            throw new LocalizedException(__('The quote was not found.'));
        }

        return $quote;
    }

    /**
     * Atualiza e salva a senha criptografada no carrinho.
     *
     * @param CartInterface $quote Cotação (quote) onde a senha será armazenada.
     * @param string $password Senha em texto plano para ser criptografada.
     * @return void
     */
    private function updatePassword(CartInterface $quote, string $password): void
    {
        $encryptedPassword  = $this->encryptor->encrypt($password);
        $quote->setData('encrypted_password', $encryptedPassword);
    }

    /**
     * Atualiza e salva a data do usuário na quote
     *
     * @param CartInterface $quote Cotação (quote) onde a senha será armazenada.
     * @param string $password Data de nascimento
     * @return void
     */
    private function updateDob(CartInterface $quote, string $dob): void
    {
        $quote->setData('dob', $dob);
    }

    /**
     * Converte a data recebida para o formato correto (YYYY-MM-DD)
     *
     * @param string $dob
     * @return string|null Retorna a data formatada ou null se for inválida.
     */
    private function formatDate(string $dob): ?string
    {
    try {
        $dateTime = \DateTime::createFromFormat('d/m/Y', $dob);

        // Verificar se a data foi criada corretamente
        if (!$dateTime) {
            return null; // Caso a data seja inválida
        }
        
        // Usando o método format() para formatar a data
        return $dateTime->format('Y-m-d');        
    } catch (\Exception $e) {
        $this->logger->error('Error formatting date: ' . $e->getMessage());
        return null;
    }
}
}
