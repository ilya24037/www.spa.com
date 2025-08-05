<?php

namespace App\Application\Services\Integration;

use App\Domain\Booking\Contracts\BookingRepositoryInterface;
use App\Domain\Booking\Contracts\BookingServiceInterface;
use App\Domain\Booking\Events\BookingRequested;
use App\Domain\Booking\Events\BookingCancelled;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

/**
 * Сервис интеграции User ↔ Booking доменов
 * Заменяет прямые связи через трейт HasBookings
 */
class UserBookingIntegrationService
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private BookingServiceInterface $bookingService
    ) {}

    /**
     * Получить все бронирования пользователя
     * Заменяет: $user->bookings()
     */
    public function getUserBookings(int $userId): Collection
    {
        return $this->bookingRepository->getUserBookings($userId);
    }

    /**
     * Получить активные бронирования пользователя
     * Заменяет: $user->activeBookings()
     */
    public function getActiveUserBookings(int $userId): Collection
    {
        return $this->bookingRepository->getActiveUserBookings($userId);
    }

    /**
     * Получить завершенные бронирования пользователя
     * Заменяет: $user->completedBookings()
     */
    public function getCompletedUserBookings(int $userId): Collection
    {
        return $this->bookingRepository->getCompletedUserBookings($userId);
    }

    /**
     * Проверить есть ли активные бронирования
     * Заменяет: $user->hasActiveBookings()
     */
    public function hasActiveBookings(int $userId): bool
    {
        return $this->bookingRepository->hasActiveBookings($userId);
    }

    /**
     * Получить последнее бронирование пользователя
     * Заменяет: $user->lastBooking()
     */
    public function getLastUserBooking(int $userId)
    {
        return $this->bookingRepository->getLastUserBooking($userId);
    }

    /**
     * Получить количество бронирований пользователя
     * Заменяет: $user->getTotalBookingsAttribute()
     */
    public function getUserBookingsCount(int $userId): int
    {
        return $this->bookingRepository->getUserBookingsCount($userId);
    }

    /**
     * Создать бронирование через событие
     * Заменяет: $user->bookings()->create($data)
     */
    public function createBookingForUser(int $userId, int $masterId, array $bookingData): void
    {
        // Отправляем событие вместо прямого создания
        Event::dispatch(new BookingRequested($userId, $masterId, $bookingData));
    }

    /**
     * Отменить все бронирования пользователя
     * Заменяет: $user->bookings()->delete()
     */
    public function cancelAllUserBookings(int $userId, int $cancelledBy, ?string $reason = null): int
    {
        $activeBookings = $this->getActiveUserBookings($userId);
        $cancelledCount = 0;

        foreach ($activeBookings as $booking) {
            Event::dispatch(new BookingCancelled(
                $booking->id,
                $userId,
                $booking->master_id,
                $cancelledBy,
                $reason,
                $booking->toArray()
            ));
            $cancelledCount++;
        }

        return $cancelledCount;
    }

    /**
     * Получить статистики бронирований пользователя
     * Для аналитики и дашборда
     */
    public function getUserBookingStatistics(int $userId): array
    {
        return $this->bookingRepository->getUserBookingStats($userId);
    }

    /**
     * Проверить может ли пользователь создать бронирование
     * Бизнес-логика проверки лимитов и ограничений
     */
    public function canUserCreateBooking(int $userId, int $masterId, array $bookingData): bool
    {
        return $this->bookingService->canCreateBooking($userId, $masterId, $bookingData);
    }

    /**
     * Получить доступные слоты для пользователя
     * Интеграция с календарем мастера
     */
    public function getAvailableSlotsForUser(int $masterId, string $date): array
    {
        return $this->bookingService->getAvailableSlots($masterId, $date);
    }
}