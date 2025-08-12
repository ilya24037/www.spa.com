<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'client_id',
        'master_profile_id',
        'service_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration',
        'address',
        'address_details',
        'is_home_service',
        'service_price',
        'travel_fee',
        'discount_amount',
        'total_price',
        'payment_method',
        'payment_status',
        'status',
        'client_name',
        'client_phone',
        'client_email',
        'client_comment',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'reminder_sent',
        'source'
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_home_service' => 'boolean',
        'reminder_sent' => 'boolean',
        'review_requested' => 'boolean',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'paid_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'extra_data' => 'array'
    ];

    // Статусы бронирования
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    // Методы оплаты
    const PAYMENT_CASH = 'cash';
    const PAYMENT_CARD = 'card';
    const PAYMENT_ONLINE = 'online';
    const PAYMENT_TRANSFER = 'transfer';

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
     * Профиль мастера
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Мастер (через профиль)
     */
    public function master()
    {
        return $this->hasOneThrough(User::class, MasterProfile::class, 'id', 'id', 'master_profile_id', 'user_id');
    }

    /**
     * Услуга
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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