<?php

namespace App\Domain\Payment\Handlers\Webhooks;

use App\Domain\Payment\Contracts\WebhookHandlerInterface;
use App\Domain\Payment\DTOs\CreateSubscriptionDTO;
use App\Domain\Payment\Models\Subscription;
use App\Domain\Payment\Services\SubscriptionService;
use App\Domain\Payment\Services\TransactionService;
use App\Domain\Payment\Enums\SubscriptionStatus;
use Illuminate\Http\Request;

/**
 * Обработчик вебхуков подписок
 */
class SubscriptionWebhookProcessor
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TransactionService $transactionService
    ) {}

    /**
     * Обработать создание подписки
     */
    public function handleCreated(WebhookHandlerInterface $handler, Request $request): array
    {
        $subscriptionData = $handler->handle($request);

        // Создать или обновить подписку
        $subscription = $this->subscriptionService->createSubscription(
            new CreateSubscriptionDTO($subscriptionData)
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
    public function handleUpdated(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $request->input('id');
        $status = $request->input('status');

        $subscription = Subscription::where('gateway_subscription_id', $externalId)
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
    public function handleCancelled(WebhookHandlerInterface $handler, Request $request): array
    {
        $externalId = $request->input('id');

        $subscription = Subscription::where('gateway_subscription_id', $externalId)
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
    public function handleInvoicePaymentSucceeded(WebhookHandlerInterface $handler, Request $request): array
    {
        $subscriptionId = $request->input('subscription');
        $amount = $request->input('amount_paid', 0) / 100;

        $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)
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
    public function handleInvoicePaymentFailed(WebhookHandlerInterface $handler, Request $request): array
    {
        $subscriptionId = $request->input('subscription');

        $subscription = Subscription::where('gateway_subscription_id', $subscriptionId)
            ->where('gateway', $handler->getGatewayName())
            ->first();

        if ($subscription) {
            // Обновить статус подписки
            $subscription->update([
                'status' => SubscriptionStatus::PAST_DUE,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Invoice payment failure processed',
        ];
    }

    /**
     * Преобразовать статус подписки из внешнего формата
     */
    protected function mapSubscriptionStatus(string $externalStatus): SubscriptionStatus
    {
        return match($externalStatus) {
            'active' => SubscriptionStatus::ACTIVE,
            'trialing' => SubscriptionStatus::TRIALING,
            'past_due' => SubscriptionStatus::PAST_DUE,
            'canceled', 'cancelled' => SubscriptionStatus::CANCELLED,
            'incomplete' => SubscriptionStatus::INCOMPLETE,
            'paused' => SubscriptionStatus::PAUSED,
            default => SubscriptionStatus::PENDING,
        };
    }
}