<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use Carbon\Carbon;

/**
 * Сервис валидации завершения бронирования
 */
class BookingCompletionValidationService
{
    /**
     * Проверить, можно ли завершить бронирование
     */
    public function canComplete(Booking $booking): bool
    {
        // Бронирование можно завершить только если оно активное
        if ($booking->status !== 'confirmed' && $booking->status !== 'in_progress') {
            return false;
        }

        // Проверяем, что время бронирования уже прошло
        $now = Carbon::now();
        $bookingEnd = Carbon::parse($booking->end_time);
        
        return $now->isAfter($bookingEnd);
    }

    /**
     * Получить причину, по которой нельзя завершить
     */
    public function getCompletionReason(Booking $booking): ?string
    {
        if ($booking->status === 'completed') {
            return 'Бронирование уже завершено';
        }

        if ($booking->status === 'cancelled') {
            return 'Бронирование отменено';
        }

        if ($booking->status !== 'confirmed' && $booking->status !== 'in_progress') {
            return 'Неверный статус бронирования';
        }

        $now = Carbon::now();
        $bookingEnd = Carbon::parse($booking->end_time);
        
        if ($now->isBefore($bookingEnd)) {
            return 'Время бронирования еще не истекло';
        }

        return null;
    }

    /**
     * Валидировать данные для завершения
     */
    public function validateCompletionData(array $data): array
    {
        $errors = [];

        if (empty($data['completion_notes'])) {
            $errors[] = 'Необходимо указать заметки о завершении';
        }

        if (isset($data['rating']) && ($data['rating'] < 1 || $data['rating'] > 5)) {
            $errors[] = 'Рейтинг должен быть от 1 до 5';
        }

        return $errors;
    }
}
