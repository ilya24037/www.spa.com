<?php

namespace App\Models;

use App\Enums\AdStatus;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ad extends Model
{
    use HasFactory;

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
        'favorites_count' => 'integer',
        
        // Enums
        'status' => AdStatus::class,
        'price_unit' => PriceUnit::class,
        'work_format' => WorkFormat::class,
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с контентом объявления
     */
    public function content(): HasOne
    {
        return $this->hasOne(AdContent::class);
    }

    /**
     * Связь с ценами объявления
     */
    public function pricing(): HasOne
    {
        return $this->hasOne(AdPricing::class);
    }

    /**
     * Связь с расписанием объявления
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(AdSchedule::class);
    }

    /**
     * Связь с медиа объявления
     */
    public function media(): HasOne
    {
        return $this->hasOne(AdMedia::class);
    }

    /**
     * Получить читаемый статус объявления
     */
    public function getReadableStatusAttribute()
    {
        return $this->status?->getLabel() ?? $this->status;
    }

    /**
     * Получить форматированную цену
     */
    public function getFormattedPriceAttribute()
    {
        if (!$this->price) {
            return 'Цена не указана';
        }

        $prefix = $this->is_starting_price ? 'от ' : '';
        $unit = $this->price_unit?->getLabel() ?? $this->price_unit;
        
        return $prefix . number_format($this->price, 0, ',', ' ') . ' ₽ ' . $unit;
    }

    /**
     * Проверка: ждет ли объявление действий
     */
    public function isWaitingAction(): bool
    {
        return $this->status === AdStatus::WAITING_PAYMENT && !$this->is_paid;
    }

    /**
     * Проверка: активно ли объявление
     */
    public function isActive(): bool
    {
        return $this->status === AdStatus::ACTIVE && 
               $this->is_paid && 
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Проверка: истекло ли объявление
     */
    public function isExpired(): bool
    {
        return $this->status === AdStatus::EXPIRED || 
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
        return $query->where('status', AdStatus::WAITING_PAYMENT)
                     ->where('is_paid', false);
    }

    /**
     * Scope для активных объявлений
     */
    public function scopeActive($query)
    {
        return $query->where('status', AdStatus::ACTIVE)
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
        return $query->where('status', AdStatus::DRAFT);
    }

    /**
     * Scope для архивных объявлений
     */
    public function scopeArchived($query)
    {
        return $query->where('status', AdStatus::ARCHIVED);
    }

    /**
     * Проверить можно ли редактировать объявление
     */
    public function isEditable(): bool
    {
        return $this->status?->isEditable() ?? false;
    }

    /**
     * Проверить можно ли удалить объявление
     */
    public function isDeletable(): bool
    {
        return $this->status?->isDeletable() ?? false;
    }

    /**
     * Проверить является ли объявление публичным
     */
    public function isPublic(): bool
    {
        return $this->status?->isPublic() ?? false;
    }

    /**
     * Проверить готовность к публикации
     */
    public function canBePublished(): bool
    {
        // Проверяем основные поля
        if (empty($this->category) || empty($this->address) || empty($this->phone)) {
            return false;
        }

        // Проверяем контент
        if (!$this->content || !$this->content->isComplete()) {
            return false;
        }

        // Проверяем цены
        if (!$this->pricing || !$this->pricing->isValidPrice()) {
            return false;
        }

        return true;
    }

    /**
     * Получить процент заполненности объявления
     */
    public function getCompletionPercentageAttribute(): int
    {
        $totalFields = 10;
        $filledFields = 0;

        // Основные поля
        if (!empty($this->category)) $filledFields++;
        if (!empty($this->address)) $filledFields++;
        if (!empty($this->phone)) $filledFields++;
        if (!empty($this->work_format)) $filledFields++;

        // Контент
        if ($this->content && $this->content->isComplete()) {
            $filledFields += 2; // title + description
        }

        // Цены  
        if ($this->pricing && $this->pricing->isValidPrice()) {
            $filledFields++;
        }

        // Расписание
        if ($this->schedule && $this->schedule->isComplete()) {
            $filledFields++;
        }

        // Медиа
        if ($this->media && $this->media->hasMedia()) {
            $filledFields += 2; // фото + настройки
        }

        return intval(($filledFields / $totalFields) * 100);
    }

    /**
     * Получить список недостающих полей для публикации
     */
    public function getMissingFieldsForPublication(): array
    {
        $missing = [];

        if (empty($this->category)) {
            $missing[] = 'category';
        }

        if (empty($this->address)) {
            $missing[] = 'address';
        }

        if (empty($this->phone)) {
            $missing[] = 'phone';
        }

        if (!$this->content || !$this->content->isComplete()) {
            $missing[] = 'content';
        }

        if (!$this->pricing || !$this->pricing->isValidPrice()) {
            $missing[] = 'pricing';
        }

        return $missing;
    }
} 