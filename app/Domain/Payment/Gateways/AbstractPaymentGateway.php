<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Базовый класс для платежных шлюзов
 * 
 * Содержит общую функциональность для всех платежных провайдеров
 */
abstract class AbstractPaymentGateway implements PaymentGateway
{
    /**
     * @var array Конфигурация шлюза
     */
    protected array $config;

    /**
     * @var bool Тестовый режим
     */
    protected bool $testMode;

    /**
     * @var string Идентификатор шлюза
     */
    protected string $gatewayId;

    /**
     * @var string Название для отображения
     */
    protected string $displayName;

    /**
     * @var array Поддерживаемые валюты
     */
    protected array $supportedCurrencies = ['RUB'];

    /**
     * @var float Минимальная сумма платежа
     */
    protected float $minimumAmount = 1.0;

    /**
     * @var float Максимальная сумма платежа
     */
    protected float $maximumAmount = 1000000.0;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->testMode = $config['test_mode'] ?? config('app.env') !== 'production';
        $this->initializeGateway();
    }

    /**
     * Инициализация шлюза
     */
    abstract protected function initializeGateway(): void;

    /**
     * {@inheritdoc}
     */
    public function process(Payment $payment): PaymentResultDTO
    {
        try {
            $response = $this->createPayment($payment);
            
            return new PaymentResultDTO(
                success: $response['success'] ?? false,
                transactionId: $response['transaction_id'] ?? null,
                message: $response['message'] ?? 'Payment initiated',
                data: $response
            );
        } catch (\Exception $e) {
            Log::error("Payment processing error in {$this->gatewayId}", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return new PaymentResultDTO(
                success: false,
                message: 'Payment processing failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function confirm(Payment $payment, array $data): PaymentResultDTO
    {
        try {
            $status = $this->checkPaymentStatus($payment);
            
            if ($status['paid'] ?? false) {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'external_payment_id' => $status['transaction_id'] ?? null
                ]);
                
                return new PaymentResultDTO(
                    success: true,
                    transactionId: $status['transaction_id'] ?? null,
                    message: 'Payment confirmed'
                );
            }
            
            return new PaymentResultDTO(
                success: false,
                message: 'Payment not confirmed'
            );
        } catch (\Exception $e) {
            Log::error("Payment confirmation error in {$this->gatewayId}", [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return new PaymentResultDTO(
                success: false,
                message: 'Payment confirmation failed'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(Payment $payment, ?string $reason = null): PaymentResultDTO
    {
        try {
            $payment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'cancellation_reason' => $reason
                ])
            ]);
            
            return new PaymentResultDTO(
                success: true,
                message: 'Payment cancelled'
            );
        } catch (\Exception $e) {
            return new PaymentResultDTO(
                success: false,
                message: 'Failed to cancel payment'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(Payment $payment): string
    {
        $status = $this->checkPaymentStatus($payment);
        return $status['status'] ?? 'unknown';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supportedCurrencies);
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumAmount(): float
    {
        return $this->minimumAmount;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximumAmount(): float
    {
        return $this->maximumAmount;
    }

    /**
     * {@inheritdoc}
     */
    public function getGatewayId(): string
    {
        return $this->gatewayId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
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
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * {@inheritdoc}
     */
    public function requires3DSecure(Payment $payment): bool
    {
        return $payment->amount >= 1000; // По умолчанию для сумм больше 1000
    }

    /**
     * {@inheritdoc}
     */
    public function calculateFee(float $amount, string $currency = 'RUB'): float
    {
        // По умолчанию 2.5% комиссия
        return round($amount * 0.025, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUrl(Payment $payment, array $response): ?string
    {
        return $response['redirect_url'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentFormData(Payment $payment, array $response): array
    {
        return $response['form_data'] ?? [];
    }

    /**
     * Логировать запрос к API
     */
    protected function logApiRequest(string $method, string $url, array $data = []): void
    {
        if (config('payments.log_requests', false)) {
            Log::channel('payments')->info("API Request to {$this->gatewayId}", [
                'method' => $method,
                'url' => $url,
                'data' => $this->sanitizeLogData($data)
            ]);
        }
    }

    /**
     * Логировать ответ от API
     */
    protected function logApiResponse(string $url, $response): void
    {
        if (config('payments.log_responses', false)) {
            Log::channel('payments')->info("API Response from {$this->gatewayId}", [
                'url' => $url,
                'response' => $this->sanitizeLogData($response)
            ]);
        }
    }

    /**
     * Очистить чувствительные данные для логов
     */
    protected function sanitizeLogData($data): array
    {
        if (!is_array($data)) {
            return ['data' => '***'];
        }
        
        $sensitive = ['card_number', 'cvv', 'password', 'secret', 'token'];
        
        foreach ($sensitive as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***';
            }
        }
        
        return $data;
    }

    /**
     * Выполнить HTTP запрос с логированием
     */
    protected function makeHttpRequest(string $method, string $url, array $options = []): \Illuminate\Http\Client\Response
    {
        $this->logApiRequest($method, $url, $options);
        
        $response = Http::$method($url, $options);
        
        $this->logApiResponse($url, $response->json());
        
        return $response;
    }

    /**
     * Получить конечную сумму платежа из метаданных или основную сумму
     */
    protected function getFinalAmount(Payment $payment): float
    {
        return $payment->metadata['final_amount'] ?? $payment->amount;
    }

    /**
     * Сгенерировать уникальный идентификатор для идемпотентности
     */
    protected function generateIdempotencyKey(Payment $payment): string
    {
        return md5($this->gatewayId . $payment->payment_id . $payment->created_at);
    }
}