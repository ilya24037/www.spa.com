<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Models\MasterSchedule;
use App\Domain\Master\Repositories\MasterRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Сервис для управления расписанием мастеров
 */
class ScheduleService
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Создать расписание для мастера
     */
    public function createSchedule(int $masterId, array $scheduleData): MasterSchedule
    {
        return DB::transaction(function () use ($masterId, $scheduleData) {
            $master = $this->masterRepository->findById($masterId);
            
            if (!$master) {
                throw new \Exception('Мастер не найден');
            }

            $schedule = new MasterSchedule();
            $schedule->master_profile_id = $masterId;
            $schedule->day_of_week = $scheduleData['day_of_week'];
            $schedule->start_time = $scheduleData['start_time'];
            $schedule->end_time = $scheduleData['end_time'];
            $schedule->is_active = $scheduleData['is_active'] ?? true;
            $schedule->break_start = $scheduleData['break_start'] ?? null;
            $schedule->break_end = $scheduleData['break_end'] ?? null;
            $schedule->notes = $scheduleData['notes'] ?? null;
            $schedule->save();

            $this->clearScheduleCache($masterId);

            Log::info('Schedule created for master', [
                'master_id' => $masterId,
                'day_of_week' => $scheduleData['day_of_week']
            ]);

            return $schedule;
        });
    }

    /**
     * Обновить расписание
     */
    public function updateSchedule(int $scheduleId, array $scheduleData): MasterSchedule
    {
        return DB::transaction(function () use ($scheduleId, $scheduleData) {
            $schedule = MasterSchedule::findOrFail($scheduleId);
            
            $schedule->update([
                'day_of_week' => $scheduleData['day_of_week'] ?? $schedule->day_of_week,
                'start_time' => $scheduleData['start_time'] ?? $schedule->start_time,
                'end_time' => $scheduleData['end_time'] ?? $schedule->end_time,
                'is_active' => $scheduleData['is_active'] ?? $schedule->is_active,
                'break_start' => $scheduleData['break_start'] ?? $schedule->break_start,
                'break_end' => $scheduleData['break_end'] ?? $schedule->break_end,
                'notes' => $scheduleData['notes'] ?? $schedule->notes,
            ]);

            $this->clearScheduleCache($schedule->master_profile_id);

            Log::info('Schedule updated', [
                'schedule_id' => $scheduleId,
                'master_id' => $schedule->master_profile_id
            ]);

            return $schedule;
        });
    }

    /**
     * Установить еженедельное расписание для мастера
     */
    public function setWeeklySchedule(int $masterId, array $weeklySchedule): array
    {
        return DB::transaction(function () use ($masterId, $weeklySchedule) {
            $master = $this->masterRepository->findById($masterId);
            
            if (!$master) {
                throw new \Exception('Мастер не найден');
            }

            // Удаляем старое расписание
            MasterSchedule::where('master_profile_id', $masterId)->delete();

            $createdSchedules = [];

            foreach ($weeklySchedule as $daySchedule) {
                if (!empty($daySchedule['start_time']) && !empty($daySchedule['end_time'])) {
                    $schedule = $this->createSchedule($masterId, [
                        'day_of_week' => $daySchedule['day_of_week'],
                        'start_time' => $daySchedule['start_time'],
                        'end_time' => $daySchedule['end_time'],
                        'is_active' => $daySchedule['is_active'] ?? true,
                        'break_start' => $daySchedule['break_start'] ?? null,
                        'break_end' => $daySchedule['break_end'] ?? null,
                        'notes' => $daySchedule['notes'] ?? null,
                    ]);

                    $createdSchedules[] = $schedule;
                }
            }

            Log::info('Weekly schedule set for master', [
                'master_id' => $masterId,
                'days_count' => count($createdSchedules)
            ]);

            return $createdSchedules;
        });
    }

    /**
     * Получить расписание мастера
     */
    public function getMasterSchedule(int $masterId): array
    {
        $cacheKey = "master_schedule_{$masterId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($masterId) { // 30 минут
            $schedules = MasterSchedule::where('master_profile_id', $masterId)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->get();

            $weekSchedule = [];
            $days = ['понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'];

            foreach ($schedules as $schedule) {
                $weekSchedule[$schedule->day_of_week] = [
                    'id' => $schedule->id,
                    'day_name' => $days[$schedule->day_of_week - 1] ?? 'День ' . $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start,
                    'break_end' => $schedule->break_end,
                    'is_active' => $schedule->is_active,
                    'notes' => $schedule->notes,
                ];
            }

            return $weekSchedule;
        });
    }

    /**
     * Проверить доступность мастера в указанное время
     */
    public function isAvailable(int $masterId, Carbon $dateTime, int $durationMinutes = 60): bool
    {
        $dayOfWeek = $dateTime->dayOfWeekIso; // 1 = понедельник, 7 = воскресенье
        
        // Получаем расписание на этот день недели
        $schedule = MasterSchedule::where('master_profile_id', $masterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return false; // Нет расписания на этот день
        }

        $requestTime = $dateTime->format('H:i');
        $endTime = $dateTime->addMinutes($durationMinutes)->format('H:i');

        // Проверяем, попадает ли время в рабочие часы
        if ($requestTime < $schedule->start_time || $endTime > $schedule->end_time) {
            return false;
        }

        // Проверяем, не попадает ли время в перерыв
        if ($schedule->break_start && $schedule->break_end) {
            if (!($endTime <= $schedule->break_start || $requestTime >= $schedule->break_end)) {
                return false; // Время пересекается с перерывом
            }
        }

        // Проверяем существующие бронирования
        return $this->checkBookingConflicts($masterId, $dateTime, $durationMinutes);
    }

    /**
     * Получить доступные слоты для записи
     */
    public function getAvailableSlots(int $masterId, Carbon $date, int $durationMinutes = 60): array
    {
        $dayOfWeek = $date->dayOfWeekIso;
        
        $schedule = MasterSchedule::where('master_profile_id', $masterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return [];
        }

        $slots = [];
        $startTime = Carbon::createFromFormat('H:i', $schedule->start_time)->setDate($date->year, $date->month, $date->day);
        $endTime = Carbon::createFromFormat('H:i', $schedule->end_time)->setDate($date->year, $date->month, $date->day);

        // Генерируем слоты каждые 30 минут
        $slotInterval = 30;
        $current = $startTime->copy();

        while ($current->addMinutes($durationMinutes) <= $endTime) {
            // Пропускаем время перерыва
            if ($this->isInBreakTime($current, $schedule)) {
                $current->addMinutes($slotInterval);
                continue;
            }

            // Проверяем доступность
            if ($this->isAvailable($masterId, $current->copy(), $durationMinutes)) {
                $slots[] = [
                    'time' => $current->format('H:i'),
                    'datetime' => $current->toISOString(),
                    'available' => true
                ];
            }

            $current->addMinutes($slotInterval);
        }

        return $slots;
    }

    /**
     * Установить исключение в расписании (отпуск, больничный)
     */
    public function setScheduleException(int $masterId, Carbon $fromDate, Carbon $toDate, string $reason, string $type = 'vacation'): void
    {
        $master = $this->masterRepository->findById($masterId);
        
        if (!$master) {
            throw new \Exception('Мастер не найден');
        }

        // Логика для исключений в расписании может быть реализована через отдельную таблицу
        // Например schedule_exceptions
        
        $this->clearScheduleCache($masterId);

        Log::info('Schedule exception set', [
            'master_id' => $masterId,
            'from_date' => $fromDate->toDateString(),
            'to_date' => $toDate->toDateString(),
            'type' => $type,
            'reason' => $reason
        ]);
    }

    /**
     * Получить рабочие дни мастера на неделю
     */
    public function getWorkingDays(int $masterId): array
    {
        $schedules = MasterSchedule::where('master_profile_id', $masterId)
            ->where('is_active', true)
            ->pluck('day_of_week')
            ->toArray();

        $dayNames = [
            1 => 'Понедельник',
            2 => 'Вторник', 
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];

        $workingDays = [];
        foreach ($schedules as $dayNumber) {
            $workingDays[$dayNumber] = $dayNames[$dayNumber];
        }

        return $workingDays;
    }

    /**
     * Активировать/деактивировать день расписания
     */
    public function toggleScheduleDay(int $scheduleId, bool $isActive): MasterSchedule
    {
        $schedule = MasterSchedule::findOrFail($scheduleId);
        $schedule->update(['is_active' => $isActive]);

        $this->clearScheduleCache($schedule->master_profile_id);

        Log::info('Schedule day toggled', [
            'schedule_id' => $scheduleId,
            'master_id' => $schedule->master_profile_id,
            'is_active' => $isActive
        ]);

        return $schedule;
    }

    /**
     * Удалить расписание
     */
    public function deleteSchedule(int $scheduleId): bool
    {
        $schedule = MasterSchedule::findOrFail($scheduleId);
        $masterId = $schedule->master_profile_id;
        
        $deleted = $schedule->delete();
        
        if ($deleted) {
            $this->clearScheduleCache($masterId);
            
            Log::info('Schedule deleted', [
                'schedule_id' => $scheduleId,
                'master_id' => $masterId
            ]);
        }

        return $deleted;
    }

    /**
     * Проверить конфликты с существующими бронированиями
     */
    private function checkBookingConflicts(int $masterId, Carbon $dateTime, int $durationMinutes): bool
    {
        // Здесь должна быть логика проверки конфликтов с бронированиями
        // Возвращаем true если время свободно, false если занято
        
        // Пример проверки (нужна интеграция с системой бронирований):
        /*
        $endTime = $dateTime->copy()->addMinutes($durationMinutes);
        
        $conflicts = Booking::where('master_id', $masterId)
            ->where('status', 'confirmed')
            ->where(function($query) use ($dateTime, $endTime) {
                $query->whereBetween('start_time', [$dateTime, $endTime])
                      ->orWhereBetween('end_time', [$dateTime, $endTime])
                      ->orWhere(function($q) use ($dateTime, $endTime) {
                          $q->where('start_time', '<=', $dateTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->count();
            
        return $conflicts === 0;
        */
        
        return true; // Временно возвращаем true
    }

    /**
     * Проверить, попадает ли время в перерыв
     */
    private function isInBreakTime(Carbon $time, MasterSchedule $schedule): bool
    {
        if (!$schedule->break_start || !$schedule->break_end) {
            return false;
        }

        $timeString = $time->format('H:i');
        return $timeString >= $schedule->break_start && $timeString <= $schedule->break_end;
    }

    /**
     * Очистить кеш расписания
     */
    private function clearScheduleCache(int $masterId): void
    {
        Cache::forget("master_schedule_{$masterId}");
    }

    /**
     * Получить статистику расписания мастера
     */
    public function getScheduleStats(int $masterId): array
    {
        $schedules = MasterSchedule::where('master_profile_id', $masterId)->get();
        
        $totalWorkingDays = $schedules->where('is_active', true)->count();
        $totalWorkingHours = 0;
        
        foreach ($schedules->where('is_active', true) as $schedule) {
            $start = Carbon::createFromFormat('H:i', $schedule->start_time);
            $end = Carbon::createFromFormat('H:i', $schedule->end_time);
            $workingHours = $end->diffInHours($start);
            
            // Вычитаем время перерыва
            if ($schedule->break_start && $schedule->break_end) {
                $breakStart = Carbon::createFromFormat('H:i', $schedule->break_start);
                $breakEnd = Carbon::createFromFormat('H:i', $schedule->break_end);
                $breakHours = $breakEnd->diffInHours($breakStart);
                $workingHours -= $breakHours;
            }
            
            $totalWorkingHours += $workingHours;
        }

        return [
            'working_days_count' => $totalWorkingDays,
            'total_working_hours_per_week' => $totalWorkingHours,
            'average_hours_per_day' => $totalWorkingDays > 0 ? round($totalWorkingHours / $totalWorkingDays, 1) : 0,
            'has_breaks' => $schedules->whereNotNull('break_start')->count() > 0,
        ];
    }
}