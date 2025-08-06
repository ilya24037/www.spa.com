<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\Log;

/**
 * Сервис валидации завершения бронирований
 */
class BookingCompletionValidationService
{
    /**
     * Валидация возможности завершения
     */
    public function validateCompletion(Booking $booking, User $master): void
    {
        $this->validateMasterPermissions($booking, $master);
        $this->validateBookingStatus($booking);
        $this->validateCompletionTime($booking);
    }

    /**
     * Проверка прав мастера
     */
    public function validateMasterPermissions(Booking $booking, User $master): void
    {
        $canComplete = $booking->master_id === $master->id ||
                      ($booking->master_profile_id && $master->masterProfile && 
                       $booking->master_profile_id === $master->masterProfile->id) ||
                      $master->hasRole('admin');

        if (!$canComplete) {
            throw new \Exception('У вас нет прав для завершения этого бронирования');
        }
    }

    /**
     * Проверка статуса бронирования
     */
    public function validateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canComplete()) {
                throw new \Exception(
                    "Нельзя завершить бронирование в статусе: {$booking->status->getLabel()}"
                );
            }
        } else {
            // Совместимость со старым кодом
            if (!in_array($booking->status, [
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_IN_PROGRESS
            ])) {
                throw new \Exception('Можно завершить только подтвержденные или выполняющиеся бронирования');
            }
        }
    }

    /**
     * Проверка времени завершения
     */
    public function validateCompletionTime(Booking $booking): void
    {
        // Можно завершить только после начала или в процессе выполнения
        if ($booking->start_time->isFuture()) {
            // Разрешаем завершение за 15 минут до начала (для подготовки)
            if ($booking->start_time->diffInMinutes(now()) > 15) {
                throw new \Exception('Нельзя завершить услугу до её начала');
            }
        }

        // Не должно быть слишком поздно (более 24 часов после окончания)
        if ($booking->end_time->isPast() && 
            $booking->end_time->diffInHours(now()) > 24) {
            Log::warning('Late completion attempt', [
                'booking_id' => $booking->id,
                'hours_after_end' => $booking->end_time->diffInHours(now()),
            ]);
        }
    }
}