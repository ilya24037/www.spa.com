<?php

namespace App\Http\Controllers;

use App\Application\Http\Controllers\Booking\BookingController as BaseBookingController;
use App\Application\Http\Controllers\Booking\BookingSlotController;
use Illuminate\Http\Request;

/**
 * Legacy BookingController для обратной совместимости
 * Делегирует вызовы в новые контроллеры
 */
class BookingController extends BaseBookingController
{
    private BookingSlotController $slotController;
    
    public function __construct($bookingService)
    {
        parent::__construct($bookingService);
        $this->slotController = app(BookingSlotController::class);
    }

    /**
     * API метод для получения доступных слотов
     */
    public function availableSlots(Request $request)
    {
        return $this->slotController->availableSlots($request);
    }
}