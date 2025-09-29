<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use Carbon\Carbon;

/**
 * Сервис выполнения переноса бронирования
 */
class RescheduleExecutor
{
    public function execute(Booking $booking, Carbon $newDateTime): void
    {
        // TODO: Implement reschedule execution logic
        $booking->update([
            'scheduled_at' => $newDateTime,
            'status' => 'rescheduled'
        ]);
    }
}