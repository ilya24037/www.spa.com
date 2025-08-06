<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use Carbon\Carbon;

/**
 * Валидатор переноса бронирований
 */
class RescheduleValidator
{
    /**
     * Валидация возможности переноса
     */
    public function validate(
        Booking $booking, 
        User $user, 
        Carbon $newStartTime, 
        ?int $newDuration
    ): void {
        $this->validateUserPermissions($booking, $user);
        $this->validateBookingStatus($booking);
        $this->validateNewTime($booking, $newStartTime, $newDuration);
        $this->validateRescheduleTime($booking, $user);
    }

    /**
     * Проверка прав пользователя
     */
    public function validateUserPermissions(Booking $booking, User $user): void
    {
        $isClient = $booking->client_id === $user->id;
        $isMaster = $booking->master_id === $user->id || 
                   ($booking->master_profile_id && $user->getMasterProfile() && 
                    $booking->master_profile_id === $user->getMasterProfile()->id);
        $isAdmin = $user->hasRole('admin') || $user->hasRole('moderator');

        if (!$isClient && !$isMaster && !$isAdmin) {
            throw new \Exception('У вас нет прав для переноса этого бронирования');
        }
    }

    /**
     * Проверка статуса бронирования
     */
    public function validateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canBeRescheduled()) {
                throw new \Exception(
                    "Нельзя перенести бронирование в статусе: {$booking->status->getLabel()}"
                );
            }
        } else {
            // Совместимость со старым кодом
            if (!in_array($booking->status, [
                Booking::STATUS_PENDING, 
                Booking::STATUS_CONFIRMED
            ])) {
                throw new \Exception('Бронирование нельзя перенести в текущем статусе');
            }
        }
    }

    /**
     * Проверка нового времени
     */
    public function validateNewTime(
        Booking $booking, 
        Carbon $newStartTime, 
        ?int $newDuration
    ): void {
        // Новое время не должно быть в прошлом
        if ($newStartTime->isPast()) {
            throw new \Exception('Нельзя перенести на время в прошлом');
        }

        // Проверяем минимальное время заранее
        $minAdvanceHours = $booking->type ? $booking->type->getMinAdvanceHours() : 2;
        if ($newStartTime->lt(now()->addHours($minAdvanceHours))) {
            throw new \Exception(
                "Новое время должно быть не менее чем за {$minAdvanceHours} часов"
            );
        }

        // Проверяем максимальную продолжительность
        $duration = $newDuration ?? $booking->duration_minutes;
        $maxDurationHours = $booking->type ? $booking->type->getMaxDurationHours() : 8;
        
        if ($duration > ($maxDurationHours * 60)) {
            throw new \Exception(
                "Максимальная продолжительность: {$maxDurationHours} часов"
            );
        }

        // Проверяем что не переносим на слишком далекое будущее
        if ($newStartTime->gt(now()->addMonths(3))) {
            throw new \Exception('Нельзя перенести более чем на 3 месяца вперед');
        }
    }

    /**
     * Проверка ограничений по времени переноса
     */
    public function validateRescheduleTime(Booking $booking, User $user): void
    {
        $hoursUntilStart = now()->diffInHours($booking->start_time, false);
        $isClient = $booking->client_id === $user->id;

        // Для клиентов более строгие ограничения
        if ($isClient) {
            $minHours = $booking->type ? $booking->type->getMinAdvanceHours() : 4;
            
            if ($hoursUntilStart < $minHours) {
                throw new \Exception(
                    "Перенос возможен не позднее чем за {$minHours} часов до начала"
                );
            }
        }

        // Ограничение на количество переносов
        $rescheduleCount = $this->getRescheduleCount($booking);
        $maxReschedules = $isClient ? 2 : 5; // Клиенты - 2 раза, мастера - 5 раз
        
        if ($rescheduleCount >= $maxReschedules) {
            throw new \Exception(
                "Превышен лимит переносов ({$maxReschedules} раз). Обратитесь в поддержку."
            );
        }
    }

    /**
     * Проверить можно ли перенести бронирование
     */
    public function canReschedule(Booking $booking, User $user): bool
    {
        try {
            $this->validateUserPermissions($booking, $user);
            $this->validateBookingStatus($booking);
            $this->validateRescheduleTime($booking, $user);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить причину невозможности переноса
     */
    public function getRescheduleError(Booking $booking, User $user): ?string
    {
        try {
            $this->validateUserPermissions($booking, $user);
            $this->validateBookingStatus($booking);
            $this->validateRescheduleTime($booking, $user);
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Получить количество переносов бронирования
     */
    public function getRescheduleCount(Booking $booking): int
    {
        $metadata = $booking->metadata ?? [];
        return count($metadata['reschedules'] ?? []);
    }

    /**
     * Получить оставшееся количество переносов
     */
    public function getRemainingReschedules(Booking $booking, User $user): int
    {
        $isClient = $booking->client_id === $user->id;
        $maxReschedules = $isClient ? 2 : 5;
        $currentCount = $this->getRescheduleCount($booking);
        
        return max(0, $maxReschedules - $currentCount);
    }

    /**
     * Проверить можно ли перенести на указанное время
     */
    public function canRescheduleToTime(
        Booking $booking, 
        User $user, 
        Carbon $newStartTime, 
        ?int $newDuration = null
    ): bool {
        try {
            $this->validate($booking, $user, $newStartTime, $newDuration);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Получить минимальное время для переноса
     */
    public function getMinRescheduleTime(Booking $booking, User $user): Carbon
    {
        $isClient = $booking->client_id === $user->id;
        $minHours = $isClient ? 4 : 2;
        
        return now()->addHours($minHours);
    }

    /**
     * Получить максимальное время для переноса
     */
    public function getMaxRescheduleTime(): Carbon
    {
        return now()->addMonths(3);
    }
}