<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Enums\BookingStatus;
use Carbon\Carbon;

/**
 * Управление статусами и переходами бронирований
 */
class BookingStatusManager
{
    /**
     * Подтвердить бронирование
     */
    public function confirm(Booking $booking): bool
    {
        if (!$this->canConfirm($booking)) {
            return false;
        }

        $booking->update([
            'status' => $this->getNewStatus($booking, 'confirmed'),
            'confirmed_at' => now()
        ]);

        return true;
    }

    /**
     * Отменить бронирование
     */
    public function cancel(Booking $booking, string $reason, int $userId, bool $byClient = true): bool
    {
        if (!$this->canCancel($booking)) {
            return false;
        }

        $newStatus = $this->getNewStatus($booking, 'cancelled', $byClient);
        
        $booking->update([
            'status' => $newStatus,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $userId
        ]);

        return true;
    }

    /**
     * Завершить бронирование
     */
    public function complete(Booking $booking): bool
    {
        if (!$this->canComplete($booking)) {
            return false;
        }

        $booking->update([
            'status' => $this->getNewStatus($booking, 'completed'),
            'completed_at' => now(),
            'payment_status' => 'paid'
        ]);

        // Обновить статистику мастера
        if ($booking->masterProfile) {
            $booking->masterProfile->increment('completed_bookings');
        }

        return true;
    }

    /**
     * Начать выполнение услуги
     */
    public function startService(Booking $booking): bool
    {
        if (!$this->canStart($booking)) {
            return false;
        }

        $booking->update([
            'status' => $this->getNewStatus($booking, 'in_progress')
        ]);

        return true;
    }

    /**
     * Можно ли подтвердить
     */
    public function canConfirm(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->canTransitionTo(BookingStatus::CONFIRMED);
        }
        
        return $booking->status === Booking::STATUS_PENDING;
    }

    /**
     * Можно ли отменить
     */
    public function canCancel(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->canBeCancelled();
        }

        // Нельзя отменить если уже отменено или завершено
        if (in_array($booking->status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED])) {
            return false;
        }

        // Нельзя отменить за 2 часа до начала
        if ($booking->booking_date && $booking->start_time) {
            $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
            if ($bookingDateTime->diffInHours(now()) < 2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Можно ли завершить
     */
    public function canComplete(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->canTransitionTo(BookingStatus::COMPLETED);
        }
        
        return in_array($booking->status, [Booking::STATUS_CONFIRMED, Booking::STATUS_IN_PROGRESS]);
    }

    /**
     * Можно ли начать выполнение
     */
    public function canStart(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->canTransitionTo(BookingStatus::IN_PROGRESS);
        }
        
        return $booking->status === Booking::STATUS_CONFIRMED;
    }

    /**
     * Получить новый статус с учетом совместимости
     */
    private function getNewStatus(Booking $booking, string $action, bool $byClient = true): string|BookingStatus
    {
        if ($booking->status instanceof BookingStatus) {
            return match($action) {
                'confirmed' => BookingStatus::CONFIRMED,
                'cancelled' => $byClient ? BookingStatus::CANCELLED_BY_CLIENT : BookingStatus::CANCELLED_BY_MASTER,
                'completed' => BookingStatus::COMPLETED,
                'in_progress' => BookingStatus::IN_PROGRESS,
                default => $booking->status
            };
        }

        // Старые строковые статусы для совместимости
        return match($action) {
            'confirmed' => Booking::STATUS_CONFIRMED,
            'cancelled' => Booking::STATUS_CANCELLED,
            'completed' => Booking::STATUS_COMPLETED,
            'in_progress' => Booking::STATUS_IN_PROGRESS,
            default => $booking->status
        };
    }

    /**
     * Проверить активность бронирования
     */
    public function isActive(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->isActive();
        }

        return in_array($booking->status, [
            Booking::STATUS_PENDING,
            Booking::STATUS_CONFIRMED,
            Booking::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Проверить завершенность бронирования
     */
    public function isCompleted(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->isCompleted();
        }

        return $booking->status === Booking::STATUS_COMPLETED;
    }

    /**
     * Проверить отмененность бронирования
     */
    public function isCancelled(Booking $booking): bool
    {
        if ($booking->status instanceof BookingStatus) {
            return $booking->status->isCancelled();
        }

        return in_array($booking->status, [
            Booking::STATUS_CANCELLED,
            Booking::STATUS_NO_SHOW
        ]);
    }
}