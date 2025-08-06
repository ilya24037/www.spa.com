<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\BookingType;
use Carbon\Carbon;

/**
 * Валидация бронирований и бизнес-правил
 */
class BookingValidator
{
    /**
     * Валидировать данные бронирования перед созданием
     */
    public function validateBookingData(array $data): array
    {
        $errors = [];

        // Проверка обязательных полей
        if (empty($data['client_id'])) {
            $errors[] = 'Клиент обязателен';
        }

        if (empty($data['master_id'])) {
            $errors[] = 'Мастер обязателен';
        }

        if (empty($data['service_id'])) {
            $errors[] = 'Услуга обязательна';
        }

        // Проверка времени
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);

            if ($endTime->lte($startTime)) {
                $errors[] = 'Время окончания должно быть позже времени начала';
            }
        }

        // Проверка цены
        if (!empty($data['total_price']) && $data['total_price'] < 0) {
            $errors[] = 'Цена не может быть отрицательной';
        }

        return $errors;
    }

    /**
     * Проверить доступность мастера на указанное время
     */
    public function validateMasterAvailability(int $masterId, Carbon $startTime, Carbon $endTime, ?int $excludeBookingId = null): bool
    {
        $query = Booking::where('master_id', $masterId)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_IN_PROGRESS
            ])
            ->where(function($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function($subQ) use ($startTime, $endTime) {
                      $subQ->where('start_time', '<=', $startTime)
                           ->where('end_time', '>=', $endTime);
                  });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->doesntExist();
    }

    /**
     * Проверить минимальное время до бронирования
     */
    public function validateMinimumBookingTime(Carbon $bookingTime, int $minimumHours = 2): bool
    {
        return $bookingTime->diffInHours(now()) >= $minimumHours;
    }

    /**
     * Проверить максимальное время заблаговременного бронирования
     */
    public function validateMaximumAdvanceTime(Carbon $bookingTime, int $maximumDays = 90): bool
    {
        return $bookingTime->diffInDays(now()) <= $maximumDays;
    }

    /**
     * Проверить рабочие часы мастера
     */
    public function validateWorkingHours(int $masterId, Carbon $bookingTime): bool
    {
        // Упрощенная проверка - можно расширить логикой расписания мастера
        $hour = $bookingTime->hour;
        $dayOfWeek = $bookingTime->dayOfWeek;

        // Базовые рабочие часы: 8:00-22:00, понедельник-воскресенье
        if ($hour < 8 || $hour >= 22) {
            return false;
        }

        return true;
    }

    /**
     * Проверить максимальное количество бронирований клиента в день
     */
    public function validateClientDailyLimit(int $clientId, Carbon $date, int $maxBookingsPerDay = 3): bool
    {
        $bookingsCount = Booking::where('client_id', $clientId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_IN_PROGRESS,
                Booking::STATUS_COMPLETED
            ])
            ->count();

        return $bookingsCount < $maxBookingsPerDay;
    }

    /**
     * Проверить корректность депозита
     */
    public function validateDeposit(float $depositAmount, float $totalPrice): bool
    {
        if ($depositAmount < 0) {
            return false;
        }

        // Депозит не может превышать общую стоимость
        if ($depositAmount > $totalPrice) {
            return false;
        }

        return true;
    }

    /**
     * Проверить возможность изменения бронирования
     */
    public function canModifyBooking(Booking $booking): bool
    {
        // Нельзя изменить завершенные или отмененные бронирования
        if (in_array($booking->status, [
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW
        ])) {
            return false;
        }

        // Нельзя изменить за 4 часа до начала
        if ($booking->booking_date && $booking->start_time) {
            $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
            if ($bookingDateTime->diffInHours(now()) < 4) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверить возможность оставить отзыв
     */
    public function canLeaveReview(Booking $booking, int $userId): bool
    {
        // Только клиент может оставить отзыв
        if ($booking->client_id !== $userId) {
            return false;
        }

        // Только для завершенных бронирований
        if ($booking->status !== Booking::STATUS_COMPLETED) {
            return false;
        }

        // Проверить, что отзыв еще не был оставлен
        return !$booking->reviews()->where('user_id', $userId)->exists();
    }

    /**
     * Проверить возможность возврата средств
     */
    public function canRefund(Booking $booking): bool
    {
        // Возврат возможен только для отмененных или не состоявшихся бронирований
        if (!in_array($booking->status, [
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW
        ])) {
            return false;
        }

        // Проверить, что была оплата
        if ($booking->paid_amount <= 0) {
            return false;
        }

        // Возврат возможен в течение 30 дней после отмены
        if ($booking->cancelled_at && $booking->cancelled_at->diffInDays(now()) > 30) {
            return false;
        }

        return true;
    }

    /**
     * Рассчитать размер возврата
     */
    public function calculateRefundAmount(Booking $booking): float
    {
        if (!$this->canRefund($booking)) {
            return 0;
        }

        $refundAmount = $booking->paid_amount;

        // Если отмена за менее чем 24 часа до начала - удерживаем 50%
        if ($booking->cancelled_at && $booking->booking_date && $booking->start_time) {
            $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
            $hoursUntilBooking = $booking->cancelled_at->diffInHours($bookingDateTime);

            if ($hoursUntilBooking < 24) {
                $refundAmount *= 0.5; // 50% возврата
            }
        }

        return round($refundAmount, 2);
    }
}