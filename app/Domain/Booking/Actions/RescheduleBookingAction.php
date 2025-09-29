<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Domain\Booking\Services\RescheduleValidator;
use App\Domain\Master\Services\ScheduleAvailabilityChecker;
use App\Domain\Booking\Services\RescheduleExecutor;
use App\Domain\Booking\Services\RescheduleNotificationHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный Action для переноса бронирования
 * Делегирует логику специализированным сервисам
 */
class RescheduleBookingAction
{
    public function __construct(
        private RescheduleValidator $validator,
        private ScheduleAvailabilityChecker $availabilityChecker,
        private RescheduleExecutor $executor,
        private RescheduleNotificationHandler $notificationHandler
    ) {}

    /**
     * Выполнить перенос бронирования
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
            'booking_number' => $booking->booking_number,
            'user_id' => $user->id,
            'old_time' => $booking->start_time,
            'new_time' => $newStartTime,
            'reason' => $reason,
        ]);

        // Валидация через специализированный сервис
        $this->validator->validate($booking, $user, $newStartTime, $newDuration);

        // Проверка доступности через специализированный сервис
        $this->validateAvailability($booking, $newStartTime, $newDuration);

        // Выполнение переноса через специализированный сервис
        $result = $this->executor->execute($booking, $user, $newStartTime, $newDuration, $reason);

        // Отправка уведомлений через специализированный сервис
        $this->notificationHandler->sendRescheduleNotifications($booking, $user, $result['old_time']);

        Log::info('Booking rescheduled successfully', [
            'booking_id' => $booking->id,
            'new_time' => $booking->start_time,
            'reschedule_count' => $result['reschedule_count'],
        ]);

        return $booking->fresh();
    }

    /**
     * Массовый перенос бронирований
     */
    public function bulkReschedule(
        array $bookingIds, 
        User $user, 
        array $newTimes, 
        string $reason
    ): array {
        $results = $this->executor->bulkReschedule($bookingIds, $user, $newTimes, $reason);

        // Отправляем уведомления для успешных переносов
        $successfulBookings = array_filter($results, fn($r) => $r['success']);
        $this->notificationHandler->sendBulkRescheduleNotifications($successfulBookings, $user, $reason);

        Log::info('Bulk reschedule completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count($successfulBookings),
        ]);

        return $results;
    }

    /**
     * Автоматический перенос при конфликтах
     */
    public function autoRescheduleConflicts(User $master, Carbon $conflictDate): array
    {
        // Находим конфликтующие бронирования через availability checker
        $conflictBookings = $this->findConflictingBookings($master, $conflictDate);
        $results = [];

        foreach ($conflictBookings as $booking) {
            try {
                // Ищем ближайшее доступное время через availability checker
                $newTime = $this->availabilityChecker->findNextAvailableSlot(
                    $master->id,
                    $booking->duration_minutes,
                    $booking->start_time->copy()->addDay(),
                    $booking->id
                );
                
                if ($newTime) {
                    $result = $this->executor->execute(
                        $booking, 
                        $master, 
                        $newTime, 
                        null, 
                        'Автоматический перенос из-за изменения расписания'
                    );
                    
                    $this->notificationHandler->sendAutoRescheduleNotification(
                        $booking, 
                        $result['old_time'], 
                        'Изменение расписания мастера'
                    );
                    
                    $results[] = [
                        'booking_id' => $booking->id,
                        'success' => true,
                        'new_time' => $newTime->toISOString(),
                    ];
                } else {
                    $results[] = [
                        'booking_id' => $booking->id,
                        'success' => false,
                        'error' => 'Не найдено подходящее время для переноса',
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $booking->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Проверить можно ли перенести бронирование
     */
    public function canReschedule(Booking $booking, User $user): bool
    {
        return $this->validator->canReschedule($booking, $user);
    }

    /**
     * Получить причину невозможности переноса
     */
    public function getRescheduleError(Booking $booking, User $user): ?string
    {
        return $this->validator->getRescheduleError($booking, $user);
    }

    /**
     * Получить доступные слоты для переноса
     */
    public function getAvailableSlots(
        Booking $booking, 
        Carbon $date, 
        ?int $durationMinutes = null
    ): array {
        $duration = $durationMinutes ?? $booking->duration_minutes;
        
        return $this->availabilityChecker->getAvailableSlotsForDay(
            $booking->master_id,
            $date,
            $duration
        );
    }

    /**
     * Проверить доступность нового времени
     */
    private function validateAvailability(
        Booking $booking, 
        Carbon $newStartTime, 
        ?int $newDuration
    ): void {
        $duration = $newDuration ?? $booking->duration_minutes;
        
        if (!$this->availabilityChecker->isMasterAvailable(
            $booking->master_id,
            $newStartTime,
            $duration,
            $booking->id
        )) {
            $reason = $this->availabilityChecker->getUnavailabilityReason(
                $booking->master_id,
                $newStartTime,
                $duration
            );
            
            throw new \Exception($reason ?? 'Новое время недоступно');
        }
    }

    /**
     * Найти конфликтующие бронирования
     */
    private function findConflictingBookings(User $master, Carbon $conflictDate): array
    {
        // Простая реализация - в реальном проекте это может быть в repository
        return Booking::where('master_id', $master->id)
            ->whereDate('start_time', $conflictDate)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED
            ])
            ->get()
            ->toArray();
    }
}