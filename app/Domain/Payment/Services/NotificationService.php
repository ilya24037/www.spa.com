<?php

namespace App\Domain\Payment\Services;

use App\Infrastructure\Notification\NotificationService as BaseNotificationService;

/**
 * Сервис уведомлений для платежей
 * Расширяет базовый функционал для специфичных платежных уведомлений
 */
class NotificationService extends BaseNotificationService
{
    /**
     * Отправить уведомление об успешном платеже
     */
    public function sendPaymentSuccess(int $userId, array $paymentData): void
    {
        $user = \App\Domain\User\Models\User::find($userId);
        if ($user) {
            $this->sendByTemplate($user, 'payment_success', $paymentData);
        }
    }

    /**
     * Отправить уведомление о неудачном платеже
     */
    public function sendPaymentFailed(int $userId, array $paymentData): void
    {
        $user = \App\Domain\User\Models\User::find($userId);
        if ($user) {
            $this->sendByTemplate($user, 'payment_failed', $paymentData);
        }
    }

    /**
     * Отправить уведомление о возврате средств
     */
    public function sendRefundProcessed(int $userId, array $refundData): void
    {
        $user = \App\Domain\User\Models\User::find($userId);
        if ($user) {
            $this->sendByTemplate($user, 'refund_processed', $refundData);
        }
    }

    /**
     * Отправить напоминание об истекающей подписке
     */
    public function sendSubscriptionExpiring(int $userId, array $subscriptionData): void
    {
        $user = \App\Domain\User\Models\User::find($userId);
        if ($user) {
            $this->sendByTemplate($user, 'subscription_expiring', $subscriptionData);
        }
    }

    /**
     * Отправить квитанцию по email
     */
    public function sendReceipt(int $userId, array $receiptData): void
    {
        $user = \App\Domain\User\Models\User::find($userId);
        if ($user) {
            $this->sendByTemplate($user, 'payment_receipt', $receiptData);
        }
    }
}