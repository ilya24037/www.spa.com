<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Domain\Booking\Services\BookingValidationService;
use App\Domain\Booking\Services\BookingService;
use App\Domain\Booking\Services\BookingNotificationService;
use App\Domain\Booking\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для переноса бронирования (упрощенная версия)
 * Использует существующие сервисы без сложной логики
 */
class RescheduleBookingAction
{
    public function __construct(
        private BookingValidationService $validationService,
        private BookingService $bookingService,
        private BookingNotificationService $notificationService
    ) {}

    /**
     * Выполнить перенос бронирования (упрощенная версия)
     */
    public function execute(
        Booking $booking,
        User $user,
        Carbon $newStartTime,
        ?int $newDuration = null,
        ?string $reason = null
    ): Booking {
        Log::info('Rescheduling booking', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'old_time' => $booking->start_time,
            'new_time' => $newStartTime,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($booking, $user, $newStartTime, $newDuration, $reason) {
            // Базовая валидация
            try {
                $this->validationService->validateReschedule($booking, $user);
            } catch (\Exception $e) {
                throw new \Exception('Нельзя перенести бронирование: ' . $e->getMessage());
            }

            // Простая проверка доступности
            $this->validateSimpleAvailability($booking, $newStartTime, $newDuration);

            // Сохраняем старое время для уведомлений
            $oldTime = $booking->start_time;

            // Простое выполнение переноса
            $this->performSimpleReschedule($booking, $user, $newStartTime, $newDuration, $reason);

            // Отправка уведомлений
            try {
                $this->notificationService->sendRescheduleNotifications($booking, $user, $oldTime);
            } catch (\Exception $e) {
                Log::warning('Failed to send reschedule notifications', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Booking rescheduled successfully', [
                'booking_id' => $booking->id,
                'new_time' => $booking->start_time,
            ]);

            return $booking;
        });
    }

    /**
     * Простая проверка возможности переноса
     */
    public function canReschedule(Booking $booking, User $user): bool
    {
        try {
            $this->validationService->validateReschedule($booking, $user);
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
            $this->validationService->validateReschedule($booking, $user);
            return null;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Простая проверка доступности нового времени
     */
    private function validateSimpleAvailability(
        Booking $booking, 
        Carbon $newStartTime, 
        ?int $newDuration
    ): void {
        $duration = $newDuration ?? $booking->duration_minutes ?? 60;
        
        // Простая проверка - не допускаем перенос на прошедшее время
        if ($newStartTime->isPast()) {
            throw new \Exception('Нельзя перенести на прошедшее время');
        }

        // Простая проверка на пересечение с другими бронированиями
        $conflictingBookings = Booking::where('master_id', $booking->master_id)
            ->where('id', '!=', $booking->id)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where(function ($query) use ($newStartTime, $duration) {
                $endTime = $newStartTime->copy()->addMinutes($duration);
                $query->whereBetween('start_time', [$newStartTime, $endTime])
                      ->orWhereBetween('end_time', [$newStartTime, $endTime])
                      ->orWhere(function ($q) use ($newStartTime, $endTime) {
                          $q->where('start_time', '<=', $newStartTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->exists();

        if ($conflictingBookings) {
            throw new \Exception('На новое время уже есть другое бронирование');
        }
    }

    /**
     * Простое выполнение переноса
     */
    private function performSimpleReschedule(
        Booking $booking, 
        User $user, 
        Carbon $newStartTime, 
        ?int $newDuration, 
        ?string $reason
    ): void {
        // Обновляем время и длительность
        $booking->start_time = $newStartTime;
        
        if ($newDuration) {
            $booking->duration_minutes = $newDuration;
        }
        
        // Рассчитываем новое время окончания
        $booking->end_time = $newStartTime->copy()->addMinutes($booking->duration_minutes ?? 60);

        // Обновляем метаданные
        $metadata = $booking->metadata ?? [];
        $metadata['reschedule'] = [
            'rescheduled_by' => $user->id,
            'rescheduled_at' => now()->toDateTimeString(),
            'reason' => $reason,
            'reschedule_count' => ($metadata['reschedule']['reschedule_count'] ?? 0) + 1,
        ];
        $booking->metadata = $metadata;

        $booking->save();
    }
}