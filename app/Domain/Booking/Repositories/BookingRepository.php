<?php

namespace App\Domain\Booking\Repositories;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Handlers\BookingCrudHandler;
use App\Domain\Booking\Handlers\BookingSearchHandler;
use App\Domain\Booking\Handlers\BookingCalendarHandler;
use App\Domain\Booking\Handlers\BookingAnalyticsHandler;
use App\Domain\Common\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Упрощенный репозиторий бронирований
 * Делегирует логику специализированным обработчикам
 * 
 * @extends BaseRepository<Booking>
 */
class BookingRepository extends BaseRepository
{
    protected BookingCrudHandler $crudHandler;
    protected BookingSearchHandler $searchHandler;
    protected BookingCalendarHandler $calendarHandler;
    protected BookingAnalyticsHandler $analyticsHandler;

    /**
     * Получить класс модели
     */
    protected function getModelClass(): string
    {
        return Booking::class;
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->crudHandler = new BookingCrudHandler($this->model);
        $this->searchHandler = new BookingSearchHandler($this->model);
        $this->calendarHandler = new BookingCalendarHandler($this->model);
        $this->analyticsHandler = new BookingAnalyticsHandler($this->model);
    }

    // === CRUD ОПЕРАЦИИ ===

    /**
     * Найти бронирование по ID с загрузкой связей
     */
    public function find(int $id): ?Booking
    {
        return $this->crudHandler->find($id);
    }

    /**
     * Найти бронирование по ID или выбросить исключение
     */
    public function findOrFail(int $id): Booking
    {
        return $this->crudHandler->findOrFail($id);
    }

    public function findByNumber(string $bookingNumber): ?Booking
    {
        return $this->crudHandler->findByNumber($bookingNumber);
    }

    public function findWithRelations(int $bookingId): ?Booking
    {
        return $this->crudHandler->findWithRelations($bookingId);
    }

    public function create(array $data): Booking
    {
        return $this->crudHandler->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->crudHandler->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->crudHandler->delete($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->crudHandler->forceDelete($id);
    }

    public function restore(int $id): bool
    {
        return $this->crudHandler->restore($id);
    }

    public function validateBookingRequest(int $masterProfileId, int $serviceId): array
    {
        return $this->crudHandler->validateBookingRequest($masterProfileId, $serviceId);
    }

    public function batchUpdateStatus(array $ids, $status): int
    {
        return $this->crudHandler->batchUpdateStatus($ids, $status);
    }

    public function markReminderSent(int $bookingId): bool
    {
        return $this->crudHandler->markReminderSent($bookingId);
    }

    // === ПОИСК И ФИЛЬТРАЦИЯ ===

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->searchHandler->search($filters, $perPage);
    }

    public function getBookingsForUser($user, int $perPage = 10)
    {
        return $this->searchHandler->getBookingsForUser($user, $perPage);
    }

    public function findForClient(int $clientId, array $filters = []): Collection
    {
        return $this->searchHandler->findForClient($clientId, $filters);
    }

    public function findForMaster(int $masterId, array $filters = []): Collection
    {
        return $this->searchHandler->findForMaster($masterId, $filters);
    }

    public function findOverlapping(Carbon $startTime, Carbon $endTime, int $masterId, ?int $excludeId = null): Collection
    {
        return $this->searchHandler->findOverlapping($startTime, $endTime, $masterId, $excludeId);
    }

    public function getNeedsReminder(): Collection
    {
        return $this->searchHandler->getNeedsReminder();
    }

    public function getOverdue(): Collection
    {
        return $this->searchHandler->getOverdue();
    }

    public function getExpiringSoon(int $hours = 24): Collection
    {
        return $this->searchHandler->getExpiringSoon($hours);
    }

    public function optimizeQuery()
    {
        return $this->searchHandler->optimizeQuery();
    }

    // === КАЛЕНДАРНЫЕ ФУНКЦИИ ===

    public function getUpcoming(int $limit = 10): Collection
    {
        return $this->calendarHandler->getUpcoming($limit);
    }

    public function getUpcomingForMaster(int $masterId, int $limit = 10): Collection
    {
        return $this->calendarHandler->getUpcomingForMaster($masterId, $limit);
    }

    public function getUpcomingForClient(int $clientId, int $limit = 10): Collection
    {
        return $this->calendarHandler->getUpcomingForClient($clientId, $limit);
    }

    public function getTodayBookings(?int $masterId = null): Collection
    {
        return $this->calendarHandler->getTodayBookings($masterId);
    }

    public function getBookingsForDate(Carbon $date, ?int $masterId = null): Collection
    {
        return $this->calendarHandler->getBookingsForDate($date, $masterId);
    }

    public function getBookingsForDateRange(Carbon $startDate, Carbon $endDate, ?int $masterId = null): Collection
    {
        return $this->calendarHandler->getBookingsForDateRange($startDate, $endDate, $masterId);
    }

    public function getBookingCalendar(Carbon $startDate, Carbon $endDate, ?int $masterId = null): array
    {
        return $this->calendarHandler->getBookingCalendar($startDate, $endDate, $masterId);
    }

    // === АНАЛИТИКА И СТАТИСТИКА ===

    public function getStatistics(array $filters = []): array
    {
        return $this->analyticsHandler->getStatistics($filters);
    }

    public function getRevenueByMonth(int $year, ?int $masterId = null): Collection
    {
        return $this->analyticsHandler->getRevenueByMonth($year, $masterId);
    }

    public function getPopularServices(int $limit = 10, ?int $masterId = null): Collection
    {
        return $this->analyticsHandler->getPopularServices($limit, $masterId);
    }

    public function getFrequentClients(int $limit = 10, ?int $masterId = null): Collection
    {
        return $this->analyticsHandler->getFrequentClients($limit, $masterId);
    }

    public function getDetailedAnalytics(array $filters = []): array
    {
        return $this->analyticsHandler->getDetailedAnalytics($filters);
    }

    // === ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ ===

    /**
     * Получить доступность мастера на дату
     */
    public function getMasterAvailability(int $masterId, Carbon $date): array
    {
        return $this->calendarHandler->getMasterAvailability($masterId, $date);
    }

    /**
     * Получить статистику занятости по дням недели
     */
    public function getWeeklyOccupancy(int $masterId, Carbon $startDate): array
    {
        return $this->calendarHandler->getWeeklyOccupancy($masterId, $startDate);
    }

    /**
     * Дублировать бронирование
     */
    public function duplicate(int $id, array $overrideData = []): ?Booking
    {
        return $this->crudHandler->duplicate($id, $overrideData);
    }

    /**
     * Проверить уникальность номера бронирования
     */
    public function isBookingNumberUnique(string $bookingNumber, ?int $excludeId = null): bool
    {
        return $this->crudHandler->isBookingNumberUnique($bookingNumber, $excludeId);
    }
}