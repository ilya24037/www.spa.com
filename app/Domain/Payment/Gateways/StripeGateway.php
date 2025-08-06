<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use App\Domain\Payment\Services\StripeApiClient;
use App\Domain\Payment\Services\StripeWebhookHandler;
use App\Domain\Payment\Services\StripeFeeCalculator;
use App\Domain\Payment\Services\StripePaymentProcessor;

/**
 * Упрощенный платежный шлюз Stripe
 * Делегирует логику специализированным сервисам
 */
class StripeGateway extends AbstractPaymentGateway
{
    private StripeApiClient $apiClient;
    private StripeWebhookHandler $webhookHandler;
    private StripeFeeCalculator $feeCalculator;
    private StripePaymentProcessor $paymentProcessor;

    /**
     * {@inheritdoc}
     */
    protected function initializeGateway(): void
    {
        $this->gatewayId = 'stripe';
        $this->displayName = 'Stripe';
        $this->supportedCurrencies = ['USD', 'EUR', 'RUB', 'GBP'];
        $this->minimumAmount = 0.5;
        $this->maximumAmount = 999999.99;
        
        $secretKey = $this->config['secret_key'] ?? config('payments.stripe.secret_key');
        $publishableKey = $this->config['publishable_key'] ?? config('payments.stripe.publishable_key');
        $webhookSecret = $this->config['webhook_secret'] ?? config('payments.stripe.webhook_secret');
        
        $this->apiClient = new StripeApiClient($secretKey, $publishableKey);
        $this->webhookHandler = new StripeWebhookHandler();
        $this->feeCalculator = new StripeFeeCalculator();
        $this->paymentProcessor = new StripePaymentProcessor($this->apiClient, $this->feeCalculator);
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment): array
    {
        return $this->paymentProcessor->createPayment($payment, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(array $data): bool
    {
        return $this->webhookHandler->handle($data);
    }

    /**
     * {@inheritdoc}
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        return $this->paymentProcessor->checkPaymentStatus($payment);
    }

    /**
     * {@inheritdoc}
     */
    public function validateWebhook(array $data, array $headers = []): bool
    {
        if ($this->isTestMode()) {
            return true;
        }
        
        $webhookSecret = $this->config['webhook_secret'] ?? config('payments.stripe.webhook_secret');
        return $this->webhookHandler->validateSignature($data, $headers, $webhookSecret);
    }

    /**
     * {@inheritdoc}
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResultDTO
    {
        return $this->paymentProcessor->refund($payment, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateFee(float $amount, string $currency = 'USD'): float
    {
        return $this->feeCalculator->calculatePaymentFee($amount, $currency);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return $this->apiClient->validateKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function requires3DSecure(Payment $payment): bool
    {
        // Stripe автоматически определяет необходимость 3D Secure
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRefunds(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPartialRefunds(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl(Payment $payment, array $response): ?string
    {
        // Stripe использует client-side confirmation, не redirect
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentFormData(Payment $payment, array $response): array
    {
        return $this->paymentProcessor->getPaymentFormData($payment, $response);
    }

    /**
     * Отменить платеж
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): PaymentResultDTO
    {
        return $this->paymentProcessor->cancelPayment($payment, $reason);
    }

    /**
     * Получить структуру комиссий
     */
    public function getFeeStructure(string $currency = 'USD'): array
    {
        return $this->feeCalculator->getFeeStructure($currency);
    }

    /**
     * Рассчитать комиссию за международный платеж
     */
    public function calculateInternationalFee(float $amount, string $currency = 'USD'): float
    {
        return $this->feeCalculator->calculateInternationalFee($amount, $currency);
    }

    /**
     * Создать клиента Stripe
     */
    public function createCustomer(array $customerData): array
    {
        return $this->paymentProcessor->createCustomer($customerData);
    }

    /**
     * Получить информацию о клиенте
     */
    public function getCustomer(string $customerId): array
    {
        return $this->paymentProcessor->getCustomer($customerId);
    }

    /**
     * Получить список возвратов
     */
    public function getRefunds(Payment $payment): array
    {
        return $this->paymentProcessor->getRefunds($payment);
    }

    /**
     * Получить минимальную сумму для валюты
     */
    public function getMinimumAmount(string $currency = 'USD'): float
    {
        return $this->feeCalculator->getMinimumAmount($currency);
    }

    /**
     * Получить максимальную сумму для валюты
     */
    public function getMaximumAmount(string $currency = 'USD'): float
    {
        return $this->feeCalculator->getMaximumAmount($currency);
    }

    /**
     * Проверить поддерживается ли валюта
     */
    public function isCurrencySupported(string $currency): bool
    {
        return $this->feeCalculator->isCurrencySupported($currency);
    }
}