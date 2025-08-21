<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use App\Domain\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Платежный шлюз YooKassa (ЮKassa) - координатор
 * 
 * Реализация интеграции с платежной системой YooKassa
 * https://yookassa.ru/developers
 */
class YooKassaGateway implements PaymentGateway
{
    /**
     * @var string Shop ID
     */
    private string $shopId;

    /**
     * @var string Secret key
     */
    private string $secretKey;

    private YooKassaApiClient $apiClient;
    private YooKassaRequestBuilder $requestBuilder;
    private YooKassaWebhookHandler $webhookHandler;
    private YooKassaResponseProcessor $responseProcessor;
    private YooKassaFeeCalculator $feeCalculator;

    /**
     * {@inheritdoc}
     */
    protected function initializeGateway(): void
    {
        $this->gatewayId = 'yookassa';
        $this->displayName = 'ЮKassa';
        $this->supportedCurrencies = ['RUB'];
        $this->minimumAmount = 1.0;
        $this->maximumAmount = 700000.0;
        
        $this->shopId = $this->config['shop_id'] ?? config('payments.yookassa.shop_id');
        $this->secretKey = $this->config['secret_key'] ?? config('payments.yookassa.secret_key');
        
        // Инициализируем сервисы
        $this->apiClient = new YooKassaApiClient($this->shopId, $this->secretKey);
        $this->requestBuilder = new YooKassaRequestBuilder();
        $this->webhookHandler = new YooKassaWebhookHandler();
        $this->responseProcessor = new YooKassaResponseProcessor();
        $this->feeCalculator = new YooKassaFeeCalculator();
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment): array
    {
        $finalAmount = $this->getFinalAmount($payment);
        $paymentData = $this->requestBuilder->buildPaymentData($payment, $finalAmount, $this->config);
        $idempotencyKey = $this->requestBuilder->generateIdempotencyKey($payment);
        
        $apiResponse = $this->apiClient->createPayment($paymentData, $idempotencyKey);
        
        return $this->responseProcessor->processPaymentCreationResponse($apiResponse, $payment);
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
        if (!$payment->external_payment_id) {
            return $this->responseProcessor->getNotCreatedStatus();
        }

        $apiResponse = $this->apiClient->getPayment($payment->external_payment_id);
        
        return $this->responseProcessor->processPaymentStatusResponse($apiResponse);
    }

    /**
     * {@inheritdoc}
     */
    public function validateWebhook(array $data, array $headers = []): bool
    {
        return $this->webhookHandler->validateWebhook($data, $headers, $this->isTestMode());
    }

