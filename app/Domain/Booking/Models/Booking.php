<?php

namespace App\Domain\Booking\Models;

use App\Enums\BookingStatus;
use App\Enums\BookingType;
use App\Domain\User\Models\User;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Service\Models\Service;
use App\Domain\Payment\Models\Payment;
use App\Domain\Review\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_number',
        'client_id',
        'master_id',
        'master_profile_id', // Временно для совместимости
        'service_id',
        'type',
        'status',
        'booking_date', // Временно для совместимости
        'start_time',
        'end_time',
        'duration',
        'duration_minutes',
        'base_price',
        'service_price',
        'delivery_fee',
        'travel_fee', // Временно для совместимости
        'discount_amount',
        'total_price',
        'deposit_amount',
        'paid_amount',
        'payment_method',
        'payment_status',
        'address',
        'address_details',
        'client_address',
        'master_address',
        'client_name',
        'client_phone',
        'client_email',
        'master_phone',
        'client_comment',
        'notes',
        'internal_notes',
        'equipment_required',
        'platform',
        'meeting_link',
        'is_home_service', // Временно для совместимости
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'cancellation_reason',
        'cancelled_by',
        'reminder_sent',
        'reminder_sent_at',
        'source',
        'metadata',
    ];

    protected $casts = [
        'type' => BookingType::class,
        'status' => BookingStatus::class,
        'booking_date' => 'date', // Временно для совместимости
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer', // Временно для совместимости
        'duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'service_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'travel_fee' => 'decimal:2', // Временно для совместимости
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'equipment_required' => 'array',
        'metadata' => 'array',
        'is_home_service' => 'boolean', // Временно для совместимости
        'reminder_sent' => 'boolean', // Временно для совместимости
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime', // Временно для совместимости
        'reminder_sent_at' => 'datetime',
        'extra_data' => 'array', // Временно для совместимости
    ];

    // Статусы бронирования (временно для совместимости)
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    // Методы оплаты (временно для совместимости)
    const PAYMENT_CASH = 'cash';
    const PAYMENT_CARD = 'card';
    const PAYMENT_ONLINE = 'online';
    const PAYMENT_TRANSFER = 'transfer';

    protected $dates = [
        'start_time',
        'end_time',
        'booking_date',
        'cancelled_at',
        'confirmed_at',
        'completed_at',
        'reminder_sent_at',
        'paid_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Генерация номера бронирования при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = 'B' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    // =================== СВЯЗИ ===================

    /**
     * Клиент
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Мастер
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    /**
     * Профиль мастера (для совместимости)
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Услуга
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Услуги бронирования
     */
    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    /**
     * Слоты бронирования
     */
    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    /**
     * Платеж
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Отзывы
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Кто отменил бронирование
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // =================== МЕТОДЫ ===================

    /**
     * Можно ли отменить бронирование
     */
    public function canCancel(): bool
    {
        // Нельзя отменить если уже отменено или завершено
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED])) {
            return false;
        }

        // Нельзя отменить за 2 часа до начала
        $bookingDateTime = Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->start_time);
        if ($bookingDateTime->diffInHours(now()) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Подтвердить бронирование
     */
    public function confirm(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now()
        ]);

        // TODO: Отправить SMS/Email клиенту

        return true;
    }

    /**
     * Отменить бронирование
     */
    public function cancel(string $reason, int $userId): bool
    {
        if (!$this->canCancel()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $userId
        ]);

        // TODO: Отправить уведомление

        return true;
    }

    /**
     * Завершить бронирование
     */
    public function complete(): bool
    {
        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payment_status' => 'paid',
            'paid_at' => now()
        ]);

        // Обновить статистику мастера
        $this->masterProfile->increment('completed_bookings');

        return true;
    }

    // =================== НОВЫЕ МЕТОДЫ С ENUMS ===================

    /**
     * Получить доступен ли статус
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status instanceof BookingStatus ? $this->status->isActive() : in_array($this->status, [
            self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Получить завершено ли бронирование
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status instanceof BookingStatus ? $this->status->isCompleted() : $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Получить отменено ли бронирование
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->status instanceof BookingStatus ? $this->status->isCancelled() : in_array($this->status, [
            self::STATUS_CANCELLED, self::STATUS_NO_SHOW
        ]);
    }

    /**
     * Подтвердить бронирование (новый метод)
     */
    public function confirmBooking(): self
    {
        if ($this->status instanceof BookingStatus) {
            if (!$this->status->canTransitionTo(BookingStatus::CONFIRMED)) {
                throw new \Exception('Нельзя подтвердить бронирование в текущем статусе');
            }
            $this->status = BookingStatus::CONFIRMED;
        } else {
            // Старая логика для совместимости
            if ($this->status !== self::STATUS_PENDING) {
                throw new \Exception('Можно подтвердить только ожидающие бронирования');
            }
            $this->status = self::STATUS_CONFIRMED;
        }
        
        $this->confirmed_at = now();
        $this->save();
        return $this;
    }

    /**
     * Начать выполнение услуги
     */
    public function startService(): self
    {
        if ($this->status instanceof BookingStatus) {
            if (!$this->status->canTransitionTo(BookingStatus::IN_PROGRESS)) {
                throw new \Exception('Нельзя начать услугу в текущем статусе');
            }
            $this->status = BookingStatus::IN_PROGRESS;
        } else {
            if ($this->status !== self::STATUS_CONFIRMED) {
                throw new \Exception('Можно начать только подтвержденные услуги');
            }
            $this->status = self::STATUS_IN_PROGRESS;
        }
        
        $this->save();
        return $this;
    }

    /**
     * Завершить услугу (новый метод)
     */
    public function completeService(): self
    {
        if ($this->status instanceof BookingStatus) {
            if (!$this->status->canTransitionTo(BookingStatus::COMPLETED)) {
                throw new \Exception('Нельзя завершить услугу в текущем статусе');
            }
            $this->status = BookingStatus::COMPLETED;
        } else {
            if ($this->status !== self::STATUS_IN_PROGRESS) {
                throw new \Exception('Можно завершить только выполняющиеся услуги');
            }
            $this->status = self::STATUS_COMPLETED;
        }
        
        $this->completed_at = now();
        $this->save();
        
        // Обновить статистику мастера
        if ($this->masterProfile) {
            $this->masterProfile->increment('completed_bookings');
        }
        return $this;
    }

    /**
     * Отменить бронирование (новый метод)
     */
    public function cancelBooking(string $reason, bool $byClient = true): self
    {
        if ($this->status instanceof BookingStatus) {
            $newStatus = $byClient ? BookingStatus::CANCELLED_BY_CLIENT : BookingStatus::CANCELLED_BY_MASTER;
            if (!$this->status->canTransitionTo($newStatus)) {
                throw new \Exception('Нельзя отменить бронирование в текущем статусе');
            }
            $this->status = $newStatus;
        } else {
            if (!$this->canCancel()) {
                throw new \Exception('Нельзя отменить данное бронирование');
            }
            $this->status = self::STATUS_CANCELLED;
        }
        
        $this->cancellation_reason = $reason;
        $this->cancelled_at = now();
        $this->cancelled_by = auth()->id();
        $this->save();
        return $this;
    }

    /**
     * Получить форматированную продолжительность
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = $this->duration_minutes ?? $this->duration ?? 0;
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours} ч {$remainingMinutes} мин";
        } elseif ($hours > 0) {
            return "{$hours} ч";
        } else {
            return "{$remainingMinutes} мин";
        }
    }

    /**
     * Можно ли отменить (новая версия)
     */
    public function canCancelBooking(): bool
    {
        if ($this->status instanceof BookingStatus) {
            return $this->status->canBeCancelled();
        }
        
        return $this->canCancel(); // Старый метод для совместимости
    }

    // =================== SCOPES ===================

    /**
     * Только активные бронирования
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Бронирования на дату
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('booking_date', $date);
    }

    /**
     * Предстоящие бронирования
     */
    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', today())
                    ->orderBy('booking_date')
                    ->orderBy('start_time');
    }

    // =================== АТРИБУТЫ ===================

    /**
     * Полное время бронирования
     */
    public function getFullDateTimeAttribute()
    {
        return Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->start_time);
    }

    /**
     * Статус на русском
     */
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Ожидает подтверждения',
            self::STATUS_CONFIRMED => 'Подтверждено',
            self::STATUS_IN_PROGRESS => 'Выполняется',
            self::STATUS_COMPLETED => 'Завершено',
            self::STATUS_CANCELLED => 'Отменено',
            self::STATUS_NO_SHOW => 'Клиент не пришел',
            default => 'Неизвестно'
        };
    }

    /**
     * Цвет статуса для UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_IN_PROGRESS => 'indigo',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_NO_SHOW => 'gray',
            default => 'gray'
        };
    }
}