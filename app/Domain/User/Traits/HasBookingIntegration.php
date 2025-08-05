<?php

namespace App\Domain\User\Traits;

use App\Application\Services\Integration\UserBookingIntegrationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Трейт для интеграции с бронированиями через сервисы
 * Заменяет HasBookings трейт с прямыми связями
 * СОБЛЮДАЕТ DDD ПРИНЦИПЫ - нет прямых импортов моделей других доменов
 */
trait HasBookingIntegration
{
    /**
     * Получить все бронирования пользователя
     * Заменяет: $this->bookings()
     */
    public function getBookings(): Collection
    {
        return app(UserBookingIntegrationService::class)->getUserBookings($this->id);
    }

    /**
     * Получить активные бронирования пользователя
     * Заменяет: $this->activeBookings()
     */
    public function getActiveBookings(): Collection
    {
        return app(UserBookingIntegrationService::class)->getActiveUserBookings($this->id);
    }

    /**
     * Получить завершенные бронирования пользователя
     * Заменяет: $this->completedBookings()
     */
    public function getCompletedBookings(): Collection
    {
        return app(UserBookingIntegrationService::class)->getCompletedUserBookings($this->id);
    }

    /**
     * Проверить есть ли активные бронирования
     * Заменяет: $this->hasActiveBookings()
     */
    public function hasActiveBookings(): bool
    {
        return app(UserBookingIntegrationService::class)->hasActiveBookings($this->id);
    }

    /**
     * Получить последнее бронирование пользователя
     * Заменяет: $this->lastBooking()
     */
    public function getLastBooking()
    {
        return app(UserBookingIntegrationService::class)->getLastUserBooking($this->id);
    }

    /**
     * Получить количество бронирований пользователя
     * Заменяет: $this->getTotalBookingsAttribute()
     */
    public function getBookingsCount(): int
    {
        return app(UserBookingIntegrationService::class)->getUserBookingsCount($this->id);
    }

    /**
     * Создать бронирование через событие
     * Заменяет: $this->bookings()->create($data)
     * 
     * ВАЖНО: Не создает напрямую, а отправляет событие!
     */
    public function requestBooking(int $masterId, array $bookingData): void
    {
        app(UserBookingIntegrationService::class)->createBookingForUser(
            $this->id, 
            $masterId, 
            $bookingData
        );
    }

    /**
     * Отменить все бронирования пользователя
     * Заменяет: $this->bookings()->delete()
     */
    public function cancelAllBookings(?string $reason = null): int
    {
        return app(UserBookingIntegrationService::class)->cancelAllUserBookings(
            $this->id, 
            $this->id, // отменяет сам пользователь
            $reason
        );
    }

    /**
     * Получить статистики бронирований пользователя
     * Новый метод для аналитики
     */
    public function getBookingStatistics(): array
    {
        return app(UserBookingIntegrationService::class)->getUserBookingStatistics($this->id);
    }

    /**
     * Проверить может ли пользователь создать бронирование
     * Новый метод с бизнес-логикой
     */
    public function canCreateBooking(int $masterId, array $bookingData): bool
    {
        return app(UserBookingIntegrationService::class)->canUserCreateBooking(
            $this->id, 
            $masterId, 
            $bookingData
        );
    }

    /**
     * Получить доступные слоты для бронирования
     * Интеграция с календарем мастера
     */
    public function getAvailableSlots(int $masterId, string $date): array
    {
        return app(UserBookingIntegrationService::class)->getAvailableSlotsForUser(
            $masterId, 
            $date
        );
    }

    /**
     * DEPRECATED методы для обратной совместимости
     * Постепенно удалим после рефакторинга всех вызовов
     */

    /**
     * @deprecated Используйте getBookings()
     */
    public function bookings()
    {
        return $this->getBookings();
    }

    /**
     * @deprecated Используйте getActiveBookings()
     */
    public function activeBookings()
    {
        return $this->getActiveBookings();
    }

    /**
     * @deprecated Используйте getCompletedBookings()
     */
    public function completedBookings()
    {
        return $this->getCompletedBookings();
    }

    /**
     * @deprecated Используйте getLastBooking()
     */
    public function lastBooking()
    {
        return $this->getLastBooking();
    }

    /**
     * @deprecated Используйте getBookingsCount()
     */
    public function getTotalBookingsAttribute(): int
    {
        return $this->getBookingsCount();
    }
}