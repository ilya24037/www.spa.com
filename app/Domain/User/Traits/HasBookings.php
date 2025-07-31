<?php

namespace App\Domain\User\Traits;

use App\Models\Booking;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Трейт для работы с бронированиями пользователя
 */
trait HasBookings
{
    /**
     * Бронирования клиента
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'client_id');
    }

    /**
     * Активные бронирования
     */
    public function activeBookings(): HasMany
    {
        return $this->bookings()
            ->whereIn('status', [
                BookingStatus::PENDING->value,
                BookingStatus::CONFIRMED->value
            ]);
    }

    /**
     * Завершенные бронирования
     */
    public function completedBookings(): HasMany
    {
        return $this->bookings()
            ->where('status', BookingStatus::COMPLETED->value);
    }

    /**
     * Проверка, есть ли активные бронирования
     */
    public function hasActiveBookings(): bool
    {
        return $this->activeBookings()->exists();
    }

    /**
     * Получить последнее бронирование
     */
    public function lastBooking()
    {
        return $this->bookings()->latest()->first();
    }

    /**
     * Общее количество бронирований
     */
    public function getTotalBookingsAttribute(): int
    {
        return $this->bookings()->count();
    }
}