<?php

namespace App\Domain\Ad\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Расписание объявления
 */
class AdSchedule extends Model
{
    use HasFactory;

    protected $table = 'ad_schedules';

    protected $fillable = [
        'ad_id',
        'schedule',
        'schedule_notes',
        'working_days',
        'working_hours',
    ];

    protected $casts = [
        'schedule' => 'array',
        'working_days' => 'array',
        'working_hours' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Получить рабочие дни в читаемом виде
     */
    public function getFormattedWorkingDaysAttribute(): string
    {
        if (empty($this->working_days)) {
            return 'Не указано';
        }

        $dayNames = [
            'monday' => 'Пн',
            'tuesday' => 'Вт',
            'wednesday' => 'Ср',
            'thursday' => 'Чт',
            'friday' => 'Пт',
            'saturday' => 'Сб',
            'sunday' => 'Вс',
        ];

        $workingDayNames = [];
        foreach ($this->working_days as $day) {
            if (isset($dayNames[$day])) {
                $workingDayNames[] = $dayNames[$day];
            }
        }

        return implode(', ', $workingDayNames);
    }

    /**
     * Получить рабочие часы в читаемом виде
     */
    public function getFormattedWorkingHoursAttribute(): string
    {
        if (empty($this->working_hours) || !isset($this->working_hours['from']) || !isset($this->working_hours['to'])) {
            return 'Не указано';
        }

        return $this->working_hours['from'] . ' - ' . $this->working_hours['to'];
    }

    /**
     * Проверить работает ли сегодня
     */
    public function isWorkingToday(): bool
    {
        if (empty($this->working_days)) {
            return false;
        }

        $today = strtolower(Carbon::now()->format('l')); // monday, tuesday, etc.
        
        return in_array($today, $this->working_days);
    }

    /**
     * Проверить работает ли сейчас
     */
    public function isWorkingNow(): bool
    {
        if (!$this->isWorkingToday()) {
            return false;
        }

        if (empty($this->working_hours) || !isset($this->working_hours['from']) || !isset($this->working_hours['to'])) {
            return true; // Если часы не указаны, считаем что работает круглосуточно
        }

        $now = Carbon::now()->format('H:i');
        $from = $this->working_hours['from'];
        $to = $this->working_hours['to'];

        return $now >= $from && $now <= $to;
    }

    /**
     * Получить следующий рабочий день
     */
    public function getNextWorkingDay(): ?Carbon
    {
        if (empty($this->working_days)) {
            return null;
        }

        $dayMapping = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0,
        ];

        $workingDayNumbers = [];
        foreach ($this->working_days as $day) {
            if (isset($dayMapping[$day])) {
                $workingDayNumbers[] = $dayMapping[$day];
            }
        }

        if (empty($workingDayNumbers)) {
            return null;
        }

        sort($workingDayNumbers);
        $today = Carbon::now()->dayOfWeek;

        // Ищем следующий рабочий день
        foreach ($workingDayNumbers as $workingDay) {
            if ($workingDay > $today) {
                return Carbon::now()->next($workingDay);
            }
        }

        // Если не нашли в этой неделе, берем первый день следующей недели
        return Carbon::now()->next($workingDayNumbers[0]);
    }

    /**
     * Проверить заполненность расписания
     */
    public function isComplete(): bool
    {
        return !empty($this->working_days) || !empty($this->schedule);
    }
}