<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик успешных платежей
 */
class PaymentProcessor
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Обработать успешный платеж
     */
    public function processSuccessfulPayment(Payment $payment): void
    {
        try {
            switch ($payment->purchase_type) {
                case 'ad_placement':
                    $this->activateAd($payment);
                    break;
                case 'balance_top_up':
                    $this->topUpUserBalance($payment);
                    break;
                case 'service_booking':
                    $this->confirmBooking($payment);
                    break;
            }

            $this->notificationService->sendPaymentCompleted($payment);

            Log::info('Payment processed successfully', ['payment_id' => $payment->payment_id]);

        } catch (\Exception $e) {
            Log::error('Error processing successful payment', [
                'payment_id' => $payment->payment_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Активировать объявление после оплаты
     */
    private function activateAd(Payment $payment): void
    {
        if ($payment->ad && $payment->adPlan) {
            $payment->ad->update([
                'status' => 'active',
                'is_paid' => true,
                'paid_at' => now(),
                'expires_at' => now()->addDays($payment->adPlan->days)
            ]);
        }
    }

    /**
     * Пополнить баланс пользователя
     */
    private function topUpUserBalance(Payment $payment): void
    {
        $balance = $payment->user->getBalance();
        $finalAmount = $payment->metadata['final_amount'] ?? $payment->amount;
        $balance->addFunds($finalAmount);
    }

    /**
     * Подтвердить бронирование после оплаты
     */
    private function confirmBooking(Payment $payment): void
    {
        if ($payment->booking) {
            $payment->booking->update([
                'payment_status' => 'paid',
                'status' => 'confirmed'
            ]);
        }
    }
}