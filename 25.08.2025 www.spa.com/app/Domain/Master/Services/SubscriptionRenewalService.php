<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionStatus;

/**
 * Сервис управления продлением и активацией подписок
 */
class SubscriptionRenewalService
{
    /**
     * Активировать подписку после оплаты
     */
    public function activate(
        MasterSubscription $subscription,
        array $paymentData = []
    ): void {
        if ($subscription->status === SubscriptionStatus::ACTIVE) {
            return; // Уже активна
        }

        // Обновляем платежные данные
        if (!empty($paymentData)) {
            $subscription->update([
                'payment_method' => $paymentData['method'] ?? $subscription->payment_method,
                'transaction_id' => $paymentData['transaction_id'] ?? $subscription->transaction_id,
                'metadata' => array_merge($subscription->metadata ?? [], $paymentData['metadata'] ?? []),
            ]);
        }

        // Активируем
        $subscription->activate();

        $subscription->logHistory('activated', 'Подписка активирована после оплаты');
    }

    /**
     * Продлить подписку
     */
    public function renew(
        MasterSubscription $subscription,
        int $periodMonths = null,
        array $paymentData = []
    ): void {
        $periodMonths = $periodMonths ?? $subscription->period_months;
        
        // Обновляем цену
        $newPrice = $subscription->plan->calculateTotal($periodMonths);
        $subscription->price = $newPrice;

        // Обновляем платежные данные
        if (!empty($paymentData)) {
            $subscription->update([
                'payment_method' => $paymentData['method'] ?? $subscription->payment_method,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'metadata' => array_merge($subscription->metadata ?? [], $paymentData['metadata'] ?? []),
            ]);
        }

        // Продлеваем
        $subscription->renew($periodMonths);

        $subscription->logHistory('renewed', "Подписка продлена на {$periodMonths} мес.");
    }

    /**
     * Автопродление подписки
     */
    public function autoRenew(MasterSubscription $subscription): bool
    {
        if (!$subscription->auto_renew) {
            return false;
        }

        if (!$this->canAutoRenew($subscription)) {
            return false;
        }

        try {
            // Попытка списать оплату
            $paymentResult = $this->processAutoRenewalPayment($subscription);
            
            if ($paymentResult['success']) {
                $this->renew($subscription, null, $paymentResult['payment_data']);
                return true;
            }

            // Если не удалось списать, помечаем как проблемную
            $subscription->logHistory('auto_renewal_failed', 'Не удалось автоматически продлить подписку');
            
        } catch (\Exception $e) {
            $subscription->logHistory('auto_renewal_error', $e->getMessage());
        }

        return false;
    }

    /**
     * Получить информацию о продлении
     */
    public function getRenewalInfo(MasterSubscription $subscription): array
    {
        $daysUntilExpiry = $subscription->getDaysUntilExpiry();
        $canRenew = $this->canRenew($subscription);
        $renewalPrice = $subscription->plan->calculateTotal($subscription->period_months);

        return [
            'can_renew' => $canRenew,
            'days_until_expiry' => $daysUntilExpiry,
            'expires_at' => $subscription->getExpiryDate(),
            'auto_renew_enabled' => $subscription->auto_renew,
            'renewal_price' => $renewalPrice,
            'payment_method' => $subscription->payment_method,
            'needs_payment_update' => $this->needsPaymentUpdate($subscription),
        ];
    }

    /**
     * Проверить, можно ли продлить подписку
     */
    public function canRenew(MasterSubscription $subscription): bool
    {
        return in_array($subscription->status, [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::EXPIRED,
        ]);
    }

    /**
     * Проверить, можно ли автоматически продлить
     */
    private function canAutoRenew(MasterSubscription $subscription): bool
    {
        return $this->canRenew($subscription) && 
               $subscription->payment_method && 
               $subscription->getDaysUntilExpiry() <= 3;
    }

    /**
     * Обработать автоматический платеж за продление
     */
    private function processAutoRenewalPayment(MasterSubscription $subscription): array
    {
        // Здесь должна быть интеграция с платежной системой
        // Возвращаем заглушку
        return [
            'success' => true,
            'payment_data' => [
                'method' => $subscription->payment_method,
                'transaction_id' => 'auto_' . time(),
                'metadata' => ['auto_renewal' => true],
            ],
        ];
    }

    /**
     * Проверить, нужно ли обновить платежные данные
     */
    private function needsPaymentUpdate(MasterSubscription $subscription): bool
    {
        // Проверяем срок действия карты, если есть
        $metadata = $subscription->metadata ?? [];
        
        if (isset($metadata['card_expires'])) {
            $cardExpires = \Carbon\Carbon::createFromFormat('m/y', $metadata['card_expires']);
            return $cardExpires->isPast();
        }

        return false;
    }
}