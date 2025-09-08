<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Gateways\PaymentGatewayManager;
use Illuminate\Support\Facades\Log;

/**
 * Сервис возврата средств за бронирование
 */
class BookingRefundService
{
    public function __construct(
        private PaymentService $paymentService,
        private PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Выполнить возврат средств
     */
    public function processRefund(Booking $booking, float $amount): bool
    {
        try {
            // Создаем возврат через платежную систему
            $refund = $this->paymentService->createRefund([
                'payment_id' => $booking->payment_id,
                'amount' => $amount,
                'reason' => 'Booking cancellation'
            ]);

            if ($refund) {
                Log::info('Refund processed successfully', [
                    'booking_id' => $booking->id,
                    'amount' => $amount,
                    'refund_id' => $refund->id
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'booking_id' => $booking->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить статус возврата
     */
    public function getRefundStatus(Booking $booking): ?string
    {
        if (!$booking->payment_id) {
            return null;
        }

        // Здесь должна быть логика получения статуса возврата
        // из платежной системы
        return 'pending';
    }
}
