<?php

namespace App\Domain\Booking\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Интерфейс для запросов к бронированиям
 * Для чтения данных без изменения состояния (CQRS pattern)
 */
interface BookingQueryInterface
{
    /**
     * Получить бронирования пользователя с пагинацией
     */
    public function getUserBookingsPaginated(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Получить бронирования мастера с пагинацией
     */
    public function getMasterBookingsPaginated(int $masterId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Поиск бронирований по критериям
     */
    public function searchBookings(array $criteria): Collection;

    /**
     * Получить популярные услуги
     */
    public function getPopularServices(int $limit = 10): array;

    /**
     * Получить статистику по периодам
     */
    public function getBookingsByPeriod(string $period, array $filters = []): array;

    /**
     * Получить топ мастеров по бронированиям
     */
    public function getTopMastersByBookings(int $limit = 10): array;

    /**
     * Получить активных клиентов
     */
    public function getActiveClients(int $limit = 100): Collection;

    /**
     * Получить отчет по бронированиям
     */
    public function getBookingReport(array $filters): array;

    /**
     * Получить предстоящие бронирования
     */
    public function getUpcomingBookings(int $days = 7): Collection;

    /**
     * Получить просроченные бронирования
     */
    public function getOverdueBookings(): Collection;
}