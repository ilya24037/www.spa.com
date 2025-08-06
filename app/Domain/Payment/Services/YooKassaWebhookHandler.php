<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки webhook'ов от YooKassa
 */
class YooKassaWebhookHandler
{
    /**
     * Обработать webhook
     */
    public function handle(array $data): bool
    {
        try {
            // Проверяем тип события
            if (!$this->isValidEvent($data)) {
                return false;
            }

            $paymentData = $data['object'] ?? [];
            $paymentId = $paymentData['metadata']['payment_id'] ?? null;

            if (!$paymentId) {
                Log::warning('YooKassa webhook without payment_id', $data);
                return false;
            }

            $payment = $this->findPayment($paymentId);
            if (!$payment) {
                Log::warning('YooKassa webhook: payment not found', ['payment_id' => $paymentId]);
                return false;
            }

            return match($data['event']) {
                'payment.succeeded' => $this->handleSuccessfulPayment($payment, $paymentData),
                'payment.canceled' => $this->handleCancelledPayment($payment, $paymentData),
                default => false,
            };

        } catch (\Exception $e) {
            Log::error('YooKassa webhook processing error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Валидировать webhook данные
     */
    public function validateWebhook(array $data, array $headers = [], bool $isTestMode = false): bool
    {
        // YooKassa не использует подписи для webhook
        // Вместо этого рекомендуется проверять IP-адреса
        
        if ($isTestMode) {
            return true;
        }
        
        // Проверяем структуру данных
        return isset($data['type']) && 
               isset($data['event']) && 
               isset($data['object']);
    }

    /**
     * Проверить валидность события
     */
    private function isValidEvent(array $data): bool
    {
        return isset($data['event']) && 
               in_array($data['event'], ['payment.succeeded', 'payment.canceled']);
    }

    /**
     * Найти платеж по ID
     */
    private function findPayment(string $paymentId): ?Payment
    {
        return Payment::where('payment_id', $paymentId)->first();
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
}