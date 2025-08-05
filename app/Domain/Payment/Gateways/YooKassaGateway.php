<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Платежный шлюз YooKassa (ЮKassa)
 * 
 * Реализация интеграции с платежной системой YooKassa
 * https://yookassa.ru/developers
 */
class YooKassaGateway extends AbstractPaymentGateway
{
    /**
     * @var string API endpoint
     */
    private const API_URL = 'https://api.yookassa.ru/v3';

    /**
     * @var string Shop ID
     */
    private string $shopId;

    /**
     * @var string Secret key
     */
    private string $secretKey;

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
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment): array
    {
        try {
            $finalAmount = $this->getFinalAmount($payment);
            
            $data = [
                'amount' => [
                    'value' => number_format($finalAmount, 2, '.', ''),
                    'currency' => 'RUB'
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('payment.success', $payment)
                ],
                'capture' => true,
                'metadata' => [
                    'payment_id' => $payment->payment_id,
                    'user_id' => $payment->user_id
                ],
                'description' => $this->prepareDescription($payment)
            ];

            // Добавляем получателя для маркетплейсов
            if (!empty($this->config['marketplace_mode'])) {
                $data['receipt'] = $this->prepareReceipt($payment);
            }

            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $this->generateIdempotencyKey($payment)
                ])
                ->post(self::API_URL . '/payments', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Сохраняем внешний ID платежа
                $payment->update([
                    'external_payment_id' => $responseData['id'],
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'yookassa_data' => $responseData
                    ])
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $responseData['confirmation']['confirmation_url'],
                    'payment_id' => $responseData['id'],
                    'status' => $responseData['status']
                ];
            }

            $error = $response->json();
            Log::error('YooKassa payment creation failed', [
                'payment_id' => $payment->payment_id,
                'error' => $error
            ]);

            return [
                'success' => false,
                'error' => $error['description'] ?? 'Ошибка создания платежа',
                'code' => $error['code'] ?? 'unknown_error'
            ];
            
        } catch (\Exception $e) {
            Log::error('YooKassa payment exception', [
                'payment_id' => $payment->payment_id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Ошибка соединения с платежной системой'
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(array $data): bool
    {
        try {
            // Проверяем тип события
            if (!isset($data['event']) || !in_array($data['event'], ['payment.succeeded', 'payment.canceled'])) {
                return false;
            }

            $paymentData = $data['object'] ?? [];
            $paymentId = $paymentData['metadata']['payment_id'] ?? null;

            if (!$paymentId) {
                Log::warning('YooKassa webhook without payment_id', $data);
                return false;
            }

            $payment = Payment::where('payment_id', $paymentId)->first();
            if (!$payment) {
                Log::warning('YooKassa webhook: payment not found', ['payment_id' => $paymentId]);
                return false;
            }

            switch ($data['event']) {
                case 'payment.succeeded':
                    return $this->handleSuccessfulPayment($payment, $paymentData);
                    
                case 'payment.canceled':
                    return $this->handleCancelledPayment($payment, $paymentData);
                    
                default:
                    return false;
            }
            
        } catch (\Exception $e) {
            Log::error('YooKassa webhook processing error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return [
                'status' => 'not_created',
                'paid' => false
            ];
        }

        try {
            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->get(self::API_URL . "/payments/{$payment->external_payment_id}");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'status' => $data['status'],
                    'paid' => $data['status'] === 'succeeded',
                    'amount' => $data['amount']['value'] ?? 0,
                    'refundable' => $data['refundable'] ?? false,
                    'transaction_id' => $data['id'],
                    'raw_data' => $data
                ];
            }

            return [
                'status' => 'error',
                'paid' => false,
                'error' => 'Failed to check payment status'
            ];
            
        } catch (\Exception $e) {
            Log::error('YooKassa status check error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'error',
                'paid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateWebhook(array $data, array $headers = []): bool
    {
        // YooKassa не использует подписи для webhook
        // Вместо этого рекомендуется проверять IP-адреса
        // В production нужно добавить проверку IP из списка YooKassa
        
        if ($this->isTestMode()) {
            return true;
        }
        
        // Проверяем структуру данных
        return isset($data['type']) && 
               isset($data['event']) && 
               isset($data['object']);
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

        try {
            $refundAmount = $amount ?? $payment->amount;
            
            $data = [
                'amount' => [
                    'value' => number_format($refundAmount, 2, '.', ''),
                    'currency' => 'RUB'
                ],
                'payment_id' => $payment->external_payment_id
            ];

            $response = Http::withBasicAuth($this->shopId, $this->secretKey)
                ->withHeaders([
                    'Idempotence-Key' => $this->generateIdempotencyKey($payment) . '_refund'
                ])
                ->post(self::API_URL . '/refunds', $data);

            if ($response->successful()) {
                $refundData = $response->json();
                
                // Сохраняем информацию о возврате
                $payment->update([
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'refund_data' => $refundData,
                        'refunded_at' => now()->toIso8601String(),
                        'refunded_amount' => $refundAmount
                    ])
                ]);
                
                return new PaymentResultDTO(
                    success: true,
                    transactionId: $refundData['id'],
                    message: 'Refund initiated successfully'
                );
            }

            $error = $response->json();
            return new PaymentResultDTO(
                success: false,
                message: $error['description'] ?? 'Refund failed'
            );
            
        } catch (\Exception $e) {
            Log::error('YooKassa refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return new PaymentResultDTO(
                success: false,
                message: 'Refund processing failed'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculateFee(float $amount, string $currency = 'RUB'): float
    {
        // Комиссия YooKassa для самозанятых: 6% + 3 руб
        if ($this->config['self_employed_mode'] ?? false) {
            return round($amount * 0.06 + 3, 2);
        }
        
        // Стандартная комиссия: 2.8%
        return round($amount * 0.028, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->shopId) && !empty($this->secretKey);
    }

    /**
     * Обработать успешный платеж
     */
    private function handleSuccessfulPayment(Payment $payment, array $paymentData): bool
    {
        // Проверяем, не был ли платеж уже обработан
        if ($payment->status === 'completed') {
            return true;
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'external_payment_id' => $paymentData['id'],
            'metadata' => array_merge($payment->metadata ?? [], [
                'yookassa_webhook' => $paymentData,
                'paid_amount' => $paymentData['amount']['value'] ?? null
            ])
        ]);

        // Вызываем событие успешной оплаты
        event(new \App\Events\Payment\PaymentCompleted($payment));

        Log::info('YooKassa payment completed', [
            'payment_id' => $payment->payment_id,
            'external_id' => $paymentData['id']
        ]);

        return true;
    }

    /**
     * Обработать отмененный платеж
     */
    private function handleCancelledPayment(Payment $payment, array $paymentData): bool
    {
        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'yookassa_cancellation' => $paymentData,
                'cancellation_reason' => $paymentData['cancellation_details']['reason'] ?? 'unknown'
            ])
        ]);

        // Вызываем событие отмены платежа
        event(new \App\Events\Payment\PaymentCancelled($payment));

        Log::info('YooKassa payment cancelled', [
            'payment_id' => $payment->payment_id,
            'reason' => $paymentData['cancellation_details']['reason'] ?? 'unknown'
        ]);

        return true;
    }

    /**
     * Подготовить описание платежа
     */
    private function prepareDescription(Payment $payment): string
    {
        $description = $payment->description;
        
        // Ограничение YooKassa на длину описания
        if (mb_strlen($description) > 128) {
            $description = mb_substr($description, 0, 125) . '...';
        }
        
        return $description;
    }

    /**
     * Подготовить чек для отправки
     */
    private function prepareReceipt(Payment $payment): array
    {
        // Заглушка для чека
        // В реальном проекте здесь должна быть полная фискализация
        return [
            'customer' => [
                'email' => $payment->user->email ?? null,
                'phone' => $payment->user->phone ?? null
            ],
            'items' => [
                [
                    'description' => $this->prepareDescription($payment),
                    'amount' => [
                        'value' => number_format($this->getFinalAmount($payment), 2, '.', ''),
                        'currency' => 'RUB'
                    ],
                    'vat_code' => 1, // НДС не облагается
                    'quantity' => 1
                ]
            ]
        ];
    }
}