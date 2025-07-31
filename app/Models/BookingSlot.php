<?php

namespace App\Models;

use App\Domain\Booking\Models\BookingSlot as BaseBookingSlot;

/**
 * Legacy BookingSlot model for backward compatibility
 * @deprecated Use App\Domain\Booking\Models\BookingSlot instead
 */
class BookingSlot extends BaseBookingSlot
{
    // Все функциональность наследуется из Domain модели
}