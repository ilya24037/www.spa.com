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
        $this->send($userId, 'payment_success', $paymentData);
    }

    /**
     * Отправить уведомление о неудачном платеже
     */
    public function sendPaymentFailed(int $userId, array $paymentData): void
    {
        $this->send($userId, 'payment_failed', $paymentData);
    }

    /**
     * Отправить уведомление о возврате средств
     */
    public function sendRefundProcessed(int $userId, array $refundData): void
    {
        $this->send($userId, 'refund_processed', $refundData);
    }

    /**
     * Отправить напоминание об истекающей подписке
     */
    public function sendSubscriptionExpiring(int $userId, array $subscriptionData): void
    {
        $this->send($userId, 'subscription_expiring', $subscriptionData);
    }

    /**
     * Отправить квитанцию по email
     */
    public function sendReceipt(int $userId, array $receiptData): void
    {
        $this->send($userId, 'payment_receipt', $receiptData, ['email']);
    }
}