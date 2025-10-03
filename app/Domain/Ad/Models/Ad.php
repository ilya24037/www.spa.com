<?php

namespace App\Domain\Ad\Models;

use App\Domain\Ad\Enums\AdStatus;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;
use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Searchable;

/**
 * Основная модель объявления
 * Содержит базовую информацию и отношения
 */
class Ad extends Model
{
    use HasFactory;
    use JsonFieldsTrait;
    use Searchable;

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'clients',
        'client_age_from',
        'service_provider',
        'work_format',
        'experience',
        'features',
        'additional_features',
        'services',
        'services_additional_info',
        'schedule',
        'schedule_notes',
        'online_booking',
        'address',
        'travel_area',
        'custom_travel_areas',
        'travel_radius',
        'travel_price',
        'travel_price_type',
        'geo',
        'phone',
        'contact_method',
        'whatsapp',
        'telegram',
        'description',
        'price_unit',
        'is_starting_price',
        'prices',
        'starting_price',
        'price',
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
        'bikini_zone',
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
        'favorites_count',
        'faq',
        // Поля верификации
        'verification_photo',
        'verification_video',
        'verification_status',
        'verification_type',
        'verified_at',
        'verification_expires_at',
        'verification_comment',
        'verification_metadata',
        // Поля архивации
        'archived_at',
        // Поля модерации
        'is_published',
        'moderated_at'
    ];

    /**
     * Атрибуты для добавления в JSON представление
     */
    protected $appends = ['complaints_count', 'has_unresolved_complaints'];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'clients',
        'service_provider',
        'features',
        'services',
        'schedule',
        'geo',
        'custom_travel_areas',
        'photos',
        'video',
        'prices',
        'faq',
        'verification_metadata'
    ];

    protected $casts = [
        // JSON поля обрабатываются через JsonFieldsTrait
        'has_girlfriend' => 'boolean',
        'is_starting_price' => 'boolean',
        'show_photos_in_gallery' => 'boolean',
        'allow_download_photos' => 'boolean',
        'watermark_photos' => 'boolean',
        'is_paid' => 'boolean',
        'is_published' => 'boolean',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'moderated_at' => 'datetime',
        'views_count' => 'integer',
        'contacts_shown' => 'integer',
        'favorites_count' => 'integer',
        // Включаем enum cast для status
        'status' => AdStatus::class,
        // work_format обрабатывается через mutator в app/Models/Ad.php
        // Поля верификации
        'verified_at' => 'datetime',
        'verification_expires_at' => 'datetime',
        // Поля архивации
        'archived_at' => 'datetime',
    ];

    /**
     * Автоматический маппинг цен из JSON в отдельные поля
     */
    protected static function boot()
    {
        parent::boot();

        // При создании
        static::creating(function ($ad) {
            self::mapPricesToFields($ad);
        });

        // При обновлении
        static::updating(function ($ad) {
            self::mapPricesToFields($ad);

            // Логируем изменения важных полей
            $watchedFields = ['service_provider', 'clients'];
            $changes = [];

            foreach ($watchedFields as $field) {
                if ($ad->isDirty($field)) {
                    $changes[$field] = [
                        'old' => $ad->getOriginal($field),
                        'new' => $ad->getAttribute($field)
                    ];
                }
            }

            if (!empty($changes)) {
                Log::info('🟢 Ad Model: Изменения важных полей', [
                    'ad_id' => $ad->id,
                    'changes' => $changes
                ]);
            }
        });

        // Логируем результат после обновления
        static::updated(function ($ad) {
            Log::info('🟢 Ad Model: Объявление обновлено в БД', [
                'ad_id' => $ad->id,
                'service_provider' => $ad->service_provider,
                'clients' => $ad->clients
            ]);
        });
    }

    /**
     * Маппинг цен из JSON поля prices в отдельные поля БД
     */
    private static function mapPricesToFields($ad)
    {
        if ($ad->prices && is_array($ad->prices)) {
            // Маппинг цен за 1 час
            if (isset($ad->prices['apartments_1h'])) {
                $ad->price_per_hour = $ad->prices['apartments_1h'];
            }

            // Маппинг цен за 2 часа
            if (isset($ad->prices['apartments_2h'])) {
                $ad->price_two_hours = $ad->prices['apartments_2h'];
            }

            // Маппинг цены выезда
            if (isset($ad->prices['outcall_1h'])) {
                $ad->outcall_price = $ad->prices['outcall_1h'];
            }

            // Маппинг экспресс цены
            if (isset($ad->prices['apartments_express'])) {
                $ad->express_price = $ad->prices['apartments_express'];
            }

            // Маппинг ночной цены
            if (isset($ad->prices['apartments_night'])) {
                $ad->price_night = $ad->prices['apartments_night'];
            }

            // Устанавливаем минимальную цену как базовую
            $allPrices = array_filter([
                $ad->prices['apartments_1h'] ?? null,
                $ad->prices['apartments_2h'] ?? null,
                $ad->prices['outcall_1h'] ?? null,
                $ad->prices['outcall_2h'] ?? null,
            ]);

            if (!empty($allPrices)) {
                $ad->price = min($allPrices);
            }
        }
    }

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    /**
     * Связь с жалобами
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
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
     * Проверка: архивировано ли объявление
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Получить массив данных для индексации в поисковой системе
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'specialty' => $this->specialty,
            'service_provider' => $this->service_provider,
            'work_format' => $this->work_format,
            'address' => $this->address,
            'price' => $this->price,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Получить количество жалоб на объявление
     * @return int
     */
    public function getComplaintsCountAttribute(): int
    {
        return $this->complaints()->count();
    }

    /**
     * Проверка: есть ли неразрешенные жалобы
     * @return bool
     */
    public function getHasUnresolvedComplaintsAttribute(): bool
    {
        return $this->complaints()->where('status', 'pending')->exists();
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
     * Accessor для work_format - возвращает строку для правильной сериализации
     * При необходимости получить enum используйте $ad->work_format_enum
     */
    public function getWorkFormatAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Возвращаем строковое значение для правильной работы с frontend
        return $value;
    }

    /**
     * Получить work_format как enum объект
     */
    public function getWorkFormatEnumAttribute(): ?WorkFormat
    {
        $value = $this->attributes['work_format'] ?? null;

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
        if (is_string($value) && isset($mapping[$value])) {
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
        if (empty($this->category) || empty($this->address)) {
            return false;
        }

        // Проверяем описание
        if (empty($this->description)) {
            return false;
        }

        // Проверяем цены
        if (empty($this->prices) && empty($this->price_unit)) {
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
        if (!empty($this->geo)) $filledFields++;

        // Контент
        if (!empty($this->description)) $filledFields++;
        if (!empty($this->title)) $filledFields++;

        // Цены  
        if (!empty($this->prices) || !empty($this->price_unit)) {
            $filledFields++;
        }

        // Расписание
        if (!empty($this->schedule)) {
            $filledFields++;
        }

        // Медиа
        if (!empty($this->photos) && count($this->photos) > 0) {
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

        if (empty($this->address)) {
            $missing[] = 'address';
        }

        if (empty($this->description)) {
            $missing[] = 'description';
        }

        if (empty($this->prices) && empty($this->price_unit)) {
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

    // ============= МЕТОДЫ ВЕРИФИКАЦИИ =============
    
    /**
     * Проверка: верифицировано ли объявление
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified' && 
               (!$this->verification_expires_at || $this->verification_expires_at->isFuture());
    }
    
    /**
     * Проверка: истекла ли верификация
     */
    public function isVerificationExpired(): bool
    {
        return $this->verification_expires_at && $this->verification_expires_at->isPast();
    }
    
    /**
     * Проверка: нужно ли обновить верификацию
     */
    public function needsVerificationUpdate(): bool
    {
        if (!$this->isVerified()) {
            return false;
        }
        
        // Предупреждаем за 7 дней до истечения
        if ($this->verification_expires_at) {
            return $this->verification_expires_at->diffInDays(now()) <= 7;
        }
        
        return false;
    }
    
    /**
     * Получить badge верификации
     */
    public function getVerificationBadge(): ?array
    {
        if (!$this->isVerified()) {
            return null;
        }
        
        $daysLeft = $this->verification_expires_at 
            ? $this->verification_expires_at->diffInDays(now())
            : null;
            
        return [
            'status' => 'verified',
            'text' => 'Фото проверено',
            'expires_at' => $this->verification_expires_at?->format('d.m.Y'),
            'days_left' => $daysLeft,
            'needs_update' => $this->needsVerificationUpdate()
        ];
    }
    
    /**
     * Получить статус верификации для отображения
     */
    public function getVerificationStatusDisplay(): string
    {
        return match($this->verification_status) {
            'verified' => '✅ Фото проверено',
            'pending' => '⏳ На проверке',
            'rejected' => '❌ Отклонено',
            default => '⚪ Не проверено'
        };
    }
}