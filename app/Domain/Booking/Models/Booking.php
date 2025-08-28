<?php

namespace App\Domain\Booking\Models;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\BookingType;
use App\Domain\Booking\Services\BookingStatusManager;
use App\Domain\Booking\Services\BookingValidationService;
use App\Domain\Booking\Services\BookingFormatter;
use App\Support\Traits\JsonFieldsTrait;
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

/**
 * Упрощенная модель бронирования
 * Делегирует сложную логику специализированным сервисам
 */
class Booking extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'booking_number',
        'client_id',
        'master_id',
        'master_profile_id',
        'service_id',
        'type',
        'status',
        'booking_date',
        'start_time',
        'end_time',
        'duration',
        'duration_minutes',
        'base_price',
        'service_price',
        'delivery_fee',
        'travel_fee',
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
        'is_home_service',
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

    protected $jsonFields = [
        'equipment_required',
        'metadata',
        'extra_data',
    ];

    protected $casts = [
        'type' => BookingType::class,
        'status' => BookingStatus::class,
        'booking_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'service_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'travel_fee' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_home_service' => 'boolean',
        'reminder_sent' => 'boolean',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    // Константы статусов для совместимости
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    // Константы способов оплаты для совместимости
    const PAYMENT_CASH = 'cash';
    const PAYMENT_CARD = 'card';
    const PAYMENT_ONLINE = 'online';
    const PAYMENT_TRANSFER = 'transfer';

    // =================== СВЯЗИ ===================

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function bookingServices(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(BookingHistory::class)->latest();
    }

    // =================== МЕТОДЫ ЧЕРЕЗ СЕРВИСЫ ===================

    /**
     * Подтвердить бронирование
     */
    public function confirm(): bool
    {
        return app(BookingStatusManager::class)->confirm($this);
    }

    /**
     * Отменить бронирование
     */
    public function cancel(string $reason, int $userId, bool $byClient = true): bool
    {
        return app(BookingStatusManager::class)->cancel($this, $reason, $userId, $byClient);
    }

    /**
     * Завершить бронирование
     */
    public function complete(): bool
    {
        return app(BookingStatusManager::class)->complete($this);
    }

    /**
     * Начать выполнение услуги
     */
    public function startService(): bool
    {
        return app(BookingStatusManager::class)->startService($this);
    }

    /**
     * Можно ли отменить бронирование
     */
    public function canCancel(): bool
    {
        return app(BookingStatusManager::class)->canCancel($this);
    }

    /**
     * Можно ли подтвердить бронирование
     */
    public function canConfirm(): bool
    {
        return app(BookingStatusManager::class)->canConfirm($this);
    }

    /**
     * Можно ли завершить бронирование
     */
    public function canComplete(): bool
    {
        return app(BookingStatusManager::class)->canComplete($this);
    }

    /**
     * Можно ли изменить бронирование
     */
    public function canModify(): bool
    {
        // Можно изменить только ожидающие подтверждения или подтвержденные бронирования
        return in_array($this->status, [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            && $this->start_time > now()->addHours(2);
    }

    /**
     * Можно ли оставить отзыв
     */
    public function canLeaveReview(int $userId): bool
    {
        // Отзыв можно оставить только после завершения бронирования
        // и только клиент или мастер могут оставить отзыв
        return $this->status === BookingStatus::COMPLETED 
            && ($this->client_id === $userId || $this->master_id === $userId)
            && !$this->reviews()->where('user_id', $userId)->exists();
    }

    /**
     * Можно ли сделать возврат
     */
    public function canRefund(): bool
    {
        // Возврат возможен для отмененных бронирований с предоплатой
        return $this->status === BookingStatus::CANCELLED 
            && $this->deposit_amount > 0
            && $this->paid_amount > 0
            && $this->cancelled_at > now()->subDays(7); // в течение 7 дней после отмены
    }

    // =================== АТРИБУТЫ ЧЕРЕЗ СЕРВИСЫ ===================

    public function getIsActiveAttribute(): bool
    {
        return app(BookingStatusManager::class)->isActive($this);
    }

    public function getIsCompletedAttribute(): bool
    {
        return app(BookingStatusManager::class)->isCompleted($this);
    }

    public function getIsCancelledAttribute(): bool
    {
        return app(BookingStatusManager::class)->isCancelled($this);
    }

    public function getFormattedDurationAttribute(): string
    {
        return app(BookingFormatter::class)->formatDuration($this);
    }

    public function getFullDateTimeAttribute(): ?Carbon
    {
        return app(BookingFormatter::class)->getFullDateTime($this);
    }

    public function getStatusTextAttribute(): string
    {
        return app(BookingFormatter::class)->getStatusText($this);
    }

    public function getStatusColorAttribute(): string
    {
        return app(BookingFormatter::class)->getStatusColor($this);
    }

    // =================== SCOPES ===================

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS
        ]);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('booking_date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', today())
                    ->orderBy('booking_date')
                    ->orderBy('start_time');
    }

    // =================== ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ ===================

    /**
     * Получить информацию об оплате
     */
    public function getPaymentInfo(): array
    {
        return app(BookingFormatter::class)->getPaymentInfo($this);
    }

    /**
     * Получить короткое описание
     */
    public function getShortDescription(): string
    {
        return app(BookingFormatter::class)->getShortDescription($this);
    }

    /**
     * Получить полное описание
     */
    public function getFullDescription(): string
    {
        return app(BookingFormatter::class)->getFullDescription($this);
    }

    /**
     * Форматировать для календаря
     */
    public function formatForCalendar(): array
    {
        return app(BookingFormatter::class)->formatForCalendar($this);
    }

    /**
     * Рассчитать размер возврата
     */
    public function calculateRefundAmount(): float
    {
        if (!$this->canRefund()) {
            return 0.0;
        }

        // Простая логика возврата - возвращаем предоплату за вычетом комиссии
        $refundRate = 1.0; // 100% если отмена за 24+ часов
        
        if ($this->cancelled_at) {
            $hoursSinceCancellation = $this->cancelled_at->diffInHours($this->start_time, false);
            
            if ($hoursSinceCancellation < 24) {
                $refundRate = 0.5; // 50% если отмена менее чем за 24 часа
            }
            if ($hoursSinceCancellation < 2) {
                $refundRate = 0.0; // 0% если отмена менее чем за 2 часа
            }
        }

        return round($this->deposit_amount * $refundRate, 2);
    }
}