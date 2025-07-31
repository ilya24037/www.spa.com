<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * Временные слоты бронирования
 * Для детального контроля времени и ресурсов
 */
class BookingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'master_id',
        'resource_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_blocked',
        'is_break',
        'is_preparation',
        'notes',
        'resource_type',
        'resource_name',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'is_blocked' => 'boolean',
        'is_break' => 'boolean',
        'is_preparation' => 'boolean',
    ];

    protected $dates = [
        'start_time',
        'end_time',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    // Polymorphic relationship для ресурсов (комнаты, оборудование и т.д.)
    public function resource()
    {
        return $this->morphTo(__FUNCTION__, 'resource_type', 'resource_id');
    }

    // Accessors
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours} ч {$minutes} мин";
        } elseif ($hours > 0) {
            return "{$hours} ч";
        } else {
            return "{$minutes} мин";
        }
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->start_time->lte(now()) && $this->end_time->gte(now());
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->start_time->gt(now());
    }

    public function getIsPastAttribute(): bool
    {
        return $this->end_time->lt(now());
    }

    public function getSlotTypeAttribute(): string
    {
        if ($this->is_break) return 'break';
        if ($this->is_preparation) return 'preparation';
        if ($this->is_blocked) return 'blocked';
        return 'service';
    }

    public function getSlotTypeLabelAttribute(): string
    {
        return match($this->slot_type) {
            'break' => 'Перерыв',
            'preparation' => 'Подготовка',
            'blocked' => 'Заблокировано',
            'service' => 'Услуга',
            default => 'Неизвестно',
        };
    }

    public function getSlotTypeColorAttribute(): string
    {
        return match($this->slot_type) {
            'break' => '#F59E0B',      // amber
            'preparation' => '#8B5CF6', // violet
            'blocked' => '#EF4444',     // red
            'service' => '#10B981',     // green
            default => '#6B7280',       // gray
        };
    }

    // Business Logic
    public function isOverlapping(Carbon $startTime, Carbon $endTime): bool
    {
        return $this->start_time->lt($endTime) && $this->end_time->gt($startTime);
    }

    public function extend(int $minutes): self
    {
        $this->end_time = $this->end_time->addMinutes($minutes);
        $this->duration_minutes += $minutes;
        $this->save();

        return $this;
    }

    public function shorten(int $minutes): self
    {
        if ($this->duration_minutes <= $minutes) {
            throw new \InvalidArgumentException('Нельзя сократить слот меньше чем на его продолжительность');
        }

        $this->end_time = $this->end_time->subMinutes($minutes);
        $this->duration_minutes -= $minutes;
        $this->save();

        return $this;
    }

    public function moveBy(int $minutes): self
    {
        $this->start_time = $this->start_time->addMinutes($minutes);
        $this->end_time = $this->end_time->addMinutes($minutes);
        $this->save();

        return $this;
    }

    public function split(Carbon $splitTime): self
    {
        if ($splitTime->lte($this->start_time) || $splitTime->gte($this->end_time)) {
            throw new \InvalidArgumentException('Время разделения должно быть внутри слота');
        }

        // Создаем новый слот для второй части
        $secondSlot = $this->replicate();
        $secondSlot->start_time = $splitTime;
        $secondSlot->duration_minutes = $this->end_time->diffInMinutes($splitTime);
        $secondSlot->save();

        // Обновляем текущий слот для первой части
        $this->end_time = $splitTime;
        $this->duration_minutes = $this->start_time->diffInMinutes($splitTime);
        $this->save();

        return $secondSlot;
    }

    public function block(string $reason = null): self
    {
        $this->is_blocked = true;
        if ($reason) {
            $this->notes = $reason;
        }
        $this->save();

        return $this;
    }

    public function unblock(): self
    {
        $this->is_blocked = false;
        $this->save();

        return $this;
    }

    // Scopes
    public function scopeForMaster($query, int $masterId)
    {
        return $query->where('master_id', $masterId);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('start_time', $date);
    }

    public function scopeForDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('start_time', '<=', $now)
                     ->where('end_time', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_time', '<', now());
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeServiceSlots($query)
    {
        return $query->where('is_break', false)
                     ->where('is_preparation', false)
                     ->where('is_blocked', false);
    }

    public function scopeBreaks($query)
    {
        return $query->where('is_break', true);
    }

    public function scopePreparation($query)
    {
        return $query->where('is_preparation', true);
    }

    public function scopeOverlapping($query, Carbon $startTime, Carbon $endTime)
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
        });
    }

    public function scopeForResource($query, string $resourceType, int $resourceId)
    {
        return $query->where('resource_type', $resourceType)
                     ->where('resource_id', $resourceId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('start_time');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (BookingSlot $slot) {
            // Автоматически вычисляем продолжительность если не указана
            if (!$slot->duration_minutes && $slot->start_time && $slot->end_time) {
                $slot->duration_minutes = $slot->start_time->diffInMinutes($slot->end_time);
            }
            
            // Автоматически вычисляем end_time если не указано
            if (!$slot->end_time && $slot->start_time && $slot->duration_minutes) {
                $slot->end_time = $slot->start_time->copy()->addMinutes($slot->duration_minutes);
            }
        });

        static::updating(function (BookingSlot $slot) {
            // Обновляем продолжительность при изменении времени
            if ($slot->isDirty(['start_time', 'end_time']) && $slot->start_time && $slot->end_time) {
                $slot->duration_minutes = $slot->start_time->diffInMinutes($slot->end_time);
            }
            
            // Обновляем end_time при изменении продолжительности
            if ($slot->isDirty(['start_time', 'duration_minutes']) && $slot->start_time && $slot->duration_minutes) {
                $slot->end_time = $slot->start_time->copy()->addMinutes($slot->duration_minutes);
            }
        });
    }
}