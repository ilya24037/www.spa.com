<?php

namespace App\Domain\Payment\Handlers;

use App\Domain\Payment\Contracts\WebhookHandlerInterface;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\TransactionService;
use App\Domain\Payment\Services\SubscriptionService;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\Transaction;
use App\Domain\Payment\Enums\TransactionStatus;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Основной обработчик webhook от платежных систем
 */
class WebhookHandler
{
    /**
     * Зарегистрированные обработчики для разных платежных систем
     */
    protected array $handlers = [];

    public function __construct(
        protected PaymentService $paymentService,
        protected TransactionService $transactionService,
        protected SubscriptionService $subscriptionService
    ) {
        $this->registerDefaultHandlers();
    }

    /**
     * Зарегистрировать обработчик для платежной системы
     */
    public function registerHandler(string $gateway, WebhookHandlerInterface $handler): void
    {
        $this->handlers[$gateway] = $handler;
    }

    /**
     * Обработать webhook
     */
    public function handle(string $gateway, Request $request): array
    {
        Log::info('Webhook received', [
            'gateway' => $gateway,
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            // Получить обработчик для платежной системы
            $handler = $this->getHandler($gateway);

            // Проверить подпись
            if (!$handler->verifySignature($request)) {
                Log::warning('Webhook signature verification failed', [
                    'gateway' => $gateway,
                    'ip' => $request->ip(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }

            // Обработать webhook
            $result = DB::transaction(function () use ($handler, $request) {
                return $this->processWebhook($handler, $request);
            });

            Log::info('Webhook processed successfully', [
                'gateway' => $gateway,
                'result' => $result,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Обработать webhook через конкретный обработчик
     */
    protected function processWebhook(WebhookHandlerInterface $handler, Request $request): array
    {
        // Получить тип события
        $eventType = $handler->getEventType($request);
        
        if (!$eventType) {
            throw new \Exception('Unable to determine event type');
        }

        // Проверить, поддерживается ли событие
        if (!$handler->supportsEvent($eventType)) {
            return [
                'success' => true,
                'message' => 'Event type not supported',
                'event' => $eventType,
            ];
        }

        // Обработать событие в зависимости от типа
        return match($eventType) {
            'payment.succeeded', 'charge.succeeded', 'payment_intent.succeeded' => 
                $this->handlePaymentSuccess($handler, $request),
            
            'payment.failed', 'charge.failed', 'payment_intent.payment_failed' => 
                $this->handlePaymentFailed($handler, $request),
            
            'payment.refunded', 'charge.refunded', 'refund.succeeded' => 
                $this->handleRefund($handler, $request),
            
            'subscription.created', 'customer.subscription.created' => 
                $this->handleSubscriptionCreated($handler, $request),
            
            'subscription.updated', 'customer.subscription.updated' => 
                $this->handleSubscriptionUpdated($handler, $request),
            
            'subscription.deleted', 'customer.subscription.deleted' => 
                $this->handleSubscriptionCancelled($handler, $request),
            
            'invoice.payment_succeeded' => 
                $this->handleInvoicePaymentSucceeded($handler, $request),
            
            'invoice.payment_failed' => 
                $this->handleInvoicePaymentFailed($handler, $request),
            
            default => $handler->handle($request),
        };
    }

    /**
     * Обработать успешный платеж
     */
    protected function handlePaymentSuccess(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $handler->getPaymentId($request);
        
        if (!$externalId) {
            throw new \Exception('Payment ID not found in webhook');
        }

        // Найти платеж
        $payment = Payment::where('external_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$payment) {
            Log::warning('Payment not found for webhook', [
                'external_id' => $externalId,
                'gateway' => $handler->getGatewayName(),
            ]);

            return [
                'success' => true,
                'message' => 'Payment not found',
            ];
        }

        // Обновить статус платежа
        if ($payment->status !== PaymentStatus::COMPLETED) {
            $payment->update([
                'status' => PaymentStatus::COMPLETED,
                'confirmed_at' => now(),
                'gateway_response' => $request->all(),
            ]);

            // Создать/обновить транзакцию
            $transaction = Transaction::where('payment_id', $payment->id)->first();
            
            if ($transaction) {
                $this->transactionService->processSuccessfulTransaction($transaction);
            } else {
                $this->transactionService->createPaymentTransaction($payment);
            }
        }

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'status' => 'completed',
        ];
    }

    /**
     * Обработать неудачный платеж
     */
    protected function handlePaymentFailed(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $handler->getPaymentId($request);
        
        if (!$externalId) {
            throw new \Exception('Payment ID not found in webhook');
        }

        // Найти платеж
        $payment = Payment::where('external_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$payment) {
            return [
                'success' => true,
                'message' => 'Payment not found',
            ];
        }

        // Обновить статус платежа
        $payment->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
            'gateway_response' => $request->all(),
        ]);

        // Обновить транзакцию
        $transaction = Transaction::where('payment_id', $payment->id)->first();
        
        if ($transaction) {
            $this->transactionService->processFailedTransaction(
                $transaction, 
                'Payment failed: ' . ($request->input('error.message') ?? 'Unknown error')
            );
        }

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'status' => 'failed',
        ];
    }

    /**
     * Обработать возврат
     */
    protected function handleRefund(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $handler->getPaymentId($request);
        $refundAmount = $request->input('amount', 0) / 100; // Обычно в копейках

        // Найти оригинальный платеж
        $payment = Payment::where('external_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$payment) {
            return [
                'success' => true,
                'message' => 'Payment not found',
            ];
        }

        // Создать транзакцию возврата
        $this->transactionService->createRefundTransaction(
            $payment,
            $refundAmount,
            $request->input('reason', 'Refund processed via webhook')
        );

        // Обновить статус платежа
        if ($refundAmount >= $payment->amount) {
            $payment->update([
                'status' => PaymentStatus::REFUNDED,
                'refunded_at' => now(),
            ]);
        }

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'refund_amount' => $refundAmount,
        ];
    }

    /**
     * Обработать создание подписки
     */
    protected function handleSubscriptionCreated(WebhookHandlerInterface $handler, Request $request): array
    {
        $subscriptionData = $handler->handle($request);

        // Создать или обновить подписку
        $subscription = $this->subscriptionService->createSubscription(
            new \App\Domain\Payment\DTOs\CreateSubscriptionDTO($subscriptionData)
        );

        return [
            'success' => true,
            'subscription_id' => $subscription->id,
            'status' => 'created',
        ];
    }

    /**
     * Обработать обновление подписки
     */
    protected function handleSubscriptionUpdated(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $request->input('id');
        $status = $request->input('status');

        $subscription = \App\Domain\Payment\Models\Subscription::where('gateway_subscription_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$subscription) {
            return [
                'success' => true,
                'message' => 'Subscription not found',
            ];
        }

        // Обновить статус и другие данные
        $updateData = [
            'gateway_data' => $request->all(),
        ];

        if ($status) {
            $updateData['status'] = $this->mapSubscriptionStatus($status);
        }

        $subscription->update($updateData);

        return [
            'success' => true,
            'subscription_id' => $subscription->id,
            'status' => 'updated',
        ];
    }

    /**
     * Обработать отмену подписки
     */
    protected function handleSubscriptionCancelled(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $request->input('id');

        $subscription = \App\Domain\Payment\Models\Subscription::where('gateway_subscription_id', $externalId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if (!$subscription) {
            return [
                'success' => true,
                'message' => 'Subscription not found',
            ];
        }

        // Отменить подписку
        $this->subscriptionService->cancelSubscription(
            $subscription,
            'Cancelled via webhook',
            true
        );

        return [
            'success' => true,
            'subscription_id' => $subscription->id,
            'status' => 'cancelled',
        ];
    }

    /**
     * Обработать успешную оплату инвойса (для подписок)
     */
    protected function handleInvoicePaymentSucceeded(WebhookHandlerInterface $handler, Request $request): array
    {
        // Логика обработки успешной оплаты по подписке
        $subscriptionId = $request->input('subscription');
        $amount = $request->input('amount_paid', 0) / 100;

        $subscription = \App\Domain\Payment\Models\Subscription::where('gateway_subscription_id', $subscriptionId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if ($subscription) {
            // Создать транзакцию
            $this->transactionService->createSubscriptionTransaction(
                $subscription,
                $amount,
                'Автоматическая оплата подписки'
            );

            // Продлить подписку
            $this->subscriptionService->renewSubscription($subscription);
        }

        return [
            'success' => true,
            'message' => 'Invoice payment processed',
        ];
    }

    /**
     * Обработать неудачную оплату инвойса
     */
    protected function handleInvoicePaymentFailed(WebhookHandlerInterface $handler, Request $request): array
    {
        $subscriptionId = $request->input('subscription');

        $subscription = \App\Domain\Payment\Models\Subscription::where('gateway_subscription_id', $subscriptionId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if ($subscription) {
            // Обновить статус подписки
            $subscription->update([
                'status' => \App\Domain\Payment\Enums\SubscriptionStatus::PAST_DUE,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Invoice payment failure processed',
        ];
    }

    /**
     * Получить обработчик для платежной системы
     */
    protected function getHandler(string $gateway): WebhookHandlerInterface
    {
        if (!isset($this->handlers[$gateway])) {
            throw new \Exception("No webhook handler registered for gateway: {$gateway}");
        }

        return $this->handlers[$gateway];
    }

    /**
     * Зарегистрировать стандартные обработчики
     */
    protected function registerDefaultHandlers(): void
    {
        // Регистрация обработчиков будет выполнена в ServiceProvider
        // или через конфигурацию
    }

    /**
     * Преобразовать статус подписки из внешнего формата
     */
    protected function mapSubscriptionStatus(string $externalStatus): \App\Domain\Payment\Enums\SubscriptionStatus
    {
        return match($externalStatus) {
            'active' => \App\Domain\Payment\Enums\SubscriptionStatus::ACTIVE,
            'trialing' => \App\Domain\Payment\Enums\SubscriptionStatus::TRIALING,
            'past_due' => \App\Domain\Payment\Enums\SubscriptionStatus::PAST_DUE,
            'canceled', 'cancelled' => \App\Domain\Payment\Enums\SubscriptionStatus::CANCELLED,
            'incomplete' => \App\Domain\Payment\Enums\SubscriptionStatus::INCOMPLETE,
            'paused' => \App\Domain\Payment\Enums\SubscriptionStatus::PAUSED,
            default => \App\Domain\Payment\Enums\SubscriptionStatus::PENDING,
        };
    }
}