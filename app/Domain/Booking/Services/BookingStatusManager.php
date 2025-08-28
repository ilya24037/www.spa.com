<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Enums\BookingStatus;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный менеджер статусов бронирования
 * Делегирует сложную логику в другие сервисы
 */
class BookingStatusManager
{
    public function __construct(
        private BookingValidationService $validationService
    ) {}

    /**
     * Подтвердить бронирование
     */
    public function confirm(Booking $booking): bool
    {
        try {
            $this->validationService->validateConfirmation($booking);
            
            $booking->status = BookingStatus::CONFIRMED;
            $booking->confirmed_at = now();
            $booking->save();
            
            Log::info('Booking confirmed', ['booking_id' => $booking->id]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to confirm booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Отменить бронирование
     */
    public function cancel(Booking $booking, string $reason, int $userId, bool $byClient = true): bool
    {
        try {
            $user = \App\Domain\User\Models\User::find($userId);
            $this->validationService->validateCancellation($booking, $user);
            
            $booking->status = BookingStatus::CANCELLED;
            $booking->cancelled_at = now();
            $booking->cancellation_reason = $reason;
            $booking->cancelled_by = $userId;
            $booking->save();
            
            Log::info('Booking cancelled', [
                'booking_id' => $booking->id,
                'reason' => $reason,
                'by_client' => $byClient
            ]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to cancel booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Завершить бронирование
     */
    public function complete(Booking $booking): bool
    {
        try {
            $this->validationService->validateCompletion($booking);
            
            $booking->status = BookingStatus::COMPLETED;
            $booking->completed_at = now();
            $booking->save();
            
            Log::info('Booking completed', ['booking_id' => $booking->id]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to complete booking', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Начать выполнение услуги
     */
    public function startService(Booking $booking): bool
    {
        if ($booking->status !== BookingStatus::CONFIRMED) {
            return false;
        }

        $booking->status = BookingStatus::IN_PROGRESS;
        $booking->save();
        
        Log::info('Service started', ['booking_id' => $booking->id]);
        return true;
    }

    /**
     * Можно ли отменить бронирование
     */
    public function canCancel(Booking $booking): bool
    {
        return in_array($booking->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            && $booking->start_time > now()->addHours(2);
    }

    /**
     * Можно ли подтвердить бронирование
     */
    public function canConfirm(Booking $booking): bool
    {
        return $booking->status === BookingStatus::PENDING
            && $booking->start_time > now();
    }

    /**
     * Можно ли завершить бронирование
     */
    public function canComplete(Booking $booking): bool
    {
        return $booking->status === BookingStatus::CONFIRMED
            && $booking->end_time <= now();
    }

    /**
     * Активное ли бронирование
     */
    public function isActive(Booking $booking): bool
    {
        return in_array($booking->status, [
            BookingStatus::PENDING,
            BookingStatus::CONFIRMED,
            BookingStatus::IN_PROGRESS
        ]);
    }

    /**
     * Завершено ли бронирование
     */
    public function isCompleted(Booking $booking): bool
    {
        return $booking->status === BookingStatus::COMPLETED;
    }

    /**
     * Отменено ли бронирование
     */
    public function isCancelled(Booking $booking): bool
    {
        return $booking->status === BookingStatus::CANCELLED;
    }
}