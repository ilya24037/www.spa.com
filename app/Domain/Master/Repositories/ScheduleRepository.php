<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для работы с расписанием мастеров
 */
class ScheduleRepository
{
    /**
     * Создать запись расписания
     */
    public function create(array $scheduleData): MasterSchedule
    {
        $schedule = new MasterSchedule();
        $schedule->fill($scheduleData);
        $schedule->save();
        
        return $schedule;
    }

    /**
     * Обновить расписание
     */
    public function update(int $scheduleId, array $scheduleData): MasterSchedule
    {
        $schedule = MasterSchedule::findOrFail($scheduleId);
        $schedule->update($scheduleData);
        
        return $schedule;
    }

    /**
     * Найти расписание по ID
     */
    public function findById(int $scheduleId): ?MasterSchedule
    {
        return MasterSchedule::find($scheduleId);
    }

    /**
     * Получить расписание мастера
     */
    public function getByMasterId(int $masterId, bool $onlyActive = true): Collection
    {
        $query = MasterSchedule::where('master_profile_id', $masterId);
        
        if ($onlyActive) {
            $query->where('is_active', true);
        }
        
        return $query->orderBy('day_of_week')->get();
    }

    /**
     * Получить расписание на конкретный день недели
     */
    public function getByMasterAndDay(int $masterId, int $dayOfWeek): ?MasterSchedule
    {
        return MasterSchedule::where('master_profile_id', $masterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Удалить все расписание мастера
     */
    public function deleteByMasterId(int $masterId): int
    {
        return MasterSchedule::where('master_profile_id', $masterId)->delete();
    }

    /**
     * Удалить конкретное расписание
     */
    public function delete(int $scheduleId): bool
    {
        $schedule = MasterSchedule::find($scheduleId);
        return $schedule ? $schedule->delete() : false;
    }

    /**
     * Получить рабочие дни мастера
     */
    public function getWorkingDays(int $masterId): array
    {
        return MasterSchedule::where('master_profile_id', $masterId)
            ->where('is_active', true)
            ->pluck('day_of_week')
            ->toArray();
    }

    /**
     * Активировать/деактивировать день расписания
     */
    public function toggleActive(int $scheduleId, bool $isActive): MasterSchedule
    {
        $schedule = MasterSchedule::findOrFail($scheduleId);
        $schedule->update(['is_active' => $isActive]);
        
        return $schedule;
    }

    /**
     * Массовое создание расписания
     */
    public function createMultiple(int $masterId, array $schedules): array
    {
        return DB::transaction(function () use ($masterId, $schedules) {
            $created = [];
            
            foreach ($schedules as $scheduleData) {
                $scheduleData['master_profile_id'] = $masterId;
                $created[] = $this->create($scheduleData);
            }
            
            return $created;
        });
    }
}