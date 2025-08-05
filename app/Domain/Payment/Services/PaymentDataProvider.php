<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Domain\Ad\Models\Ad;

/**
 * Провайдер данных для платежных операций
 * Подготавливает данные для отображения в контроллерах
 */
class PaymentDataProvider
{
    /**
     * Получить тарифы пополнения баланса
     */
    public function getTopUpPlans(): array
    {
        return [
            ['amount' => 1000, 'price' => 950, 'discount' => 5],
            ['amount' => 2000, 'price' => 1750, 'discount' => 12.5],
            ['amount' => 5000, 'price' => 3750, 'discount' => 25],
            ['amount' => 10000, 'price' => 7500, 'discount' => 25],
            ['amount' => 15000, 'price' => 11000, 'discount' => 27]
        ];
    }

    /**
     * Подготовить данные платежа для view
     */
    public function preparePaymentData(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_id' => $payment->payment_id,
            'amount' => $payment->amount,
            'formatted_amount' => $payment->formatted_amount,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->format('d.m.Y H:i')
        ];
    }

    /**
     * Подготовить данные объявления для view
     */
    public function prepareAdData(Ad $ad): array
    {
        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'price' => $ad->price ?? null,
            'formatted_price' => $ad->formatted_price ?? null,
            'address' => $ad->address ?? null,
            'created_at' => $ad->created_at,
            'expires_at' => $ad->expires_at?->format('d.m.Y')
        ];
    }

    /**
     * Подготовить данные баланса пользователя
     */
    public function prepareBalanceData($balance): array
    {
        return [
            'rub_balance' => $balance->rub_balance,
            'formatted_balance' => $balance->formatted_balance,
            'loyalty_level' => $balance->loyalty_level,
            'loyalty_discount_percent' => $balance->loyalty_discount_percent
        ];
    }
}