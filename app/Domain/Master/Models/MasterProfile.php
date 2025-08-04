<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use App\Domain\Master\Traits\HasSlug;
use App\Domain\Master\Traits\GeneratesMetaTags;

/**
 * Основная модель профиля мастера
 * Содержит только базовую информацию и отношения
 */
class MasterProfile extends Model
{
    use HasFactory;
    use HasSlug;
    use GeneratesMetaTags;

    protected $slugField  = 'slug';
    protected $slugSource = 'display_name';

    protected $fillable = [
        'user_id', 'display_name', 'slug', 'bio', 'avatar',
        'phone', 'whatsapp', 'telegram', 'show_contacts',
        'experience_years', 'certificates', 'education',
        'city', 'district', 'metro_station',
        'home_service', 'salon_service', 'salon_address',
        'rating', 'reviews_count', 'completed_bookings',
        'views_count', 'status', 'is_verified',
        'is_premium', 'premium_until',
        'meta_title', 'meta_description',
        // Физические параметры
        'age', 'height', 'weight', 'breast_size',
        // Параметры внешности
        'hair_color', 'eye_color', 'nationality',
        // Особенности мастера
        'features', 'medical_certificate', 'works_during_period', 'additional_features',
        // Модульные услуги
        'services', 'services_additional_info',
    ];

    protected $casts = [
        'certificates'  => 'array',
        'education'     => 'array',
        'features'      => 'array',
        'services'      => 'array',
        'show_contacts' => 'boolean',
        'home_service'  => 'boolean',
        'salon_service' => 'boolean',
        'is_verified'   => 'boolean',
        'is_premium'    => 'boolean',
        'premium_until' => 'datetime',
        'rating'        => 'decimal:2',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    /**
     * Связь с услугами мастера
     */
    public function services(): HasMany
    {
        return $this->hasMany(\App\Domain\Service\Models\Service::class);
    }

    /**
     * Активные услуги
     */
    public function activeServices(): HasMany
    {
        return $this->services()->where('status', 'active');
    }

    /**
     * Фотографии мастера
     */
    public function photos(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\Photo::class, 'master_profile_id');
    }

    /**
     * Видео мастера
     */
    public function videos(): HasMany
    {
        return $this->hasMany(\App\Domain\Media\Models\Video::class, 'master_profile_id');
    }

    /**
     * Расписание мастера
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\MasterSchedule::class);
    }

    /**
     * Зоны обслуживания
     */
    public function workZones(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\WorkZone::class);
    }

    /**
     * Бронирования
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(\App\Domain\Booking\Models\Booking::class);
    }

    /**
     * Отзывы
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(\App\Domain\Review\Models\Review::class);
    }

    /**
     * Подписки мастера
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Domain\Master\Models\MasterSubscription::class);
    }

    /**
     * Активная подписка
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(\App\Domain\Master\Models\MasterSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latestOfMany();
    }

    /**
     * Проверка премиум статуса
     */
    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_until?->isFuture();
    }

    /**
     * Проверка активности профиля
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Обновить рейтинг на основе отзывов
     */
    public function updateRating(): void
    {
        $avg = $this->reviews()
            ->where('status', 'approved')
            ->avg('rating_overall');

        $this->update([
            'rating'        => round($avg, 2),
            'reviews_count' => $this->reviews()
                                   ->where('status', 'approved')
                                   ->count(),
        ]);
    }

    /**
     * Проверить доступность мастера сейчас
     */
    public function isAvailableNow(): bool
    {
        // Базовая логика: мастер доступен если профиль активен
        // В будущем можно расширить проверкой расписания
        return $this->isActive();
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        // Если avatar уже полный URL
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        // Иначе считаем что это путь относительно storage
        return asset('storage/' . $this->avatar);
    }

    /**
     * Получить минимальную цену услуг
     */
    public function getPriceFromAttribute(): ?float
    {
        return $this->services()->min('price');
    }

    /**
     * Получить максимальную цену услуг
     */
    public function getPriceToAttribute(): ?float
    {
        return $this->services()->max('price');
    }

    /**
     * Scopes
     */
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopePremium($q)
    {
        return $q->where('is_premium', true)
                 ->where('premium_until', '>=', now());
    }

    public function scopeVerified($q)
    {
        return $q->where('is_verified', true);
    }

    public function scopeInCity($q, $city)
    {
        return $q->where('city', $city);
    }

    public function scopeInDistrict($q, $district)
    {
        return $q->where('district', $district);
    }
}