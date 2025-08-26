<?php

namespace App\Domain\Booking\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Domain\Booking\Models\Booking;

/**
 * Интерфейс репозитория бронирований
 * Для развязки доменов и тестирования
 */
interface BookingRepositoryInterface
{
    /**
     * Получить все бронирования пользователя
     */
    public function getUserBookings(int $userId): Collection;

    /**
     * Получить активные бронирования пользователя
     */
    public function getActiveUserBookings(int $userId): Collection;

    /**
     * Получить завершенные бронирования пользователя
     */
    public function getCompletedUserBookings(int $userId): Collection;

    /**
     * Получить бронирования мастера
     */
    public function getMasterBookings(int $masterId): Collection;

    /**
     * Получить активные бронирования мастера
     */
    public function getActiveMasterBookings(int $masterId): Collection;

    /**
     * Создать новое бронирование
     */
    public function create(array $data): Booking;

    /**
     * Обновить статус бронирования
     */
    public function updateStatus(int $bookingId, string $status): bool;

    /**
     * Получить бронирование по ID
     */
    public function findById(int $bookingId): ?Booking;

    /**
     * Получить статистики бронирований пользователя
     */
    public function getUserBookingStats(int $userId): array;

    /**
     * Получить статистики бронирований мастера
     */
    public function getMasterBookingStats(int $masterId): array;

    /**
     * Проверить есть ли активные бронирования у пользователя
     */
    public function hasActiveBookings(int $userId): bool;

    /**
     * Получить последнее бронирование пользователя
     */
    public function getLastUserBooking(int $userId): ?Booking;

    /**
     * Удалить все бронирования пользователя
     */
    public function deleteUserBookings(int $userId): int;

    /**
     * Получить количество бронирований пользователя
     */
    public function getUserBookingsCount(int $userId): int;
}