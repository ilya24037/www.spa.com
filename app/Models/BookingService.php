<?php

namespace App\Models;

use App\Domain\Booking\Models\BookingService as BaseBookingService;

/**
 * Legacy BookingService model for backward compatibility
 * @deprecated Use App\Domain\Booking\Models\BookingService instead
 */
class BookingService extends BaseBookingService
{
    // Все функциональность наследуется из Domain модели
}