    /**
     * {@inheritdoc}
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResultDTO
    {
        if (!$payment->external_payment_id) {
            return new PaymentResultDTO(
                success: false,
                message: 'No external payment ID found'
            );
        }

        $refundAmount = $amount ?? $payment->amount;
        $refundData = $this->requestBuilder->buildRefundData($payment, $refundAmount);
        $idempotencyKey = $this->requestBuilder->generateIdempotencyKey($payment, '_refund');
        
        $apiResponse = $this->apiClient->createRefund($refundData, $idempotencyKey);
        
        return $this->responseProcessor->processRefundResponse($apiResponse, $payment, $refundAmount);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateFee(float $amount, string $currency = 'RUB'): float
    {
        return $this->feeCalculator->calculateFee($amount, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->shopId) && !empty($this->secretKey) && $this->apiClient->testConnection();
    }

    /**
     * {@inheritdoc}
     */
    public function requires3DSecure(Payment $payment): bool
    {
        // YooKassa автоматически определяет необходимость 3D Secure
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
        return $response['confirmation']['confirmation_url'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentFormData(Payment $payment, array $response): array
    {
        return [
            'confirmation_url' => $response['confirmation']['confirmation_url'] ?? null,
            'payment_id' => $response['id'] ?? null,
            'status' => $response['status'] ?? 'pending',
            'amount' => $response['amount']['value'] ?? $payment->amount,
            'currency' => $response['amount']['currency'] ?? 'RUB'
        ];
    }

    /**
     * Отменить платеж
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): PaymentResultDTO
    {
        if (!$payment->external_payment_id) {
            return new PaymentResultDTO(
                success: false,
                message: 'Внешний ID платежа не найден'
            );
        }

        $cancelData = $this->requestBuilder->buildCancelData($payment, $reason);
        $apiResponse = $this->apiClient->cancelPayment($payment->external_payment_id, $cancelData);
        
        return $this->responseProcessor->processCancelResponse($apiResponse, $payment);
    }

    /**
     * Получить структуру комиссий
     */
    public function getFeeStructure(string $currency = 'RUB'): array
    {
        return $this->feeCalculator->getFeeStructure($currency);
    }

    /**
     * Получить информацию о магазине
     */
    public function getShopInfo(): array
    {
        return $this->apiClient->getShopInfo();
    }

    /**
     * Получить список платежных методов
     */
    public function getPaymentMethods(): array
    {
        return $this->apiClient->getPaymentMethods();
    }

    /**
     * Создать webhook
     */
    public function createWebhook(string $url, array $events = ['payment.succeeded', 'payment.canceled', 'refund.succeeded']): array
    {
        $webhookData = [
            'event' => implode(',', $events),
            'url' => $url
        ];
        
        return $this->apiClient->createWebhook($webhookData);
    }

    /**
     * Получить список webhook'ов
     */
    public function getWebhooks(): array
    {
        return $this->apiClient->getWebhooks();
    }

    /**
     * Удалить webhook
     */
    public function deleteWebhook(string $webhookId): bool
    {
        return $this->apiClient->deleteWebhook($webhookId);
    }

    /**
     * Получить список возвратов по платежу
     */
    public function getRefunds(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return [];
        }

        return $this->apiClient->getRefunds($payment->external_payment_id);
    }

    /**
     * Получить информацию о возврате
     */
    public function getRefundInfo(string $refundId): array
    {
        return $this->apiClient->getRefund($refundId);
    }

    /**
     * Получить минимальную сумму для валюты
     */
    public function getMinimumAmount(string $currency = 'RUB'): float
    {
        return match($currency) {
            'RUB' => 1.0,
            'EUR' => 0.01,
            'USD' => 0.01,
            default => 1.0
        };
    }

    /**
     * Получить максимальную сумму для валюты
     */
    public function getMaximumAmount(string $currency = 'RUB'): float
    {
        return match($currency) {
            'RUB' => 700000.0,
            'EUR' => 10000.0,
            'USD' => 10000.0,
            default => 700000.0
        };
    }

    /**
     * Проверить поддерживается ли валюта
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array($currency, $this->supportedCurrencies);
    }

    /**
     * Создать периодический платеж (подписку)
     */
    public function createRecurringPayment(Payment $payment, array $recurringData): array
    {
        $paymentData = $this->requestBuilder->buildRecurringPaymentData($payment, $recurringData, $this->config);
        $idempotencyKey = $this->requestBuilder->generateIdempotencyKey($payment, '_recurring');
        
        $apiResponse = $this->apiClient->createPayment($paymentData, $idempotencyKey);
        
        return $this->responseProcessor->processPaymentCreationResponse($apiResponse, $payment);
    }

    /**
     * Получить информацию о рецидивном платеже
     */
    public function getRecurringPaymentInfo(string $paymentMethodId): array
    {
        return $this->apiClient->getPaymentMethod($paymentMethodId);
    }

    /**
     * Захолдить средства (двухстадийный платеж)
     */
    public function capturePayment(Payment $payment, ?float $amount = null): PaymentResultDTO
    {
        if (!$payment->external_payment_id) {
            return new PaymentResultDTO(
                success: false,
                message: 'Внешний ID платежа не найден'
            );
        }

        $captureAmount = $amount ?? $payment->amount;
        $captureData = $this->requestBuilder->buildCaptureData($payment, $captureAmount);
        $idempotencyKey = $this->requestBuilder->generateIdempotencyKey($payment, '_capture');
        
        $apiResponse = $this->apiClient->capturePayment($payment->external_payment_id, $captureData, $idempotencyKey);
        
        return $this->responseProcessor->processCaptureResponse($apiResponse, $payment, $captureAmount);
    }

    /**
     * Проверить статус многих платежей одним запросом
     */
    public function checkMultiplePaymentStatus(array $paymentIds): array
    {
        $results = [];
        
        foreach ($paymentIds as $paymentId) {
            try {
                $apiResponse = $this->apiClient->getPayment($paymentId);
                $results[$paymentId] = $this->responseProcessor->processPaymentStatusResponse($apiResponse);
            } catch (\Exception $e) {
                $results[$paymentId] = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Получить статистику платежей
     */
    public function getPaymentStatistics(\DateTime $from = null, \DateTime $to = null): array
    {
        $from = $from ?? new \DateTime('-30 days');
        $to = $to ?? new \DateTime();
        
        return $this->apiClient->getPaymentStatistics([
            'created_at' => [
                'gte' => $from->format('Y-m-d\TH:i:s\Z'),
                'lt' => $to->format('Y-m-d\TH:i:s\Z')
            ]
        ]);
    }

    /**
     * Получить настройки магазина
     */
    public function getShopSettings(): array
    {
        return [
            'shop_id' => $this->shopId,
            'supported_currencies' => $this->supportedCurrencies,
            'minimum_amount' => $this->minimumAmount,
            'maximum_amount' => $this->maximumAmount,
            'supports_refunds' => $this->supportsRefunds(),
            'supports_partial_refunds' => $this->supportsPartialRefunds(),
            'test_mode' => $this->isTestMode()
        ];
    }

    /**
     * Валидировать настройки подключения
     */
    public function validateConnection(): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => []
        ];

        // Проверка обязательных параметров
        if (empty($this->shopId)) {
            $result['errors'][] = 'Shop ID не указан';
        }

        if (empty($this->secretKey)) {
            $result['errors'][] = 'Secret key не указан';
        }

        // Проверка подключения к API
        if (!empty($result['errors'])) {
            return $result;
        }

        try {
            $shopInfo = $this->getShopInfo();
            
            if (isset($shopInfo['account_id'])) {
                $result['valid'] = true;
                $result['shop_info'] = $shopInfo;
                
                // Дополнительные проверки
                if ($this->isTestMode()) {
                    $result['warnings'][] = 'Режим тестирования включен';
                }
            } else {
                $result['errors'][] = 'Не удалось получить информацию о магазине';
            }
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Ошибка подключения: ' . $e->getMessage();
        }

        return $result;
    }
}