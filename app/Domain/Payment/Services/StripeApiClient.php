<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * API клиент для работы со Stripe
 */
class StripeApiClient
{
    private const API_URL = 'https://api.stripe.com/v1';

    public function __construct(
        private string $secretKey,
        private string $publishableKey
    ) {}

    /**
     * Создать PaymentIntent
     */
    public function createPaymentIntent(array $data): array
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
    public function getPaymentIntent(string $paymentIntentId): array
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
    public function createRefund(array $data): array
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
     * Создать клиента
     */
    public function createCustomer(array $data): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->asForm()
                ->post(self::API_URL . '/customers', $data);

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
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Customer creation failed'
            ];
        }
    }

    /**
     * Отменить PaymentIntent
     */
    public function cancelPaymentIntent(string $paymentIntentId, ?string $reason = null): array
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
     * Получить список возвратов для PaymentIntent
     */
    public function getRefunds(string $paymentIntentId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . '/refunds', [
                    'payment_intent' => $paymentIntentId
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
     * Получить publishable key
     */
    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    /**
     * Проверить валидность ключей
     */
    public function validateKeys(): bool
    {
        return !empty($this->secretKey) && 
               !empty($this->publishableKey) && 
               str_starts_with($this->secretKey, 'sk_') &&
               str_starts_with($this->publishableKey, 'pk_');
    }

    /**
     * Получить информацию об аккаунте
     */
    public function getAccount(): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->get(self::API_URL . '/account');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get account info'
            ];
            
        } catch (\Exception $e) {
            Log::error('Stripe account get error', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}