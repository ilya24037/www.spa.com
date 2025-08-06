<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Services\PaymentGatewayService;
use App\Infrastructure\Notification\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки платежей при завершении бронирования
 */
class BookingPaymentProcessorService
{
    public function __construct(
        private PaymentGatewayService $paymentService,
        private NotificationService $notificationService
    ) {}

    /**
     * Обработка оплаты
     */
    public function processPayment(Booking $booking, array $options): void
    {
        $remainingAmount = $this->calculateRemainingAmount($booking);
        
        if ($remainingAmount <= 0) {
            $booking->update(['payment_status' => 'paid']);
            return;
        }

        if ($options['auto_charge'] ?? false) {
            $this->attemptAutoCharge($booking, $remainingAmount);
        } else {
            $this->sendPaymentLink($booking, $remainingAmount);
        }
    }

    /**
     * Вычислить остаток к доплате
     */
    private function calculateRemainingAmount(Booking $booking): float
    {
        return $booking->total_price - ($booking->paid_amount ?? 0);
    }

    /**
     * Попытка автоматического списания
     */
    private function attemptAutoCharge(Booking $booking, float $amount): void
    {
        try {
            $chargeResult = $this->paymentService->chargeRemaining($booking, $amount);
            
            if ($chargeResult['success']) {
                $booking->update([
                    'paid_amount' => $booking->total_price,
                    'payment_status' => 'paid',
                ]);
                
                Log::info('Remaining amount charged successfully', [
                    'booking_id' => $booking->id,
                    'charged_amount' => $amount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to charge remaining amount', [
                'booking_id' => $booking->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить ссылку на доплату
     */
    private function sendPaymentLink(Booking $booking, float $amount): void
    {
        try {
            $paymentLink = $this->paymentService->createRemainingPaymentLink($booking, $amount);
            
            if ($paymentLink) {
                $this->notificationService->sendRemainingPaymentLink($booking, $paymentLink);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create remaining payment link', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}