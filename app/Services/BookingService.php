<?php

namespace App\Services;

use App\Domain\Booking\Services\BookingService as DomainBookingService;

/**
 * Legacy-адаптер для BookingService
 * @deprecated Используйте App\Domain\Booking\Services\BookingService
 * 
 * Этот класс обеспечивает обратную совместимость для старого кода,
 * делегируя все вызовы новому доменному сервису
 */
class BookingService extends DomainBookingService
{
    // Все методы наследуются из доменного сервиса
    // Дополнительные методы для обратной совместимости можно добавить здесь
}