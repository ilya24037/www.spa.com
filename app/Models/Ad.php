<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    use HasFactory;

    // Константы статусов
    const STATUS_WAITING_PAYMENT = 'waiting_payment';
    const STATUS_ACTIVE = 'active';
    const STATUS_DRAFT = 'draft';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'specialty',
        'clients',
        'service_location',
        'outcall_locations',
        'taxi_option',
        'work_format',
        'service_provider',
        'experience',
        'education_level',
        'features',
        'additional_features',
        'description',
        'price',
        'price_unit',
        'is_starting_price',
        'pricing_data',
        'contacts_per_hour',
        'discount',
        'new_client_discount',
        'gift',
        'address',
        'travel_area',
        'phone',
        'contact_method',
        // Физические параметры
        'age',
        'height',
        'weight',
        'breast_size',
        'hair_color',
        'eye_color',
        'appearance',
        'nationality',
        'has_girlfriend',
        'services',
        'services_additional_info',
        'schedule',
        'schedule_notes',
        'photos',
        'video',
        'show_photos_in_gallery',
        'allow_download_photos',
        'watermark_photos',
        'status',
        'is_paid',
        'paid_at',
        'expires_at',
        'views_count',
        'contacts_shown',
        'favorites_count'
    ];

    protected $casts = [
        'clients' => 'array',
        'service_location' => 'array',
        'outcall_locations' => 'array',
        'service_provider' => 'array',
        'features' => 'array',
        'services' => 'array',
        'schedule' => 'array',
        'photos' => 'array',
        'video' => 'array',
        'is_starting_price' => 'boolean',
        'has_girlfriend' => 'boolean',
        'pricing_data' => 'array',
        'show_photos_in_gallery' => 'boolean',
        'allow_download_photos' => 'boolean',
        'watermark_photos' => 'boolean',
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'discount' => 'integer',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'views_count' => 'integer',
        'contacts_shown' => 'integer',
        'favorites_count' => 'integer'
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить читаемый статус объявления
     */
    public function getReadableStatusAttribute()
    {
        return [
            'waiting_payment' => 'Ждет оплаты',
            'active' => 'Активное',
            'draft' => 'Черновик',
            'archived' => 'В архиве',
            'expired' => 'Истекло',
            'rejected' => 'Отклонено',
            'blocked' => 'Заблокировано'
        ][$this->status] ?? $this->status;
    }

    /**
     * Получить форматированную цену
     */
    public function getFormattedPriceAttribute()
    {
        if (!$this->price) {
            return 'Цена не указана';
        }

        $units = [
            'service' => 'за услугу',
            'hour' => 'за час',
            'unit' => 'за единицу',
            'day' => 'за день',
            'month' => 'за месяц',
            'minute' => 'за минуту'
        ];

        $prefix = $this->is_starting_price ? 'от ' : '';
        $unit = $units[$this->price_unit] ?? $this->price_unit;
        
        return $prefix . number_format($this->price, 0, ',', ' ') . ' ₽ ' . $unit;
    }

    /**
     * Проверка: ждет ли объявление действий
     */
    public function isWaitingAction(): bool
    {
        return $this->status === self::STATUS_WAITING_PAYMENT && !$this->is_paid;
    }

    /**
     * Проверка: активно ли объявление
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->is_paid && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Проверка: истекло ли объявление
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Получить количество дней до истечения
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Получить сообщение о статусе
     */
    public function getStatusMessageAttribute(): string
    {
        if ($this->isWaitingAction()) {
            return 'Не оплачено';
        }

        if ($this->isExpired()) {
            return 'Истекло ' . $this->expires_at->format('d.m.Y');
        }

        if ($this->days_until_expiration !== null && $this->days_until_expiration <= 3) {
            return 'Осталось ' . $this->days_until_expiration . ' ' . 
                   trans_choice('день|дня|дней', $this->days_until_expiration);
        }

        return '';
    }

    /**
     * Scope для объявлений, ждущих действий
     */
    public function scopeWaitingAction($query)
    {
        return $query->where('status', self::STATUS_WAITING_PAYMENT)
                     ->where('is_paid', false);
    }

    /**
     * Scope для активных объявлений
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where('is_paid', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope для черновиков
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope для архивных объявлений
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }
} 