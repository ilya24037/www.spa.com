<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Ad\Models\Ad;

/**
 * Сервис авторизации платежных операций
 * Централизует проверки доступа к платежам
 */
class PaymentAuthorizationService
{
    /**
     * Проверить принадлежность платежа пользователю
     */
    public function authorizePaymentOwnership(Payment $payment, int $userId): void
    {
        if ($payment->user_id !== $userId) {
            abort(403, 'Доступ к платежу запрещен');
        }
    }

    /**
     * Проверить возможность обновления объявления
     */
    public function authorizeAdUpdate(Ad $ad, int $userId): void
    {
        if ($ad->user_id !== $userId) {
            abort(403, 'Доступ к объявлению запрещен');
        }
    }

    /**
     * Проверить статус платежа для обработки
     */
    public function validatePaymentForProcessing(Payment $payment): bool
    {
        return !$payment->isPaid();
    }

    /**
     * Проверить метод платежа для СБП
     */
    public function validateSbpPayment(Payment $payment): bool
    {
        return $payment->payment_method === 'sbp';
    }
}