<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Платежный шлюз Stripe
 * 
 * Реализация интеграции с платежной системой Stripe
 * https://stripe.com/docs/api
 */
class StripeGateway extends AbstractPaymentGateway
{
    /**
     * @var string API endpoint
     */
    private const API_URL = 'https://api.stripe.com/v1';

    /**
     * @var string Secret key
     */
    private string $secretKey;

    /**
     * @var string Publishable key
     */
    private string $publishableKey;

    /**
     * @var string Webhook secret
     */
    private string $webhookSecret;

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
        try {
            $finalAmount = $this->getFinalAmount($payment);
            
            // Создаем PaymentIntent
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

            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/payment_intents', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                
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

            $error = $response->json();
            Log::error('Stripe payment creation failed', [
                'payment_id' => $payment->payment_id,
                'error' => $error
            ]);

            return [
                'success' => false,
                'error' => $error['error']['message'] ?? 'Ошибка создания платежа',
                'code' => $error['error']['code'] ?? 'unknown_error'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe payment exception', [
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

            switch ($eventType) {
                case 'payment_intent.succeeded':
                    return $this->handleSuccessfulPayment($payment, $paymentIntentData);
                
                case 'payment_intent.payment_failed':
                    return $this->handleFailedPayment($payment, $paymentIntentData);
                
                case 'payment_intent.canceled':
                    return $this->handleCancelledPayment($payment, $paymentIntentData);
                
                case 'charge.dispute.created':
                    return $this->handleDispute($payment, $paymentIntentData);
                
                default:
                    Log::info('Stripe webhook event not handled', ['type' => $eventType]);
                    return true;
            }
            
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

        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . "/payment_intents/{$payment->external_payment_id}");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'status' => $data['status'],
                    'paid' => $data['status'] === 'succeeded',
                    'amount' => $data['amount'] / 100, // Из центов в рубли
                    'currency' => strtoupper($data['currency']),
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
            Log::error('Stripe status check error', [
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
        if ($this->isTestMode()) {
            return true;
        }
        
        // Проверяем подпись Stripe
        $signature = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? null;
        
        if (!$signature || !$this->webhookSecret) {
            return false;
        }

        try {
            $payload = json_encode($data);
            $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
            
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
                'payment_intent' => $payment->external_payment_id,
                'amount' => round($refundAmount * 100), // В центах
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'original_payment_id' => $payment->payment_id,
                    'refund_requested_at' => now()->toIso8601String()
                ]
            ];

            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/refunds', $data);

            if ($response->successful()) {
                $refundData = $response->json();
                
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

            $error = $response->json();
            return new PaymentResultDTO(
                success: false,
                message: $error['error']['message'] ?? 'Refund failed'
            );
            
        } catch (\Exception $e) {
            Log::error('Stripe refund error', [
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
    public function calculateFee(float $amount, string $currency = 'USD'): float
    {
        // Комиссия Stripe: 2.9% + $0.30 (для USD)
        $feePercent = 0.029;
        $fixedFee = match(strtoupper($currency)) {
            'USD' => 0.30,
            'EUR' => 0.25,
            'GBP' => 0.20,
            'RUB' => 15.0,
            default => 0.30
        };
        
        return round($amount * $feePercent + $fixedFee, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->secretKey) && !empty($this->publishableKey);
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
}