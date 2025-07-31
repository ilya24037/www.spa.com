<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Услуги в составе бронирования
 * Для пакетных услуг или когда в одном бронировании несколько услуг
 */
class BookingService extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'service_id',
        'quantity',
        'unit_price',
        'total_price',
        'duration_minutes',
        'start_offset_minutes',
        'notes',
        'is_completed',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'start_offset_minutes' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected $dates = [
        'completed_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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

    public function getStartTimeAttribute(): ?\Carbon\Carbon
    {
        if (!$this->booking || !$this->booking->start_time) {
            return null;
        }

        return $this->booking->start_time->copy()->addMinutes($this->start_offset_minutes ?? 0);
    }

    public function getEndTimeAttribute(): ?\Carbon\Carbon
    {
        $startTime = $this->start_time;
        return $startTime ? $startTime->copy()->addMinutes($this->duration_minutes) : null;
    }

    // Business Logic
    public function markCompleted(): self
    {
        $this->is_completed = true;
        $this->completed_at = now();
        $this->save();

        return $this;
    }

    public function calculateTotalPrice(): self
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();

        return $this;
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('start_offset_minutes');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (BookingService $bookingService) {
            if (!$bookingService->total_price && $bookingService->quantity && $bookingService->unit_price) {
                $bookingService->total_price = $bookingService->quantity * $bookingService->unit_price;
            }
            
            if (!$bookingService->sort_order) {
                $maxSortOrder = self::where('booking_id', $bookingService->booking_id)->max('sort_order') ?? 0;
                $bookingService->sort_order = $maxSortOrder + 1;
            }
        });

        static::updating(function (BookingService $bookingService) {
            if ($bookingService->isDirty(['quantity', 'unit_price'])) {
                $bookingService->total_price = $bookingService->quantity * $bookingService->unit_price;
            }
        });
    }
}