<?php

namespace App\Domain\Booking\Actions\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\User\Models\User;
use Exception;

/**
 * Валидатор подтверждения бронирований
 */
class BookingConfirmationValidator
{
    /**
     * Валидация возможности подтверждения
     */
    public function validateConfirmation(Booking $booking, User $master): void
    {
        $this->validateMasterPermissions($booking, $master);
        $this->validateBookingStatus($booking);
        $this->validateConfirmationTime($booking);
    }

    /**
     * Проверка прав мастера
     */
    protected function validateMasterPermissions(Booking $booking, User $master): void
    {
        $canConfirm = $booking->master_id === $master->id ||
                     ($booking->master_profile_id && $master->masterProfile && 
                      $booking->master_profile_id === $master->masterProfile->id) ||
                     $master->hasRole('admin');

        if (!$canConfirm) {
            throw new Exception('У вас нет прав для подтверждения этого бронирования');
        }
    }

    /**
     * Проверка статуса бронирования
     */
    protected function validateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if (!$booking->status->canTransitionTo(BookingStatus::CONFIRMED)) {
                throw new Exception(
                    "Нельзя подтвердить бронирование в статусе: {$booking->status->getLabel()}"
                );
            }
        } else {
            if ($booking->status !== Booking::STATUS_PENDING) {
                throw new Exception('Можно подтвердить только ожидающие бронирования');
            }
        }
    }

    /**
     * Проверка времени подтверждения
     */
    protected function validateConfirmationTime(Booking $booking): void
    {
        if ($booking->start_time->isPast()) {
            throw new Exception('Нельзя подтвердить просроченное бронирование');
        }

        $hoursOld = $booking->created_at->diffInHours(now());
        $autoCanelHours = $booking->status instanceof BookingStatus 
            ? $booking->status->getAutoCanelHours() 
            : 24;

        if ($autoCanelHours && $hoursOld > $autoCanelHours) {
            throw new Exception(
                "Бронирование просрочено для подтверждения (создано {$hoursOld} часов назад, " .
                "лимит: {$autoCanelHours} часов)"
            );
        }
    }

    /**
     * Проверка конфликтов в расписании
     */
    public function checkScheduleConflicts(Booking $booking, User $master): void
    {
        $conflicts = Booking::where('master_id', $master->id)
            ->where('id', '!=', $booking->id)
            ->where(function ($query) use ($booking) {
                $query->where('status', BookingStatus::CONFIRMED)
                      ->orWhere('status', BookingStatus::IN_PROGRESS);
            })
            ->where(function ($query) use ($booking) {
                $query->whereBetween('start_time', [$booking->start_time, $booking->end_time])
                      ->orWhereBetween('end_time', [$booking->start_time, $booking->end_time])
                      ->orWhere(function ($q) use ($booking) {
                          $q->where('start_time', '<=', $booking->start_time)
                            ->where('end_time', '>=', $booking->end_time);
                      });
            })
            ->exists();

        if ($conflicts) {
            throw new Exception(
                'Обнаружен конфликт в расписании. У вас уже есть подтвержденное бронирование на это время.'
            );
        }
    }
}