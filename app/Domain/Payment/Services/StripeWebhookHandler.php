<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик вебхуков Stripe
 */
class StripeWebhookHandler
{
    /**
     * Обработать вебхук от Stripe
     */
    public function handle(array $data): bool
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
     * Валидировать подпись вебхука
     */
    public function validateSignature(array $data, array $headers, string $webhookSecret): bool
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
    public function handleSuccessfulPayment(Payment $payment, array $paymentIntentData): bool
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
    public function handleFailedPayment(Payment $payment, array $paymentIntentData): bool
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
    public function handleCancelledPayment(Payment $payment, array $paymentIntentData): bool
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
    public function handleDispute(Payment $payment, array $disputeData): bool
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

    /**
     * Получить все поддерживаемые типы событий
     */
    public function getSupportedEventTypes(): array
    {
        return [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
            'charge.dispute.created',
        ];
    }

    /**
     * Проверить является ли событие поддерживаемым
     */
    public function isEventSupported(string $eventType): bool
    {
        return in_array($eventType, $this->getSupportedEventTypes());
    }
}