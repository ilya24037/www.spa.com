<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterSchedule;
use App\Domain\Master\Repositories\ScheduleRepository;
use Carbon\Carbon;

/**
 * Сервис проверки доступности мастера
 */
class ScheduleAvailabilityChecker
{
    private ScheduleRepository $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * Проверить доступность мастера в указанное время
     */
    public function isAvailable(int $masterId, Carbon $dateTime, int $durationMinutes = 60): bool
    {
        $dayOfWeek = $dateTime->dayOfWeekIso; // 1 = понедельник, 7 = воскресенье
        
        // Получаем расписание на этот день недели
        $schedule = $this->scheduleRepository->getByMasterAndDay($masterId, $dayOfWeek);

        if (!$schedule) {
            return false; // Нет расписания на этот день
        }

        if (!$this->isTimeWithinWorkingHours($dateTime, $durationMinutes, $schedule)) {
            return false;
        }

        if ($this->isTimeInBreak($dateTime, $durationMinutes, $schedule)) {
            return false;
        }

        // Проверяем существующие бронирования
        return $this->checkBookingConflicts($masterId, $dateTime, $durationMinutes);
    }

    /**
     * Проверить, попадает ли время в рабочие часы
     */
    public function isTimeWithinWorkingHours(Carbon $dateTime, int $durationMinutes, MasterSchedule $schedule): bool
    {
        $requestTime = $dateTime->format('H:i');
        $endTime = $dateTime->copy()->addMinutes($durationMinutes)->format('H:i');

        return $requestTime >= $schedule->start_time && $endTime <= $schedule->end_time;
    }

    /**
     * Проверить, попадает ли время в перерыв
     */
    public function isTimeInBreak(Carbon $dateTime, int $durationMinutes, MasterSchedule $schedule): bool
    {
        if (!$schedule->break_start || !$schedule->break_end) {
            return false;
        }

        $requestTime = $dateTime->format('H:i');
        $endTime = $dateTime->copy()->addMinutes($durationMinutes)->format('H:i');

        // Время пересекается с перерывом
        return !($endTime <= $schedule->break_start || $requestTime >= $schedule->break_end);
    }

    /**
     * Проверить, попадает ли конкретное время в перерыв
     */
    public function isInBreakTime(Carbon $time, MasterSchedule $schedule): bool
    {
        if (!$schedule->break_start || !$schedule->break_end) {
            return false;
        }

        $timeString = $time->format('H:i');
        return $timeString >= $schedule->break_start && $timeString <= $schedule->break_end;
    }

    /**
     * Проверить конфликты с существующими бронированиями
     */
    public function checkBookingConflicts(int $masterId, Carbon $dateTime, int $durationMinutes): bool
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
     * Проверить доступность на несколько дней вперед
     */
    public function checkAvailabilityRange(int $masterId, Carbon $startDate, int $days, int $durationMinutes = 60): array
    {
        $availability = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayOfWeek = $date->dayOfWeekIso;
            
            $schedule = $this->scheduleRepository->getByMasterAndDay($masterId, $dayOfWeek);
            
            $availability[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'day_name' => $date->translatedFormat('l'),
                'has_schedule' => (bool) $schedule,
                'is_working_day' => (bool) $schedule,
                'working_hours' => $schedule ? [
                    'start' => $schedule->start_time,
                    'end' => $schedule->end_time,
                    'break_start' => $schedule->break_start,
                    'break_end' => $schedule->break_end,
                ] : null
            ];
        }
        
        return $availability;
    }
}