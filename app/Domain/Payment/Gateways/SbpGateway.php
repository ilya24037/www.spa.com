<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use App\Domain\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Log;

/**
 * Платежный шлюз СБП (Система Быстрых Платежей)
 * 
 * Реализация интеграции с Системой Быстрых Платежей через банк-партнер
 * https://sbp.nspk.ru/
 */
class SbpGateway implements PaymentGateway
{
    /**
     * @var string Bank ID партнер банка
     */
    private string $bankId;

    /**
     * @var string Merchant ID
     */
    private string $merchantId;

    /**
     * @var string Secret key для подписи
     */
    private string $secretKey;

    private SbpApiClient $apiClient;
    private SbpQrGenerator $qrGenerator;
    private SbpWebhookHandler $webhookHandler;
    private SbpFeeCalculator $feeCalculator;
    
    /**
     * @var array Configuration
     */
    private array $config = [];
    
    /**
     * @var float Minimum amount
     */
    protected float $minimumAmount = 0.01;
    
    /**
     * @var float Maximum amount
     */
    protected float $maximumAmount = 2000000.0;
    
    /**
     * @var array Supported currencies
     */
    protected array $supportedCurrencies = ['RUB'];
    
    /**
     * @var string Gateway ID
     */
    protected string $gatewayId = 'sbp';
    
    /**
     * @var string Display name
     */
    protected string $displayName = 'Система Быстрых Платежей';

    /**
     * {@inheritdoc}
     */
    protected function initializeGateway(): void
    {
        $this->gatewayId = 'sbp';
        $this->displayName = 'Система Быстрых Платежей';
        $this->supportedCurrencies = ['RUB'];
        $this->minimumAmount = 0.01;
        $this->maximumAmount = 2000000.0; // 2 млн рублей лимит СБП
        
        $this->bankId = $this->config['bank_id'] ?? config('payments.sbp.bank_id');
        $this->merchantId = $this->config['merchant_id'] ?? config('payments.sbp.merchant_id');
        $this->secretKey = $this->config['secret_key'] ?? config('payments.sbp.secret_key');
        
        // Инициализируем сервисы
        $this->apiClient = new SbpApiClient($this->bankId, $this->merchantId, $this->secretKey);
        $this->qrGenerator = new SbpQrGenerator($this->bankId, $this->merchantId);
        $this->webhookHandler = new SbpWebhookHandler();
        $this->feeCalculator = new SbpFeeCalculator();
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment): array
    {
        $finalAmount = $this->getFinalAmount($payment);
        $qrString = $this->qrGenerator->generateQrString($payment, $finalAmount);
        
        // Создаем платеж в банке
        if (!$this->isTestMode()) {
            $apiResponse = $this->apiClient->createPayment($payment, $finalAmount);
            
            if (!$apiResponse['success']) {
                throw new \Exception($apiResponse['message'] ?? 'Failed to create SBP payment');
            }
        }

        return [
            'success' => true,
            'qr_code' => $qrString,
            'qr_image' => $this->qrGenerator->generateQrCodeImage($qrString),
            'payment_id' => $payment->payment_id,
            'amount' => $finalAmount,
            'currency' => 'RUB',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15)->toISOString(), // QR код действует 15 минут
            'deep_link' => $this->qrGenerator->generateDeepLink($qrString)
        ];
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
        if ($this->isTestMode()) {
            // В тестовом режиме имитируем случайный успех
            $isSuccess = rand(1, 20) === 1; // 5% шанс успеха для тестирования
            
            if ($isSuccess && $payment->status === 'pending') {
                return [
                    'status' => 'completed',
                    'paid' => true,
                    'transaction_id' => 'test_' . uniqid(),
                    'paid_at' => now()->toISOString()
                ];
            }
            
            return [
                'status' => 'pending', 
                'paid' => false
            ];
        }

        // Реальная проверка через API банка
        if (!$payment->external_payment_id) {
            return [
                'status' => 'not_created',
                'paid' => false,
                'message' => 'Payment not created in bank system'
            ];
        }

