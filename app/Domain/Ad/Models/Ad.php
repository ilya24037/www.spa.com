<?php

namespace App\Domain\Ad\Models;

use App\Domain\Ad\Enums\AdStatus;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Основная модель объявления
 * Содержит базовую информацию и отношения
 */
class Ad extends Model
{
    use HasFactory;
    use JsonFieldsTrait;

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'specialty',
        'clients',
        'service_provider',
        'work_format',
        'experience',
        'features',
        'additional_features',
        'services',
        'schedule',
        'schedule_notes',
        'service_location',
        'outcall_locations', 
        'address',
        'geo',
        'travel_area',
        'phone',
        'contact_method',
        'whatsapp',
        'telegram',
        'description',
        'price',
        'price_unit',
        'price_per_hour',
        'outcall_price',
        'express_price',
        'price_two_hours',
        'price_night',
        'min_duration',
        'contacts_per_hour',
        'has_girlfriend',
        'age',
        'height',
        'weight',
        'breast_size',
        'hair_color',
        'eye_color',
        'appearance',
        'nationality',
        'discount',
        'new_client_discount', 
        'gift',
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

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'clients',
        'service_provider',
        'features',
        'services',
        'schedule',
        'service_location',
        'outcall_locations',
        'geo',
        'photos',
        'video'
    ];

    protected $casts = [
        // JSON поля обрабатываются через JsonFieldsTrait
        'has_girlfriend' => 'boolean',
        'show_photos_in_gallery' => 'boolean',
        'allow_download_photos' => 'boolean',
        'watermark_photos' => 'boolean',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'views_count' => 'integer',
        'contacts_shown' => 'integer',
        'favorites_count' => 'integer',
        'status' => AdStatus::class,
        // work_format обрабатывается через mutator в app/Models/Ad.php
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    /**
     * Связь с контентом объявления
     */
    public function content(): HasOne
    {
        return $this->hasOne(\App\Domain\Ad\Models\AdContent::class);
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
        return $this->hasOne(\App\Domain\Ad\Models\AdSchedule::class);
    }

    /**
     * Связь с медиа объявления
     */
    public function media(): HasOne
    {
        return $this->hasOne(AdMedia::class);
    }

    /**
     * Связь с локацией объявления
     */
    public function location(): HasOne
    {
        return $this->hasOne(AdLocation::class);
    }

    /**
     * Получить читаемый статус объявления
     */
    public function getReadableStatusAttribute()
    {
        return $this->status?->getLabel() ?? $this->status;
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
     * Accessor для work_format - возвращает enum
     */
    public function getWorkFormatAttribute($value): ?WorkFormat
    {
        if (!$value) {
            return null;
        }
        
        try {
            return WorkFormat::from($value);
        } catch (\ValueError $e) {
            // Если значение не валидное, возвращаем значение по умолчанию
            return WorkFormat::INDIVIDUAL;
        }
    }

    /**
     * Мутатор для work_format - преобразует неизвестные значения
     */
    public function setWorkFormatAttribute($value)
    {
        // Маппинг старых/неправильных значений к правильным
        $mapping = [
            'private_master' => 'individual',
            'частный мастер' => 'individual',
            'индивидуальный' => 'individual',
            'салон' => 'salon',
            'team' => 'salon',
            'команда' => 'salon',
            'пара' => 'duo',
            'групповой' => 'group',
        ];

        // Если значение в маппинге, используем преобразованное
        if (isset($mapping[$value])) {
            $value = $mapping[$value];
        }

        // Проверяем, является ли значение валидным для enum
        if ($value) {
            $validValues = array_column(WorkFormat::cases(), 'value');
            if (!in_array($value, $validValues)) {
                // Если не валидное, используем значение по умолчанию
                $value = 'individual';
            }
        }

        $this->attributes['work_format'] = $value;
    }

    /**
     * Проверить готовность к публикации
     */
    public function canBePublished(): bool
    {
        // Проверяем основные поля
        if (empty($this->category) || !$this->location || !$this->location->isComplete()) {
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
        if ($this->location && $this->location->isComplete()) $filledFields += 2;

        // Контент
        if ($this->content && $this->content->isComplete()) {
            $filledFields += 2;
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
            $filledFields += 2;
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

        if (!$this->location || !$this->location->isComplete()) {
            $missing[] = 'location';
        }

        if (!$this->content || !$this->content->isComplete()) {
            $missing[] = 'content';
        }

        if (!$this->pricing || !$this->pricing->isValidPrice()) {
            $missing[] = 'pricing';
        }

        return $missing;
    }

    /**
     * Scopes
     */
    public function scopeWaitingAction($query)
    {
        return $query->where('status', AdStatus::WAITING_PAYMENT)
                     ->where('is_paid', false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', AdStatus::ACTIVE)
                     ->where('is_paid', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeDrafts($query)
    {
        return $query->where('status', AdStatus::DRAFT);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', AdStatus::ARCHIVED);
    }
}