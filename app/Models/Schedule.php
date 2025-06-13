<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_profile_id', 'day_of_week', 'start_time', 'end_time',
        'is_working_day', 'break_start', 'break_end', 'slot_duration',
        'buffer_time', 'is_flexible', 'notes'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
        'is_working_day' => 'boolean',
        'is_flexible' => 'boolean'
    ];

    const DAYS = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье'
    ];

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function getAvailableSlots(Carbon $date): array
    {
        if (!$this->is_working_day) return [];

        $slots = [];
        $current = Carbon::parse($date->format('Y-m-d') . ' ' . $this->start_time);
        $end = Carbon::parse($date->format('Y-m-d') . ' ' . $this->end_time);

        while ($current < $end) {
            // Пропускаем перерыв
            if ($this->break_start && $this->break_end) {
                $breakStart = Carbon::parse($date->format('Y-m-d') . ' ' . $this->break_start);
                $breakEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $this->break_end);
                
                if ($current >= $breakStart && $current < $breakEnd) {
                    $current = $breakEnd;
                    continue;
                }
            }

            $slots[] = $current->format('H:i');
            $current->addMinutes($this->slot_duration + $this->buffer_time);
        }

        return $slots;
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }
}