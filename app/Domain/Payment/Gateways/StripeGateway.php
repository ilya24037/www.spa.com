<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use App\Domain\Payment\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Консолидированный платежный шлюз Stripe
 * Включает все Stripe сервисы внутри класса
 */
class StripeGateway implements PaymentGateway
{
    private string $secretKey;
    private string $publishableKey;
    private string $webhookSecret;
    
    private const API_URL = 'https://api.stripe.com/v1';
    
    // Fee calculation constants
    private const BASE_FEE_PERCENT = 0.029; // 2.9%
    private const FIXED_FEES = [
        'USD' => 0.30,
        'EUR' => 0.25,
        'GBP' => 0.20,
        'RUB' => 15.0,
        'CAD' => 0.30,
        'AUD' => 0.30,
        'JPY' => 35.0,
        'CHF' => 0.30,
        'SEK' => 3.0,
        'NOK' => 3.0,
        'DKK' => 2.0,
    ];
    
    private const SPECIAL_PERCENTS = [
        'JPY' => 0.036, // 3.6% для японской йены
        'RUB' => 0.035, // 3.5% для российского рубля
    ];

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
        
        $this->secretKey = $this->config['secret_key'] ?? config('payments.stripe.secret_key');
        $this->publishableKey = $this->config['publishable_key'] ?? config('payments.stripe.publishable_key');
        $this->webhookSecret = $this->config['webhook_secret'] ?? config('payments.stripe.webhook_secret');
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment): array
    {
        $finalAmount = $this->calculateFinalAmount($payment);
        
        // Подготавливаем данные для PaymentIntent
        $data = [
            'amount' => round($finalAmount * 100), // Stripe принимает в центах
            'currency' => strtolower($payment->currency ?? 'usd'),
            'automatic_payment_methods' => [
                'enabled' => true
            ],
            'confirm' => false,
            'metadata' => [
                'payment_id' => $payment->payment_id,
                'user_id' => $payment->user_id,
                'description' => $payment->description
            ]
        ];

        // Добавляем customer если есть
        if (!empty($this->config['customer_id'])) {
            $data['customer'] = $this->config['customer_id'];
        }

        $result = $this->createPaymentIntent($data);

        if ($result['success']) {
            $responseData = $result['data'];
            
            // Сохраняем внешний ID платежа
            $payment->update([
                'external_payment_id' => $responseData['id'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'stripe_data' => $responseData,
                    'client_secret' => $responseData['client_secret']
                ])
            ]);

            return [
                'success' => true,
                'client_secret' => $responseData['client_secret'],
                'payment_intent_id' => $responseData['id'],
                'publishable_key' => $this->publishableKey,
                'amount' => $responseData['amount'],
                'currency' => $responseData['currency'],
                'status' => $responseData['status']
            ];
        }

        Log::error('Stripe payment creation failed', [
            'payment_id' => $payment->payment_id,
            'error' => $result['error']
        ]);

        return [
            'success' => false,
            'error' => $result['error'],
            'code' => $result['code'] ?? 'unknown_error'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(array $data): bool
    {
        try {
            // Проверяем тип события
            $eventType = $data['type'] ?? null;
            if (!$eventType) {
                return false;
            }

            $paymentIntentData = $data['data']['object'] ?? [];
            $paymentId = $paymentIntentData['metadata']['payment_id'] ?? null;

            if (!$paymentId) {
                Log::warning('Stripe webhook without payment_id', $data);
                return false;
            }

            $payment = Payment::where('payment_id', $paymentId)->first();
            if (!$payment) {
                Log::warning('Stripe webhook: payment not found', ['payment_id' => $paymentId]);
                return false;
            }

            return match ($eventType) {
                'payment_intent.succeeded' => $this->handleSuccessfulPayment($payment, $paymentIntentData),
                'payment_intent.payment_failed' => $this->handleFailedPayment($payment, $paymentIntentData),
                'payment_intent.canceled' => $this->handleCancelledPayment($payment, $paymentIntentData),
                'charge.dispute.created' => $this->handleDispute($payment, $paymentIntentData),
                default => $this->handleUnknownEvent($eventType)
            };
            
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error', [
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

        $result = $this->getPaymentIntent($payment->external_payment_id);

        if ($result['success']) {
            $data = $result['data'];
            
            return [
                'status' => $data['status'],
                'paid' => $data['status'] === 'succeeded',
                'amount' => $data['amount'] / 100, // Из центов в рубли
                'currency' => strtoupper($data['currency']),
                'transaction_id' => $data['id'],
                'raw_data' => $data
            ];
        }

        Log::error('Stripe status check error', [
            'payment_id' => $payment->id,
            'error' => $result['error']
        ]);

        return [
            'status' => 'error',
            'paid' => false,
            'error' => $result['error']
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
        
        return $this->validateSignature($data, $headers, $this->webhookSecret);
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
        
        $data = [
            'payment_intent' => $payment->external_payment_id,
            'amount' => round($refundAmount * 100), // В центах
            'reason' => 'requested_by_customer',
            'metadata' => [
                'original_payment_id' => $payment->payment_id,
                'refund_requested_at' => now()->toIso8601String()
            ]
        ];

        $result = $this->createRefund($data);

        if ($result['success']) {
            $refundData = $result['data'];
            
            // Сохраняем информацию о возврате
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'stripe_refund' => $refundData,
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

        return new PaymentResultDTO(
            success: false,
            message: $result['error']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function calculateFee(float $amount, string $currency = 'USD'): float
    {
        return $this->calculatePaymentFee($amount, $currency);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return $this->validateKeys();
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
        return [
            'client_secret' => $response['client_secret'] ?? null,
            'publishable_key' => $this->publishableKey,
            'payment_intent_id' => $response['payment_intent_id'] ?? null
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
                message: 'No external payment ID found'
            );
        }

        $result = $this->cancelPaymentIntent($payment->external_payment_id, $reason);

        if ($result['success']) {
            $data = $result['data'];
            
            $payment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'stripe_cancellation' => $data,
                    'cancellation_reason' => $reason ?? 'manual_cancellation'
                ])
            ]);
            
            return new PaymentResultDTO(
                success: true,
                transactionId: $data['id'],
                message: 'Payment cancelled successfully'
            );
        }

        return new PaymentResultDTO(
            success: false,
            message: $result['error']
        );
    }

    /**
     * Получить структуру комиссий
     */
    public function getFeeStructure(string $currency = 'USD'): array
    {
        $currency = strtoupper($currency);
        
        return [
            'currency' => $currency,
            'percentage' => (self::SPECIAL_PERCENTS[$currency] ?? self::BASE_FEE_PERCENT) * 100,
            'fixed_fee' => self::FIXED_FEES[$currency] ?? self::FIXED_FEES['USD'],
            'dispute_fee' => $this->calculateDisputeFee($currency),
            'supports_international_cards' => true,
            'international_surcharge_percent' => 1.0,
        ];
    }

    /**
     * Рассчитать комиссию за международный платеж
     */
    public function calculateInternationalFee(float $amount, string $currency = 'USD'): float
    {
        $baseFee = $this->calculatePaymentFee($amount, $currency);
        $internationalSurcharge = $amount * 0.01; // Дополнительно 1% за международные платежи
        
        return round($baseFee + $internationalSurcharge, 2);
    }

    /**
     * Создать клиента Stripe
     */
    public function createCustomer(array $customerData): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/customers', $customerData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Customer creation failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe customer creation error', [
                'data' => $customerData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Customer creation failed'
            ];
        }
    }

    /**
     * Получить информацию о клиенте
     */
    public function getCustomer(string $customerId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . "/customers/{$customerId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Customer not found'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe customer get error', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получить список возвратов
     */
    public function getRefunds(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return ['success' => false, 'error' => 'No external payment ID'];
        }

        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . '/refunds', [
                    'payment_intent' => $payment->external_payment_id
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get refunds'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe refunds get error', [
                'payment_intent_id' => $payment->external_payment_id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Получить минимальную сумму для валюты
     */
    public function getMinimumAmount(string $currency = 'USD'): float
    {
        return match (strtoupper($currency)) {
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF' => 0.50,
            'RUB' => 30.0,
            'JPY' => 50.0,
            'SEK', 'NOK' => 3.0,
            'DKK' => 2.5,
            default => 0.50
        };
    }

    /**
     * Получить максимальную сумму для валюты
     */
    public function getMaximumAmount(string $currency = 'USD'): float
    {
        return match (strtoupper($currency)) {
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF' => 999999.99,
            'RUB' => 73000000.0, // ~1M USD
            'JPY' => 110000000.0, // ~1M USD
            'SEK', 'NOK' => 9000000.0, // ~1M USD
            'DKK' => 6700000.0, // ~1M USD
            default => 999999.99
        };
    }

    /**
     * Проверить поддерживается ли валюта
     */
    public function isCurrencySupported(string $currency): bool
    {
        return isset(self::FIXED_FEES[strtoupper($currency)]);
    }
    
    // =====================================================
    // PRIVATE API CLIENT METHODS (ex-StripeApiClient)
    // =====================================================
    
    /**
     * Создать PaymentIntent
     */
    private function createPaymentIntent(array $data): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/payment_intents', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $error = $response->json();
            Log::error('Stripe PaymentIntent creation failed', [
                'error' => $error,
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Ошибка создания платежа',
                'code' => $error['error']['code'] ?? 'unknown_error'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent exception', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'error' => 'Ошибка соединения с платежной системой'
            ];
        }
    }
    
    /**
     * Получить информацию о PaymentIntent
     */
    private function getPaymentIntent(string $paymentIntentId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . "/payment_intents/{$paymentIntentId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get payment intent'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent get error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Создать возврат
     */
    private function createRefund(array $data): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/refunds', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Refund failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe refund error', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Refund processing failed'
            ];
        }
    }
    
    /**
     * Отменить PaymentIntent
     */
    private function cancelPaymentIntent(string $paymentIntentId, ?string $reason = null): array
    {
        try {
            $data = [];
            if ($reason) {
                $data['cancellation_reason'] = $reason;
            }

            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . "/payment_intents/{$paymentIntentId}/cancel", $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Cancellation failed'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent cancel error', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Проверить валидность ключей
     */
    private function validateKeys(): bool
    {
        return !empty($this->secretKey) && 
               !empty($this->publishableKey) && 
               str_starts_with($this->secretKey, 'sk_') &&
               str_starts_with($this->publishableKey, 'pk_');
    }
    
    // =====================================================
    // PRIVATE FEE CALCULATOR METHODS (ex-StripeFeeCalculator)
    // =====================================================
    
    /**
     * Рассчитать комиссию за платеж
     */
    private function calculatePaymentFee(float $amount, string $currency = 'USD'): float
    {
        $currency = strtoupper($currency);
        
        $feePercent = self::SPECIAL_PERCENTS[$currency] ?? self::BASE_FEE_PERCENT;
        $fixedFee = self::FIXED_FEES[$currency] ?? self::FIXED_FEES['USD'];
        
        return round($amount * $feePercent + $fixedFee, 2);
    }
    
    /**
     * Рассчитать комиссию за диспут
     */
    private function calculateDisputeFee(string $currency = 'USD'): float
    {
        return match (strtoupper($currency)) {
            'USD' => 15.00,
            'EUR' => 15.00,
            'GBP' => 15.00,
            'RUB' => 1100.0,
            'JPY' => 1500.0,
            default => 15.00
        };
    }
    
    /**
     * Рассчитать финальную сумму платежа
     */
    private function calculateFinalAmount(Payment $payment): float
    {
        $amount = $payment->amount;
        
        // Применяем скидку если есть
        if ($payment->discount_amount > 0) {
            $amount -= $payment->discount_amount;
        }
        
        // Добавляем дополнительную комиссию если есть
        if ($payment->fee_amount > 0) {
            $amount += $payment->fee_amount;
        }
        
        return max($amount, $this->getMinimumAmount($payment->currency ?? 'USD'));
    }
    
    // =====================================================
    // PRIVATE WEBHOOK HANDLER METHODS (ex-StripeWebhookHandler)
    // =====================================================
    
    /**
     * Валидировать подпись вебхука
     */
    private function validateSignature(array $data, array $headers, string $webhookSecret): bool
    {
        // Проверяем подпись Stripe
        $signature = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? null;
        
        if (!$signature || !$webhookSecret) {
            return false;
        }

        try {
            $payload = json_encode($data);
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
            
            // Stripe отправляет несколько версий подписи
            $signatures = [];
            foreach (explode(',', $signature) as $sig) {
                $parts = explode('=', trim($sig), 2);
                if (count($parts) === 2) {
                    $signatures[$parts[0]] = $parts[1];
                }
            }
            
            return isset($signatures['v1']) && 
                   hash_equals($expectedSignature, $signatures['v1']);
                   
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature validation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Обработать успешный платеж
     */
    private function handleSuccessfulPayment(Payment $payment, array $paymentIntentData): bool
    {
        // Проверяем, не был ли платеж уже обработан
        if ($payment->status === 'completed') {
            return true;
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'external_payment_id' => $paymentIntentData['id'],
            'metadata' => array_merge($payment->metadata ?? [], [
                'stripe_webhook' => $paymentIntentData,
                'paid_amount' => ($paymentIntentData['amount_received'] ?? 0) / 100
            ])
        ]);

        // Вызываем событие успешной оплаты
        event(new \App\Events\Payment\PaymentCompleted($payment));

        Log::info('Stripe payment completed', [
            'payment_id' => $payment->payment_id,
            'external_id' => $paymentIntentData['id']
        ]);

        return true;
    }
    
    /**
     * Обработать неудачный платеж
     */
    private function handleFailedPayment(Payment $payment, array $paymentIntentData): bool
    {
        $payment->update([
            'status' => 'failed',
            'failed_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'stripe_failure' => $paymentIntentData,
                'failure_reason' => $paymentIntentData['last_payment_error']['message'] ?? 'unknown'
            ])
        ]);

        // Вызываем событие неудачной оплаты
        event(new \App\Events\Payment\PaymentFailed($payment));

        Log::info('Stripe payment failed', [
            'payment_id' => $payment->payment_id,
            'reason' => $paymentIntentData['last_payment_error']['message'] ?? 'unknown'
        ]);

        return true;
    }
    
    /**
     * Обработать отмененный платеж
     */
    private function handleCancelledPayment(Payment $payment, array $paymentIntentData): bool
    {
        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'stripe_cancellation' => $paymentIntentData,
                'cancellation_reason' => $paymentIntentData['cancellation_reason'] ?? 'unknown'
            ])
        ]);

        // Вызываем событие отмены платежа
        event(new \App\Events\Payment\PaymentCancelled($payment));

        Log::info('Stripe payment cancelled', [
            'payment_id' => $payment->payment_id,
            'reason' => $paymentIntentData['cancellation_reason'] ?? 'unknown'
        ]);

        return true;
    }
    
    /**
     * Обработать диспут (возражение клиента)
     */
    private function handleDispute(Payment $payment, array $disputeData): bool
    {
        $payment->update([
            'metadata' => array_merge($payment->metadata ?? [], [
                'stripe_dispute' => $disputeData,
                'dispute_created_at' => now()->toIso8601String(),
                'dispute_reason' => $disputeData['reason'] ?? 'unknown',
                'dispute_amount' => ($disputeData['amount'] ?? 0) / 100
            ])
        ]);

        // Вызываем событие диспута
        event(new \App\Events\Payment\PaymentDisputed($payment, $disputeData));

        Log::warning('Stripe payment disputed', [
            'payment_id' => $payment->payment_id,
            'dispute_id' => $disputeData['id'] ?? null,
            'reason' => $disputeData['reason'] ?? 'unknown'
        ]);

        return true;
    }
    
    /**
     * Обработать неизвестное событие
     */
    private function handleUnknownEvent(string $eventType): bool
    {
        Log::info('Stripe webhook event not handled', ['type' => $eventType]);
        return true; // Возвращаем true чтобы Stripe не повторял отправку
    }
}