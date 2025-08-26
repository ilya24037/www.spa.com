<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель для работы с расписанием мастера
 * Управляет графиком работы и исключениями
 */
class MasterSchedule extends Model
{
    /**
     * Связь с расписанием
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\Schedule::class, 'master_profile_id');
    }

    /**
     * Связь с исключениями расписания
     */
    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\ScheduleException::class, 'master_profile_id');
    }

    /**
     * Проверка доступности мастера в данный момент
     */
    public function isAvailableNow(int $masterProfileId, bool $isActive): bool
    {
        if (!$isActive) {
            return false;
        }

        $now = now();
        $dow = $now->dayOfWeek;
        $timeString = $now->format('H:i');

        $schedule = \App\Domain\Master\Models\Schedule::where('master_profile_id', $masterProfileId)
            ->where('day_of_week', $dow)
            ->where('is_working_day', true)
            ->first();

        if (!$schedule) {
            // Если расписания нет, проверяем есть ли активные зоны обслуживания
            return \App\Domain\Master\Models\WorkZone::where('master_profile_id', $masterProfileId)
                ->where('is_active', true)
                ->exists();
        }

        return $timeString >= $schedule->start_time && $timeString <= $schedule->end_time;
    }

    /**
     * Получить расписание на день недели
     */
    public function getScheduleForDay(int $masterProfileId, int $dayOfWeek)
    {
        return \App\Domain\Master\Models\Schedule::where('master_profile_id', $masterProfileId)
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }

    /**
     * Получить исключения расписания на дату
     */
    public function getExceptionsForDate(int $masterProfileId, string $date)
    {
        return \App\Domain\Master\Models\ScheduleException::where('master_profile_id', $masterProfileId)
            ->whereDate('exception_date', $date)
            ->get();
    }

    /**
     * Проверка рабочего дня
     */
    public function isWorkingDay(int $masterProfileId, string $date): bool
    {
        $dayOfWeek = now()->parse($date)->dayOfWeek;
        
        // Проверяем исключения
        $exception = $this->getExceptionsForDate($masterProfileId, $date)->first();
        if ($exception) {
            return !$exception->is_day_off;
        }

        // Проверяем обычное расписание
        $schedule = $this->getScheduleForDay($masterProfileId, $dayOfWeek);
        return $schedule ? $schedule->is_working_day : false;
    }

    /**
     * Получить рабочие часы на дату
     */
    public function getWorkingHours(int $masterProfileId, string $date): ?array
    {
        $dayOfWeek = now()->parse($date)->dayOfWeek;
        
        // Проверяем исключения
        $exception = $this->getExceptionsForDate($masterProfileId, $date)->first();
        if ($exception && !$exception->is_day_off) {
            return [
                'start_time' => $exception->start_time,
                'end_time' => $exception->end_time,
            ];
        }

        // Обычное расписание
        $schedule = $this->getScheduleForDay($masterProfileId, $dayOfWeek);
        if ($schedule && $schedule->is_working_day) {
            return [
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ];
        }

        return null;
    }
}