<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentType;

/**
 * Сервис обновления связанных сущностей при возврате
 */
class RefundEntitiesUpdateService
{
    /**
     * Обновить связанные сущности
     */
    public function updateRelatedEntities(Payment $refund, Payment $originalPayment): void
    {
        // Обновляем статус бронирования
        if ($originalPayment->payable_type === 'App\Domain\Booking\Models\Booking') {
            $this->updateBookingAfterRefund($originalPayment->payable, $refund);
        }

        // Обновляем статистику пользователя
        $this->updateUserStatistics($refund->user, $refund);

        // Обновляем статистику мастера
        if ($originalPayment->payable_type === 'App\Domain\Booking\Models\Booking') {
            $booking = $originalPayment->payable;
            if ($booking && $booking->master) {
                $this->updateMasterStatistics($booking->master, $refund);
            }
        }
    }

    /**
     * Обновить бронирование после возврата
     */
    private function updateBookingAfterRefund($booking, Payment $refund): void
    {
        if (!$booking) return;

        $originalPayment = $refund->parentPayment;

        if ($originalPayment->type === PaymentType::BOOKING_DEPOSIT) {
            $booking->update([
                'deposit_refunded' => true,
                'refund_amount' => $refund->amount,
                'status' => 'cancelled_by_client',
            ]);
        } elseif ($originalPayment->type === PaymentType::SERVICE_PAYMENT) {
            if ($originalPayment->isFullyRefunded()) {
                $booking->update([
                    'payment_status' => 'refunded',
                    'status' => 'cancelled_by_client',
                ]);
            } else {
                $booking->update([
                    'payment_status' => 'partially_refunded',
                ]);
            }
        }
    }

    /**
     * Обновить статистику пользователя
     */
    private function updateUserStatistics($user, Payment $refund): void
    {
        $user->increment('refunds_count');
        $user->increment('refunds_amount', $refund->amount);
    }

    /**
     * Обновить статистику мастера
     */
    private function updateMasterStatistics($master, Payment $refund): void
    {
        // Уменьшаем доходы мастера
        if ($master->balance) {
            $master->balance->decrement('amount', $refund->amount);
        }

        // Обновляем статистику
        $master->increment('cancelled_bookings_count');
    }
}