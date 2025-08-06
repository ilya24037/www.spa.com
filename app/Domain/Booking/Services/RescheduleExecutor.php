<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Исполнитель переноса бронирований
 */
class RescheduleExecutor
{
    /**
     * Выполнить перенос бронирования
     */
    public function execute(
        Booking $booking,
        User $user,
        Carbon $newStartTime,
        ?int $newDuration,
        ?string $reason
    ): array {
        return DB::transaction(function () use ($booking, $user, $newStartTime, $newDuration, $reason) {
            return $this->performReschedule($booking, $user, $newStartTime, $newDuration, $reason);
        });
    }

    /**
     * Выполнение переноса
     */
    private function performReschedule(
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

        // Сохраняем старые данные
        $oldData = [
            'start_time' => $oldStartTime,
            'end_time' => $oldEndTime,
            'duration_minutes' => $booking->duration_minutes,
        ];

        // Обновляем основные данные бронирования
        $booking->start_time = $newStartTime;
        $booking->end_time = $newEndTime;
        $booking->duration_minutes = $duration;

        // Обновляем статус если необходимо
        $this->updateBookingStatus($booking);

        // Добавляем информацию о переносе в метаданные
        $this->updateBookingMetadata($booking, $user, $oldData, $newStartTime, $duration, $reason);

        $booking->save();

        // Обновляем связанные слоты
        $this->updateBookingSlots($booking, $oldStartTime, $oldEndTime);

        return [
            'booking' => $booking,
            'old_time' => $oldStartTime,
            'new_time' => $newStartTime,
            'old_duration' => $oldData['duration_minutes'],
            'new_duration' => $duration,
            'reschedule_count' => $this->getRescheduleCount($booking),
        ];
    }

    /**
     * Обновить статус бронирования при переносе
     */
    private function updateBookingStatus(Booking $booking): void
    {
        if ($booking->status instanceof BookingStatus) {
            if ($booking->status === BookingStatus::PENDING) {
                $booking->status = BookingStatus::RESCHEDULED;
            }
        }
        // Для строковых статусов оставляем как есть
    }

    /**
     * Обновить метаданные бронирования
     */
    private function updateBookingMetadata(
        Booking $booking,
        User $user,
        array $oldData,
        Carbon $newStartTime,
        int $duration,
        ?string $reason
    ): void {
        $metadata = $booking->metadata ?? [];
        $reschedules = $metadata['reschedules'] ?? [];
        
        $reschedules[] = [
            'from_time' => $oldData['start_time']->toISOString(),
            'to_time' => $newStartTime->toISOString(),
            'from_duration' => $oldData['duration_minutes'],
            'to_duration' => $duration,
            'rescheduled_by' => $user->id,
            'rescheduled_at' => now()->toISOString(),
            'reason' => $reason,
            'user_role' => $booking->client_id === $user->id ? 'client' : 'master',
        ];
        
        $metadata['reschedules'] = $reschedules;
        $metadata['reschedule_count'] = count($reschedules);
        $metadata['last_rescheduled_at'] = now()->toISOString();
        $metadata['last_rescheduled_by'] = $user->id;
        
        $booking->metadata = $metadata;
    }

    /**
     * Обновление временных слотов
     */
    private function updateBookingSlots(
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
     * Массовый перенос бронирований
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
                
                $result = $this->execute($booking, $user, $newTime, null, $reason);
                
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => true,
                    'new_time' => $newTime->toISOString(),
                    'reschedule_count' => $result['reschedule_count'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'booking_id' => $bookingId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Отменить последний перенос (откат)
     */
    public function rollbackLastReschedule(Booking $booking, User $user): bool
    {
        $metadata = $booking->metadata ?? [];
        $reschedules = $metadata['reschedules'] ?? [];

        if (empty($reschedules)) {
            throw new \Exception('Нет переносов для отката');
        }

        $lastReschedule = array_pop($reschedules);
        
        // Проверяем права на откат
        if ($lastReschedule['rescheduled_by'] !== $user->id && !$user->hasRole(['admin', 'moderator'])) {
            throw new \Exception('Нет прав для отката чужого переноса');
        }

        return DB::transaction(function () use ($booking, $lastReschedule, $reschedules) {
            // Возвращаем старые данные
            $booking->start_time = Carbon::parse($lastReschedule['from_time']);
            $booking->end_time = $booking->start_time->copy()->addMinutes($lastReschedule['from_duration']);
            $booking->duration_minutes = $lastReschedule['from_duration'];

            // Обновляем метаданные
            $metadata = $booking->metadata ?? [];
            $metadata['reschedules'] = $reschedules;
            $metadata['reschedule_count'] = count($reschedules);
            $booking->metadata = $metadata;

            $booking->save();

            // Обновляем слоты
            $this->updateBookingSlots(
                $booking, 
                Carbon::parse($lastReschedule['to_time']), 
                Carbon::parse($lastReschedule['to_time'])->addMinutes($lastReschedule['to_duration'])
            );

            return true;
        });
    }

    /**
     * Получить историю переносов
     */
    public function getRescheduleHistory(Booking $booking): array
    {
        $metadata = $booking->metadata ?? [];
        return $metadata['reschedules'] ?? [];
    }

    /**
     * Получить количество переносов
     */
    public function getRescheduleCount(Booking $booking): int
    {
        $metadata = $booking->metadata ?? [];
        return count($metadata['reschedules'] ?? []);
    }

    /**
     * Проверить был ли откат переноса
     */
    public function wasRescheduled(Booking $booking): bool
    {
        return $this->getRescheduleCount($booking) > 0;
    }

    /**
     * Получить информацию о последнем переносе
     */
    public function getLastRescheduleInfo(Booking $booking): ?array
    {
        $reschedules = $this->getRescheduleHistory($booking);
        return empty($reschedules) ? null : end($reschedules);
    }

    /**
     * Получить статистику переносов
     */
    public function getRescheduleStats(Booking $booking): array
    {
        $reschedules = $this->getRescheduleHistory($booking);
        
        if (empty($reschedules)) {
            return [
                'total_count' => 0,
                'by_client' => 0,
                'by_master' => 0,
                'last_rescheduled' => null,
            ];
        }

        $byClient = count(array_filter($reschedules, fn($r) => $r['user_role'] === 'client'));
        $byMaster = count(array_filter($reschedules, fn($r) => $r['user_role'] === 'master'));
        $lastReschedule = end($reschedules);

        return [
            'total_count' => count($reschedules),
            'by_client' => $byClient,
            'by_master' => $byMaster,
            'last_rescheduled' => $lastReschedule['rescheduled_at'] ?? null,
            'last_rescheduled_by_role' => $lastReschedule['user_role'] ?? null,
        ];
    }
}