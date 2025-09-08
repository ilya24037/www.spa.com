<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use Carbon\Carbon;

/**
 * Сервис валидации отмены бронирования
 */
class CancellationValidationService
{
    /**
     * Проверить, можно ли отменить бронирование
     */
    public function canCancel(Booking $booking): bool
    {
        // Бронирование можно отменить только если оно не завершено
        if ($booking->status === 'completed' || $booking->status === 'cancelled') {
            return false;
        }

        // Проверяем, не прошло ли слишком много времени
        $now = Carbon::now();
        $bookingStart = Carbon::parse($booking->start_time);
        
        // Можно отменить за 2 часа до начала
        $cancellationDeadline = $bookingStart->subHours(2);
        
        return $now->isBefore($cancellationDeadline);
    }

    /**
     * Получить причину, по которой нельзя отменить
     */
    public function getCancellationReason(Booking $booking): ?string
    {
        if ($booking->status === 'completed') {
            return 'Бронирование уже завершено';
        }

        if ($booking->status === 'cancelled') {
            return 'Бронирование уже отменено';
        }

        $now = Carbon::now();
        $bookingStart = Carbon::parse($booking->start_time);
        $cancellationDeadline = $bookingStart->subHours(2);
        
        if ($now->isAfter($cancellationDeadline)) {
            return 'Слишком поздно для отмены (менее 2 часов до начала)';
        }

        return null;
    }
}
