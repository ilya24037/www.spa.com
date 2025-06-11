<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MasterProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'display_name',
        'slug',
        'bio',
        'avatar',
        'phone',
        'whatsapp',
        'telegram',
        'show_contacts',
        'experience_years',
        'certificates',
        'education',
        'city',
        'district',
        'metro_station',
        'home_service',
        'salon_service',
        'salon_address',
        'rating',
        'reviews_count',
        'completed_bookings',
        'views_count',
        'status',
        'is_verified',
        'is_premium',
        'premium_until',
        'meta_title',
        'meta_description',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'certificates' => 'array',
        'education' => 'array',
        'show_contacts' => 'boolean',
        'home_service' => 'boolean',
        'salon_service' => 'boolean',
        'is_verified' => 'boolean',
        'is_premium' => 'boolean',
        'premium_until' => 'datetime',
        'rating' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Автоматически создаём slug при создании
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->display_name . '-' . Str::random(6));
            }
        });

        // Обновляем slug при изменении имени
        static::updating(function ($model) {
            if ($model->isDirty('display_name') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->display_name . '-' . Str::random(6));
            }
        });
    }

    /**
     * Пользователь
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Услуги мастера
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Активные услуги
     */
    public function activeServices(): HasMany
    {
        return $this->services()->where('status', 'active');
    }

    /**
     * Районы обслуживания
     */
    public function workZones(): HasMany
    {
        return $this->hasMany(WorkZone::class);
    }

    /**
     * Расписание
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Исключения в расписании
     */
    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    /**
     * Бронирования
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Отзывы
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Подписки
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MasterSubscription::class);
    }

    /**
     * Активная подписка
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MasterSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest();
    }

    /**
     * Проверка премиум статуса
     */
    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_until && $this->premium_until->isFuture();
    }

    /**
     * Проверка активности профиля
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Увеличение счётчика просмотров
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Обновление рейтинга
     */
    public function updateRating(): void
    {
        $avgRating = $this->reviews()
            ->where('status', 'approved')
            ->avg('rating_overall');

        $this->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $this->reviews()->where('status', 'approved')->count(),
        ]);
    }

    /**
     * Получить URL профиля
     */
    public function getUrlAttribute(): string
    {
        return route('masters.show', $this->slug);
    }

    /**
     * Получить полный адрес салона
     */
    public function getFullSalonAddressAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->district,
            $this->metro_station ? "м. {$this->metro_station}" : null,
            $this->salon_address,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Scope для активных мастеров
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope для премиум мастеров
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true)
            ->where('premium_until', '>=', now());
    }

    /**
     * Scope для верифицированных мастеров
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope для поиска по городу
     */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope для поиска по району
     */
    public function scopeInDistrict($query, $district)
    {
        return $query->where('district', $district);
    }
}