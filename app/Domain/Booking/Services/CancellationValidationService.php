<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\User\Models\User;

/**
 * Сервис валидации отмены бронирования
 */
class CancellationValidationService
{
    /**
     * Проверить возможность отмены бронирования
     */
    public function validate(Booking $booking, User $user): array
    {
        // Проверка прав доступа
        if (!$this->canUserCancel($booking, $user)) {
            return [
                'valid' => false,
                'message' => 'У вас нет прав для отмены этого бронирования'
            ];
        }

        // Проверка статуса
        $statusCheck = $this->validateBookingStatus($booking);
        if (!$statusCheck['valid']) {
            return $statusCheck;
        }

        // Проверка времени
        $timeCheck = $this->validateCancellationTime($booking, $user);
        if (!$timeCheck['valid']) {
            return $timeCheck;
        }

        return [
            'valid' => true,
            'message' => 'Бронирование может быть отменено'
        ];
    }

    /**
     * Проверить права пользователя на отмену
     */
    public function canUserCancel(Booking $booking, User $user): bool
    {
        // Админы могут отменять любые бронирования
        if ($user->hasRole('admin')) {
            return true;
        }

        // Проверяем, является ли пользователь клиентом или мастером
        return $booking->client_id === $user->id || 
               $booking->master_id === $user->id;
    }

    /**
     * Проверка статуса бронирования
     */
    public function validateBookingStatus(Booking $booking): array
    {
        $allowedStatuses = [
            BookingStatus::PENDING,
            BookingStatus::CONFIRMED,
            BookingStatus::PAID
        ];

        if ($booking->status instanceof BookingStatus) {
            if (!in_array($booking->status, $allowedStatuses)) {
                return [
                    'valid' => false,
                    'message' => "Нельзя отменить бронирование в статусе: {$booking->status->getLabel()}"
                ];
            }
        } else {
            // Совместимость со старым кодом
            $oldAllowedStatuses = [
                Booking::STATUS_PENDING ?? 'pending',
                Booking::STATUS_CONFIRMED ?? 'confirmed'
            ];
            
            if (!in_array($booking->status, $oldAllowedStatuses)) {
                return [
                    'valid' => false,
                    'message' => 'Бронирование нельзя отменить в текущем статусе'
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'Статус позволяет отмену'
        ];
    }

    /**
     * Проверка времени отмены
     */
    public function validateCancellationTime(Booking $booking, User $user): array
    {
        $bookingStart = $booking->start_at ?? $booking->start_time;
        $hoursUntilStart = now()->diffInHours($bookingStart, false);

        // Для клиентов - более строгие ограничения
        if ($booking->client_id === $user->id) {
            $minHours = $this->getMinAdvanceHoursForClient($booking);
            
            if ($hoursUntilStart < $minHours) {
                return [
                    'valid' => false,
                    'message' => "Отмена возможна не позднее чем за {$minHours} часов до начала услуги"
                ];
            }
        }

        // Для мастеров - можно отменить позже, но с ограничениями
        if ($booking->master_id === $user->id && $hoursUntilStart < 1) {
            return [
                'valid' => false,
                'message' => 'Мастер не может отменить бронирование менее чем за час до начала'
            ];
        }

        // Проверка на прошедшее время
        if ($hoursUntilStart < 0) {
            return [
                'valid' => false,
                'message' => 'Невозможно отменить уже прошедшее бронирование'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Время позволяет отмену',
            'hours_until_start' => $hoursUntilStart
        ];
    }

    /**
     * Получить минимальное количество часов для отмены клиентом
     */
    private function getMinAdvanceHoursForClient(Booking $booking): int
    {
        // Можно настроить в зависимости от типа услуги
        if ($booking->service_type === 'premium') {
            return 24;
        }
        
        if ($booking->service_type === 'standard') {
            return 4;
        }
        
        return 2; // По умолчанию
    }

    /**
     * Проверить, требуется ли подтверждение для отмены
     */
    public function requiresConfirmation(Booking $booking, User $user): bool
    {
        $hoursUntilStart = now()->diffInHours($booking->start_at ?? $booking->start_time, false);
        
        // Требуем подтверждение при поздней отмене
        if ($hoursUntilStart < 4) {
            return true;
        }
        
        // Требуем подтверждение для оплаченных бронирований
        if ($booking->paid_amount > 0) {
            return true;
        }
        
        return false;
    }
}