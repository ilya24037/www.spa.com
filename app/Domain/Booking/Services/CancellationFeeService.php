<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use Carbon\Carbon;

/**
 * Сервис расчета комиссии за отмену бронирования
 */
class CancellationFeeService
{
    /**
     * Рассчитать комиссию за отмену
     */
    public function calculateFee(Booking $booking): float
    {
        $now = Carbon::now();
        $bookingStart = Carbon::parse($booking->start_time);
        
        // Если отмена за 24 часа до начала - без комиссии
        if ($now->diffInHours($bookingStart) >= 24) {
            return 0.0;
        }
        
        // Если отмена за 2-24 часа - 50% комиссия
        if ($now->diffInHours($bookingStart) >= 2) {
            return $booking->total_amount * 0.5;
        }
        
        // Если отмена менее чем за 2 часа - 100% комиссия
        return $booking->total_amount;
    }

    /**
     * Получить информацию о комиссии
     */
    public function getFeeInfo(Booking $booking): array
    {
        $fee = $this->calculateFee($booking);
        $refund = $booking->total_amount - $fee;
        
        return [
            'total_amount' => $booking->total_amount,
            'cancellation_fee' => $fee,
            'refund_amount' => $refund,
            'fee_percentage' => $fee > 0 ? round(($fee / $booking->total_amount) * 100, 2) : 0
        ];
    }
}
