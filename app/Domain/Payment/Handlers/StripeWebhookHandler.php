<?php

namespace App\Domain\Payment\Handlers;

use App\Domain\Payment\Contracts\WebhookHandlerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик webhook для Stripe
 */
class StripeWebhookHandler implements WebhookHandlerInterface
{
    /**
     * Поддерживаемые события
     */
    protected array $supportedEvents = [
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'charge.succeeded',
        'charge.failed',
        'charge.refunded',
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'invoice.payment_succeeded',
        'invoice.payment_failed',
    ];

    /**
     * Проверить подпись webhook
     */
    public function verifySignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        try {
            $elements = explode(',', $signature);
            $timestamp = null;
            $signatures = [];

            foreach ($elements as $element) {
                $parts = explode('=', $element, 2);
                if ($parts[0] === 't') {
                    $timestamp = $parts[1];
                } elseif ($parts[0] === 'v1') {
                    $signatures[] = $parts[1];
                }
            }

            if (!$timestamp) {
                return false;
            }

            // Проверить timestamp (защита от replay атак)
            $tolerance = 300; // 5 минут
            if (abs(time() - $timestamp) > $tolerance) {
                return false;
            }

            // Создать подписанный payload
            $signedPayload = $timestamp . '.' . $payload;
            $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

            // Проверить подпись
            foreach ($signatures as $signature) {
                if (hash_equals($expectedSignature, $signature)) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Обработать webhook
     */
    public function handle(Request $request): array
    {
        $event = json_decode($request->getContent(), true);

        return [
            'success' => true,
            'event_id' => $event['id'] ?? null,
            'event_type' => $event['type'] ?? null,
            'data' => $event['data'] ?? [],
        ];
    }

    /**
     * Получить тип события из webhook
     */
    public function getEventType(Request $request): ?string
    {
        $event = json_decode($request->getContent(), true);
        return $event['type'] ?? null;
    }

    /**
     * Получить ID платежа из webhook
     */
    public function getPaymentId(Request $request): ?string
    {
        $event = json_decode($request->getContent(), true);
        
        // Stripe может отправлять ID в разных местах в зависимости от типа события
        return $event['data']['object']['id'] ?? 
               $event['data']['object']['payment_intent'] ?? 
               $event['data']['object']['charge'] ?? 
               null;
    }

    /**
     * Получить статус платежа из webhook
     */
    public function getPaymentStatus(Request $request): ?string
    {
        $event = json_decode($request->getContent(), true);
        $status = $event['data']['object']['status'] ?? null;

        // Преобразовать статус Stripe в наш формат
        return match($status) {
            'succeeded' => 'completed',
            'processing' => 'processing',
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            'canceled' => 'cancelled',
            'failed' => 'failed',
            default => $status,
        };
    }

    /**
     * Поддерживает ли обработчик данный тип события
     */
    public function supportsEvent(string $eventType): bool
    {
        return in_array($eventType, $this->supportedEvents);
    }

    /**
     * Получить название платежного гейтвея
     */
    public function getGatewayName(): string
    {
        return 'stripe';
    }
}