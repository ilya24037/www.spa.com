<?php

namespace App\Models;

use App\Domain\Booking\Models\Booking as BaseBooking;

/**
 * Legacy Booking model for backward compatibility
 * @deprecated Use App\Domain\Booking\Models\Booking instead
 */
class Booking extends BaseBooking
{
    // Все функциональность наследуется из Domain модели
}