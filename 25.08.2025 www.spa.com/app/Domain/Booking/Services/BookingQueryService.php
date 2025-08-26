<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Сервис запросов бронирований
 */
class BookingQueryService
{
    public function __construct(
        private BookingRepository $bookingRepository
    ) {}

    /**
     * Найти бронирование по номеру
     */
    public function findByNumber(string $bookingNumber): ?Booking
    {
        return $this->bookingRepository->findByNumber($bookingNumber);
    }

    /**
     * Найти бронирование с отношениями
     */
    public function findBookingWithRelations(int $bookingId): ?Booking
    {
        return $this->bookingRepository->findWithRelations($bookingId);
    }

    /**
     * Получить бронирования пользователя (универсальный метод)
     */
    public function getUserBookings(
        User $user,
        array $filters = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        return $this->bookingRepository->getUserBookings($user, $filters, $perPage);
    }

    /**
     * Получить бронирования клиента
     */
    public function getClientBookings(
        User $client,
        array $statuses = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $filters = ['statuses' => $statuses];
        return $this->bookingRepository->getClientBookings($client, $filters, $perPage);
    }

    /**
     * Получить бронирования мастера
     */
    public function getMasterBookings(
        User $master,
        array $statuses = [],
        int $perPage = 10
    ): LengthAwarePaginator {
        $filters = ['statuses' => $statuses];
        return $this->bookingRepository->getMasterBookings($master, $filters, $perPage);
    }

    /**
     * Получить бронирования для пользователя (клиент или мастер)
     */
    public function getBookingsForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        if ($user->isMaster()) {
            return $this->getMasterBookings($user, [], $perPage);
        }
        return $this->getClientBookings($user, [], $perPage);
    }

    /**
     * Получить предстоящие бронирования
     */
    public function getUpcomingBookings(User $user, int $days = 7): Collection
    {
        return $this->bookingRepository->getUpcomingBookings($user, $days);
    }

    /**
     * Получить прошедшие бронирования
     */
    public function getPastBookings(User $user, int $limit = 10): Collection
    {
        return $this->bookingRepository->getPastBookings($user, $limit);
    }
}