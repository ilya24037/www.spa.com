<?php

namespace App\Domain\Payment\Handlers;

use App\Domain\Payment\Contracts\WebhookHandlerInterface;
use App\Domain\Payment\Handlers\Webhooks\PaymentWebhookProcessor;
use App\Domain\Payment\Handlers\Webhooks\SubscriptionWebhookProcessor;
use App\Domain\Payment\Services\PaymentService;
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

    protected PaymentWebhookProcessor $paymentProcessor;
    protected SubscriptionWebhookProcessor $subscriptionProcessor;

    public function __construct(
        protected PaymentService $paymentService,
        PaymentWebhookProcessor $paymentProcessor,
        SubscriptionWebhookProcessor $subscriptionProcessor
    ) {
        $this->paymentProcessor = $paymentProcessor;
        $this->subscriptionProcessor = $subscriptionProcessor;
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
            // Платежные события
            'payment.succeeded', 'charge.succeeded', 'payment_intent.succeeded' => 
                $this->paymentProcessor->handleSuccess($handler, $request),
            
            'payment.failed', 'charge.failed', 'payment_intent.payment_failed' => 
                $this->paymentProcessor->handleFailed($handler, $request),
            
            'payment.refunded', 'charge.refunded', 'refund.succeeded' => 
                $this->paymentProcessor->handleRefund($handler, $request),
            
            // События подписок
            'subscription.created', 'customer.subscription.created' => 
                $this->subscriptionProcessor->handleCreated($handler, $request),
            
            'subscription.updated', 'customer.subscription.updated' => 
                $this->subscriptionProcessor->handleUpdated($handler, $request),
            
            'subscription.deleted', 'customer.subscription.deleted' => 
                $this->subscriptionProcessor->handleCancelled($handler, $request),
            
            'invoice.payment_succeeded' => 
                $this->subscriptionProcessor->handleInvoicePaymentSucceeded($handler, $request),
            
            'invoice.payment_failed' => 
                $this->subscriptionProcessor->handleInvoicePaymentFailed($handler, $request),
            
            // Кастомная обработка
            default => $handler->handle($request),
        };
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
}