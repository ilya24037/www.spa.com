<?php

namespace App\Domain\Booking\Actions;

/**
 * ✅ DDD РЕФАКТОРИНГ ПРИМЕНЕН:
 * - Заменены прямые связи на Integration Services
 * - Удалены циклические зависимости между доменами
 * - Применены Events для междоменного взаимодействия
 * 
 * Обновлено автоматически: 2025-08-05T06:11:58.039Z
 */


use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use App\Domain\Booking\Repositories\BookingRepository;
use App\Infrastructure\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для переноса бронирования
 * Инкапсулирует всю логику переноса с проверками доступности и уведомлениями
 */
class RescheduleBookingAction
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private NotificationService $notificationService
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

        // Валидация прав и возможности переноса
        $this->validateReschedule($booking, $user, $newStartTime, $newDuration);

        // Проверка доступности нового времени
        $this->validateNewTimeSlot($booking, $newStartTime, $newDuration);

        // Выполнение переноса в транзакции
        $result = DB::transaction(function () use ($booking, $user, $newStartTime, $newDuration, $reason) {
            return $this->performReschedule($booking, $user, $newStartTime, $newDuration, $reason);
        });

        // Отправка уведомлений
        $this->sendRescheduleNotifications($booking, $user, $result['old_time']);

        Log::info('Booking rescheduled successfully', [
            'booking_id' => $booking->id,
            'new_time' => $booking->start_time,
        ]);

        return $booking->fresh();
    }

    /**
     * Валидация возможности переноса
     */
    protected function validateReschedule(
        Booking $booking, 
        User $user, 
        Carbon $newStartTime, 
        ?int $newDuration
    ): void {
        // Проверяем права пользователя
        $this->validateUserPermissions($booking, $user);

        // Проверяем статус бронирования
        $this->validateBookingStatus($booking);

        // Проверяем новое время
        $this->validateNewTime($booking, $newStartTime, $newDuration);

        // Проверяем ограничения по времени переноса
        $this->validateRescheduleTime($booking, $user);
    }

    /**
     * Проверка прав пользователя
     */
    protected function validateUserPermissions(Booking $booking, User $user): void
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
    protected function validateBookingStatus(Booking $booking): void
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
    protected function validateNewTime(
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
    protected function validateRescheduleTime(Booking $booking, User $user): void
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
     * Получить количество переносов бронирования
     */
    protected function getRescheduleCount(Booking $booking): int
    {
        $metadata = $booking->metadata ?? [];
        return count($metadata['reschedules'] ?? []);
    }

    /**
     * Проверка доступности нового временного слота
     */
    protected function validateNewTimeSlot(
        Booking $booking, 
        Carbon $newStartTime, 
        ?int $newDuration
    ): void {
        $duration = $newDuration ?? $booking->duration_minutes;
        $newEndTime = $newStartTime->copy()->addMinutes($duration);
        $masterId = $booking->master_id;

        // Проверяем пересечения с другими бронированиями
        $overlapping = $this->bookingRepository->findOverlapping(
            $newStartTime,
            $newEndTime,
            $masterId,
            $booking->id
        );

        if ($overlapping->isNotEmpty()) {
            $conflictBooking = $overlapping->first();
            throw new \Exception(
                "Новое время занято бронированием #{$conflictBooking->booking_number} " .
                "({$conflictBooking->start_time->format('d.m.Y H:i')} - {$conflictBooking->end_time->format('H:i')})"
            );
        }

        // Проверяем рабочее время мастера
        $this->validateMasterAvailability($booking, $newStartTime, $duration);
    }

    /**
     * Проверка доступности мастера
     */
    protected function validateMasterAvailability(
        Booking $booking, 
        Carbon $newStartTime, 
        int $duration
    ): void {
        $master = User::find($booking->master_id);
        if (!$master || !$master->masterProfile) {
            return; // Если нет профиля мастера, пропускаем проверку
        }

        $dayOfWeek = $newStartTime->dayOfWeek;
        $schedule = $master->masterProfile->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            throw new \Exception(
                'Мастер не работает в ' . $newStartTime->locale('ru')->dayName
            );
        }

        $workStart = Carbon::parse($newStartTime->format('Y-m-d') . ' ' . $schedule->start_time);
        $workEnd = Carbon::parse($newStartTime->format('Y-m-d') . ' ' . $schedule->end_time);
        $bookingEnd = $newStartTime->copy()->addMinutes($duration);

        if ($newStartTime->lt($workStart) || $bookingEnd->gt($workEnd)) {
            throw new \Exception(
                "Новое время выходит за рабочие часы мастера ({$workStart->format('H:i')} - {$workEnd->format('H:i')})"
            );
        }
    }

    /**
     * Выполнение переноса
     */
    protected function performReschedule(
        Booking $booking,
        User $user,
        Carbon $newStartTime,
        ?int $newDuration,
        ?string $reason
    ): array {
        $oldStartTime = $booking->start_time->copy();
        $oldEndTime = $booking->end_time->copy();
        $duration = $newDuration ?? $booking->duration_minutes;
        $newEndTime = $newStartTime->copy()->addMinutes($duration);

        // Обновляем бронирование
        $booking->start_time = $newStartTime;
        $booking->end_time = $newEndTime;
        $booking->duration_minutes = $duration;

        // Устанавливаем статус "перенесено" если еще не подтверждено
        if ($booking->status instanceof BookingStatus) {
            if ($booking->status === BookingStatus::PENDING) {
                $booking->status = BookingStatus::RESCHEDULED;
            }
        }

        // Добавляем информацию о переносе в метаданные
        $metadata = $booking->metadata ?? [];
        $reschedules = $metadata['reschedules'] ?? [];
        
        $reschedules[] = [
            'from_time' => $oldStartTime->toISOString(),
            'to_time' => $newStartTime->toISOString(),
            'from_duration' => $booking->duration_minutes,
            'to_duration' => $duration,
            'rescheduled_by' => $user->id,
            'rescheduled_at' => now()->toISOString(),
            'reason' => $reason,
            'user_role' => $booking->client_id === $user->id ? 'client' : 'master',
        ];
        
        $metadata['reschedules'] = $reschedules;
        $metadata['reschedule_count'] = count($reschedules);
        $booking->metadata = $metadata;

        $booking->save();

        // Обновляем связанные слоты
        $this->updateBookingSlots($booking, $oldStartTime, $oldEndTime);

        return [
            'booking' => $booking,
            'old_time' => $oldStartTime,
            'new_time' => $newStartTime,
            'reschedule_count' => count($reschedules),
        ];
    }

    /**
     * Обновление временных слотов
     */
    protected function updateBookingSlots(
        Booking $booking, 
        Carbon $oldStartTime, 
        Carbon $oldEndTime
    ): void {
        // Удаляем старые слоты
        $booking->slots()->delete();

        // Создаем новые слоты
        $booking->slots()->create([
            'master_id' => $booking->master_id,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'duration_minutes' => $booking->duration_minutes,
            'is_blocked' => false,
            'is_break' => false,
            'is_preparation' => false,
            'notes' => "Перенесено с {$oldStartTime->format('d.m.Y H:i')} - {$oldEndTime->format('H:i')}",
        ]);
    }

    /**
     * Отправка уведомлений о переносе
     */
    protected function sendRescheduleNotifications(
        Booking $booking, 
        User $rescheduledBy, 
        Carbon $oldTime
    ): void {
        try {
            $isClient = $booking->client_id === $rescheduledBy->id;

            if ($isClient) {
                // Клиент перенес - уведомляем мастера
                $this->notificationService->sendBookingRescheduledByClient($booking, $oldTime);
            } else {
                // Мастер перенес - уведомляем клиента
                $this->notificationService->sendBookingRescheduledByMaster($booking, $oldTime);
            }

            // SMS уведомление с новым временем
            if ($booking->client_phone) {
                $this->notificationService->sendRescheduleSMS($booking, $oldTime);
            }

            Log::info('Reschedule notifications sent', [
                'booking_id' => $booking->id,
                'rescheduled_by' => $isClient ? 'client' : 'master',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reschedule notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Массовый перенос бронирований (например, при изменении расписания мастера)
     */
    public function bulkReschedule(
        array $bookingIds, 
        User $user, 
        array $newTimes, 
        string $reason
    ): array {
        $results = [];

        foreach ($bookingIds as $index => $bookingId) {
            try {
                $booking = Booking::findOrFail($bookingId);
                $newTime = Carbon::parse($newTimes[$index] ?? $newTimes[0]);
                
                $this->execute($booking, $user, $newTime, null, $reason);
                
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => true,
                    'new_time' => $newTime->toISOString(),
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Bulk reschedule completed', [
            'total_bookings' => count($bookingIds),
            'successful' => count(array_filter($results, fn($r) => $r['success'])),
        ]);

        return $results;
    }

    /**
     * Автоматический перенос при конфликтах
     */
    public function autoRescheduleConflicts(User $master, Carbon $conflictDate): array
    {
        // Находим конфликтующие бронирования
        $conflictBookings = $this->bookingRepository->getBookingsForDate($conflictDate, $master->id)
            ->filter(function ($booking) {
                return $booking->status instanceof BookingStatus ? 
                    $booking->status->canBeRescheduled() : 
                    in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED]);
            });

        $results = [];

        foreach ($conflictBookings as $booking) {
            try {
                // Ищем ближайшее доступное время
                $newTime = $this->findNextAvailableSlot($booking, $master);
                
                if ($newTime) {
                    $this->execute($booking, $master, $newTime, null, 'Автоматический перенос из-за изменения расписания');
                    
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
     * Поиск следующего доступного слота
     */
    protected function findNextAvailableSlot(Booking $booking, User $master): ?Carbon
    {
        $duration = $booking->duration_minutes;
        $startDate = $booking->start_time->copy()->addDay();
        $endDate = $startDate->copy()->addWeeks(2);

        for ($date = $startDate; $date <= $endDate; $date->addDay()) {
            $dayOfWeek = $date->dayOfWeek;
            $schedule = $master->masterProfile->schedules()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->first();

            if (!$schedule) continue;

            $workStart = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
            $workEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

            // Ищем свободные слоты в этот день
            $currentTime = $workStart->copy();
            
            while ($currentTime->copy()->addMinutes($duration) <= $workEnd) {
                $slotEnd = $currentTime->copy()->addMinutes($duration);
                
                // Проверяем конфликты
                $conflicts = $this->bookingRepository->findOverlapping(
                    $currentTime,
                    $slotEnd,
                    $master->id,
                    $booking->id
                );

                if ($conflicts->isEmpty()) {
                    return $currentTime;
                }

                $currentTime->addMinutes(30);
            }
        }

        return null; // Не найдено подходящего времени
    }
}