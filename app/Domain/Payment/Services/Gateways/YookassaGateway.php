<?php

namespace App\Domain\Payment\Services\Gateways;

use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Адаптер для работы с YooKassa
 */
class YookassaGateway implements PaymentGatewayInterface
{
    /**
     * Создать платеж
     */
    public function createPayment(Payment $payment): array
    {
        $finalAmount = $payment->metadata['final_amount'] ?? $payment->amount;
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
            'description' => $payment->description
        ];

        $response = Http::withBasicAuth(
            config('payments.yookassa.shop_id'),
            config('payments.yookassa.secret_key')
        )->withHeaders([
            'Idempotence-Key' => $payment->payment_id
        ])->post('https://api.yookassa.ru/v3/payments', $data);

        if ($response->successful()) {
            $responseData = $response->json();
            
            $payment->update([
                'external_payment_id' => $responseData['id'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'yookassa_data' => $responseData
                ])
            ]);

            return [
                'success' => true,
                'redirect_url' => $responseData['confirmation']['confirmation_url'],
                'payment_id' => $responseData['id']
            ];
        }

        Log::error('YooKassa payment creation failed', [
            'payment_id' => $payment->payment_id,
            'response' => $response->body()
        ]);

        return [
            'success' => false,
            'error' => 'Ошибка создания платежа'
        ];
    }

    /**
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool
    {
        if (!isset($data['object']) || $data['object']['status'] !== 'succeeded') {
            return false;
        }

        $paymentData = $data['object'];
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

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'external_payment_id' => $paymentData['id'],
            'metadata' => array_merge($payment->metadata ?? [], [
                'yookassa_webhook' => $paymentData
            ])
        ]);

        return true;
    }

    /**
     * Проверить статус платежа
     */
    public function checkStatus(Payment $payment): array
    {
        if (!$payment->external_payment_id) {
            return ['status' => 'not_created'];
        }

        $response = Http::withBasicAuth(
            config('payments.yookassa.shop_id'),
            config('payments.yookassa.secret_key')
        )->get("https://api.yookassa.ru/v3/payments/{$payment->external_payment_id}");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => $data['status'],
                'paid' => $data['status'] === 'succeeded'
            ];
        }

        return ['status' => 'error'];
    }
}