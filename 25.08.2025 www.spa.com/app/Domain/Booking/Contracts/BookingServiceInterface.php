<?php

namespace App\Domain\Booking\Contracts;

use App\Domain\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

/**
 * Интерфейс сервиса бронирований
 * Для развязки доменов и инкапсуляции бизнес-логики
 */
interface BookingServiceInterface
{
    /**
     * Создать новое бронирование
     */
    public function createBooking(array $data): Booking;

    /**
     * Подтвердить бронирование
     */
    public function confirmBooking(int $bookingId): bool;

    /**
     * Отменить бронирование
     */
    public function cancelBooking(int $bookingId, int $cancelledBy, ?string $reason = null): bool;

    /**
     * Завершить бронирование
     */
    public function completeBooking(int $bookingId, array $completionData = []): bool;

    /**
     * Получить доступные слоты для бронирования
     */
    public function getAvailableSlots(int $masterId, string $date): array;

    /**
     * Проверить возможность создания бронирования
     */
    public function canCreateBooking(int $clientId, int $masterId, array $bookingData): bool;

    /**
     * Получить бронирования с фильтрами
     */
    public function getBookingsWithFilters(array $filters): Collection;

    /**
     * Отправить уведомления о бронировании
     */
    public function sendBookingNotifications(Booking $booking, string $eventType): void;

    /**
     * Получить статистику бронирований
     */
    public function getBookingStatistics(array $filters = []): array;

    /**
     * Валидировать данные бронирования
     */
    public function validateBookingData(array $data): array;
}