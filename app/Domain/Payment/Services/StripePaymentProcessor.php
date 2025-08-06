<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;
use Illuminate\Support\Facades\Log;

/**
 * Процессор платежей Stripe
 */
class StripePaymentProcessor
{
    public function __construct(
        private StripeApiClient $apiClient,
        private StripeFeeCalculator $feeCalculator
    ) {}

    /**
     * Создать платеж
     */
    public function createPayment(Payment $payment, array $config = []): array
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
        if (!empty($config['customer_id'])) {
            $data['customer'] = $config['customer_id'];
        }

        $result = $this->apiClient->createPaymentIntent($data);

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
                'publishable_key' => $this->apiClient->getPublishableKey(),
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
     * Проверить статус платежа
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return [
                'status' => 'not_created',
                'paid' => false
            ];
        }

        $result = $this->apiClient->getPaymentIntent($payment->external_payment_id);

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
     * Выполнить возврат
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

        $result = $this->apiClient->createRefund($data);

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

        $result = $this->apiClient->cancelPaymentIntent($payment->external_payment_id, $reason);

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
     * Получить данные для формы оплаты
     */
    public function getPaymentFormData(Payment $payment, array $response): array
    {
        return [
            'client_secret' => $response['client_secret'] ?? null,
            'publishable_key' => $this->apiClient->getPublishableKey(),
            'payment_intent_id' => $response['payment_intent_id'] ?? null
        ];
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
        
        return max($amount, $this->feeCalculator->getMinimumAmount($payment->currency ?? 'USD'));
    }

    /**
     * Создать клиента Stripe
     */
    public function createCustomer(array $customerData): array
    {
        return $this->apiClient->createCustomer($customerData);
    }

    /**
     * Получить информацию о клиенте
     */
    public function getCustomer(string $customerId): array
    {
        return $this->apiClient->getCustomer($customerId);
    }

    /**
     * Получить список возвратов
     */
    public function getRefunds(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return ['success' => false, 'error' => 'No external payment ID'];
        }

        return $this->apiClient->getRefunds($payment->external_payment_id);
    }
}