        $apiResponse = $this->apiClient->checkPaymentStatus($payment->external_payment_id);
        
        return [
            'status' => $apiResponse['status'] ?? 'unknown',
            'paid' => $apiResponse['paid'] ?? false,
            'transaction_id' => $apiResponse['transaction_id'] ?? null,
            'paid_at' => $apiResponse['paid_at'] ?? null,
            'message' => $apiResponse['message'] ?? null
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateWebhook(array $data, array $headers = []): bool
    {
        if ($this->isTestMode()) {
            return true;
        }
        
        return $this->webhookHandler->validateSignature($data, $headers, $this->secretKey);
    }

    /**
     * {@inheritdoc}
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResultDTO
    {
        if (!$payment->external_payment_id) {
            return new PaymentResultDTO(
                success: false,
                message: 'Внешний ID платежа не найден'
            );
        }

        $refundAmount = $amount ?? $payment->amount;
        
        if ($this->isTestMode()) {
            return new PaymentResultDTO(
                success: true,
                transactionId: 'test_refund_' . uniqid(),
                message: 'Возврат выполнен (тестовый режим)',
                data: [
                    'refund_amount' => $refundAmount,
                    'refund_id' => 'test_refund_' . uniqid(),
                    'status' => 'completed'
                ]
            );
        }

        $apiResponse = $this->apiClient->createRefund($payment, $refundAmount);
        
        return new PaymentResultDTO(
            success: $apiResponse['success'] ?? false,
            transactionId: $apiResponse['refund_id'] ?? null,
            message: $apiResponse['message'] ?? 'Refund processed',
            data: $apiResponse
        );
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
        return !empty($this->bankId) && 
               !empty($this->merchantId) && 
               !empty($this->secretKey) && 
               ($this->isTestMode() || $this->apiClient->testConnection());
    }

    /**
     * {@inheritdoc}
     */
    public function requires3DSecure(Payment $payment): bool
    {
        // СБП не использует 3D Secure
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
        // СБП не использует редиректы, только QR коды и deep links
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentFormData(Payment $payment, array $response): array
    {
        return [
            'qr_code' => $response['qr_code'] ?? null,
            'qr_image' => $response['qr_image'] ?? null,
            'deep_link' => $response['deep_link'] ?? null,
            'payment_id' => $response['payment_id'] ?? null,
            'status' => $response['status'] ?? 'pending',
            'amount' => $response['amount'] ?? $payment->amount,
            'currency' => $response['currency'] ?? 'RUB',
            'expires_at' => $response['expires_at'] ?? null
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

        if ($this->isTestMode()) {
            return new PaymentResultDTO(
                success: true,
                message: 'Платеж отменен (тестовый режим)'
            );
        }

        $apiResponse = $this->apiClient->cancelPayment($payment->external_payment_id, $reason);
        
        return new PaymentResultDTO(
            success: $apiResponse['success'] ?? false,
            message: $apiResponse['message'] ?? 'Payment cancellation processed'
        );
    }

    /**
     * Получить структуру комиссий
     */
    public function getFeeStructure(string $currency = 'RUB'): array
    {
        return $this->feeCalculator->getFeeStructure($currency);
    }

    /**
     * Получить информацию о банке-партнере
     */
    public function getBankInfo(): array
    {
        if ($this->isTestMode()) {
            return [
                'bank_id' => $this->bankId,
                'bank_name' => 'Тестовый банк СБП',
                'status' => 'active'
            ];
        }
        
        return $this->apiClient->getBankInfo();
    }

    /**
     * Проверить доступность СБП
     */
    public function checkSbpAvailability(): array
    {
        if ($this->isTestMode()) {
            return [
                'available' => true,
                'maintenance' => false,
                'message' => 'СБП доступна (тестовый режим)'
            ];
        }
        
        return $this->apiClient->checkSbpStatus();
    }

    /**
     * Получить список участников СБП
     */
    public function getSbpParticipants(): array
    {
        return $this->apiClient->getSbpParticipants();
    }

    /**
     * Получить QR код для платежа
     */
    public function getPaymentQrCode(Payment $payment): array
    {
        $finalAmount = $this->getFinalAmount($payment);
        $qrString = $this->qrGenerator->generateQrString($payment, $finalAmount);
        
        return [
            'qr_code' => $qrString,
            'qr_image' => $this->qrGenerator->generateQrCodeImage($qrString),
            'deep_link' => $this->qrGenerator->generateDeepLink($qrString),
            'expires_at' => now()->addMinutes(15)->toISOString()
        ];
    }

    /**
     * Получить минимальную сумму для валюты
     */
    public function getMinimumAmount(string $currency = 'RUB'): float
    {
        return match($currency) {
            'RUB' => 0.01,
            default => 0.01
        };
    }

    /**
     * Получить максимальную сумму для валюты
     */
    public function getMaximumAmount(string $currency = 'RUB'): float
    {
        return match($currency) {
            'RUB' => 2000000.0, // Лимит СБП
            default => 2000000.0
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
     * Получить настройки шлюза
     */
    public function getGatewaySettings(): array
    {
        return [
            'bank_id' => $this->bankId,
            'merchant_id' => $this->merchantId,
            'supported_currencies' => $this->supportedCurrencies,
            'minimum_amount' => $this->minimumAmount,
            'maximum_amount' => $this->maximumAmount,
            'supports_refunds' => $this->supportsRefunds(),
            'supports_partial_refunds' => $this->supportsPartialRefunds(),
            'test_mode' => $this->isTestMode(),
            'qr_expires_minutes' => 15
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
        if (empty($this->bankId)) {
            $result['errors'][] = 'Bank ID не указан';
        }

        if (empty($this->merchantId)) {
            $result['errors'][] = 'Merchant ID не указан';
        }

        if (empty($this->secretKey)) {
            $result['errors'][] = 'Secret key не указан';
        }

        // Проверка подключения к API
        if (!empty($result['errors'])) {
            return $result;
        }

        try {
            if ($this->isTestMode()) {
                $result['valid'] = true;
                $result['warnings'][] = 'Режим тестирования включен';
                $result['test_mode'] = true;
            } else {
                $connectionTest = $this->apiClient->testConnection();
                
                if ($connectionTest['success']) {
                    $result['valid'] = true;
                    $result['bank_info'] = $connectionTest['bank_info'] ?? null;
                } else {
                    $result['errors'][] = 'Не удалось подключиться к банку: ' . ($connectionTest['message'] ?? 'Unknown error');
                }
            }
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Ошибка подключения: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Получить статистику платежей СБП
     */
    public function getPaymentStatistics(\DateTime $from = null, \DateTime $to = null): array
    {
        $from = $from ?? new \DateTime('-30 days');
        $to = $to ?? new \DateTime();
        
        if ($this->isTestMode()) {
            return [
                'total_payments' => rand(50, 500),
                'successful_payments' => rand(40, 450),
                'failed_payments' => rand(5, 50),
                'total_amount' => rand(10000, 100000),
                'average_amount' => rand(200, 2000),
                'period' => [
                    'from' => $from->format('Y-m-d'),
                    'to' => $to->format('Y-m-d')
                ]
            ];
        }
        
        return $this->apiClient->getPaymentStatistics([
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d')
        ]);
    }

    /**
     * Создать webhook для уведомлений
     */
    public function createWebhook(string $url): array
    {
        if ($this->isTestMode()) {
            return [
                'success' => true,
                'webhook_id' => 'test_webhook_' . uniqid(),
                'url' => $url,
                'events' => ['payment.success', 'payment.fail']
            ];
        }
        
        return $this->apiClient->createWebhook($url);
    }

    /**
     * Получить список webhook'ов
     */
    public function getWebhooks(): array
    {
        if ($this->isTestMode()) {
            return [
                [
                    'id' => 'test_webhook_1',
                    'url' => config('app.url') . '/webhooks/sbp',
                    'events' => ['payment.success', 'payment.fail'],
                    'status' => 'active'
                ]
            ];
        }
        
        return $this->apiClient->getWebhooks();
    }

    /**
     * Удалить webhook
     */
    public function deleteWebhook(string $webhookId): bool
    {
        if ($this->isTestMode()) {
            return true;
        }
        
        return $this->apiClient->deleteWebhook($webhookId);
    }
    
    /**
     * Проверить тестовый режим
     */
    private function isTestMode(): bool
    {
        return config('payments.test_mode', true) || 
               config('payments.sbp.test_mode', true) ||
               app()->environment('local', 'testing');
    }
    
    /**
     * Получить финальную сумму с учётом комиссии
     */
    private function getFinalAmount(Payment $payment): float
    {
        // В СБП комиссия обычно берётся с продавца, не добавляется к сумме покупателя
        return $payment->amount;
    }
}

/**
 * API клиент для СБП (внутренний класс)
 */
class SbpApiClient
{
    private string $bankId;
    private string $merchantId;
    private string $secretKey;
    
    public function __construct(string $bankId, string $merchantId, string $secretKey)
    {
        $this->bankId = $bankId;
        $this->merchantId = $merchantId;
        $this->secretKey = $secretKey;
    }
    
    public function createPayment($payment, $amount): array
    {
        // Заглушка для создания платежа
        return ['success' => true, 'payment_id' => uniqid('sbp_')];
    }
    
    public function checkPaymentStatus($paymentId): array
    {
        // Заглушка для проверки статуса
        return ['status' => 'pending', 'paid' => false];
    }
    
    public function createRefund($payment, $amount): array
    {
        // Заглушка для возврата
        return ['success' => true, 'refund_id' => uniqid('refund_')];
    }
    
    public function cancelPayment($paymentId, $reason): array
    {
        // Заглушка для отмены
        return ['success' => true];
    }
    
    public function testConnection(): bool
    {
        return true;
    }
    
    public function getBankInfo(): array
    {
        return ['bank_id' => $this->bankId, 'bank_name' => 'Test Bank', 'status' => 'active'];
    }
    
    public function checkSbpStatus(): array
    {
        return ['available' => true, 'maintenance' => false];
    }
    
    public function getSbpParticipants(): array
    {
        return [];
    }
    
    public function getPaymentStatistics(array $params): array
    {
        return ['total_payments' => 0, 'successful_payments' => 0];
    }
    
    public function createWebhook(string $url): array
    {
        return ['success' => true, 'webhook_id' => uniqid('webhook_')];
    }
    
    public function getWebhooks(): array
    {
        return [];
    }
    
    public function deleteWebhook(string $webhookId): bool
    {
        return true;
    }
    
    public function ping(): bool
    {
        return true;
    }
    
    public function cluster(): array
    {
        return ['health' => ['status' => 'green']];
    }
}

/**
 * Генератор QR кодов для СБП (внутренний класс)
 */
class SbpQrGenerator
{
    private string $bankId;
    private string $merchantId;
    
    public function __construct(string $bankId, string $merchantId)
    {
        $this->bankId = $bankId;
        $this->merchantId = $merchantId;
    }
    
    public function generateQrString($payment, $amount): string
    {
        return 'https://qr.nspk.ru/test/' . uniqid();
    }
    
    public function generateQrCodeImage(string $qrString): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    }
    
    public function generateDeepLink(string $qrString): string
    {
        return 'sbp://qr/' . base64_encode($qrString);
    }
}

/**
 * Обработчик вебхуков СБП (внутренний класс)
 */
class SbpWebhookHandler
{
    public function handle(array $data): bool
    {
        // Заглушка обработки вебхука
        return true;
    }
    
    public function validateSignature(array $data, array $headers, string $secretKey): bool
    {
        // Заглушка валидации подписи
        return true;
    }
}

/**
 * Калькулятор комиссий СБП (внутренний класс)
 */
class SbpFeeCalculator
{
    public function calculateFee(float $amount, array $config): float
    {
        // СБП обычно имеет фиксированную комиссию 0.4-0.7%
        return $amount * 0.007;
    }
    
    public function getFeeStructure(string $currency): array
    {
        return [
            'percentage' => 0.7,
            'fixed' => 0,
            'currency' => $currency
        ];
    }
}