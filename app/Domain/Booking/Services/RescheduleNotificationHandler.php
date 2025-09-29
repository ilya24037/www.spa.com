<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;

/**
 * Сервис уведомлений о переносе бронирования
 */
class RescheduleNotificationHandler
{
    public function handle(Booking $booking): void
    {
        // TODO: Implement notification logic
        // Send notifications to master and client about rescheduling
    }
}