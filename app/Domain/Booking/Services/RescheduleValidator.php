<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;

class RescheduleValidator
{
    public function canReschedule(Booking $booking): bool
    {
        // Check if booking can be rescheduled
        if ($booking->status === 'cancelled' || $booking->status === 'completed') {
            return false;
        }

        // Check if booking is not too close to the scheduled time
        $hoursUntilBooking = now()->diffInHours($booking->scheduled_at);
        return $hoursUntilBooking >= 24;
    }

    public function validateNewTime(\DateTime $newTime, Booking $booking): bool
    {
        // Validate that new time is in the future
        if ($newTime < now()) {
            return false;
        }

        // Validate that new time is not too soon
        $hoursUntilNewTime = now()->diffInHours($newTime);
        return $hoursUntilNewTime >= 2;
    }
}