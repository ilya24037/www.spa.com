<?php

namespace App\Models;

use App\Traits\HasUniqueSlug; // 🔥 Добавлено
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MasterProfile extends Model
{
    use HasFactory;
    use HasUniqueSlug; // 🔥 Добавлено
    
    /**
     * Настройки для trait HasUniqueSlug  🔥 Добавлено
     */
    protected $slugField = 'slug';           // Поле для хранения slug
    protected $slugSource = 'display_name';  // Откуда генерировать slug

    /**
     * Атрибуты, пригодные для массового присвоения.
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
     * Касты атрибутов.
     */
    protected $casts = [
        'certificates'     => 'array',
        'education'        => 'array',
        'show_contacts'    => 'boolean',
        'home_service'     => 'boolean',
        'salon_service'    => 'boolean',
        'is_verified'      => 'boolean',
        'is_premium'       => 'boolean',
        'premium_until'    => 'datetime',
        'rating'           => 'decimal:2',
    ];

    /**
     * Атрибуты, которые должны быть добавлены к модели
     */
    protected $appends = ['url', 'full_salon_address', 'full_url', 'share_url', 'avatar_url', 'all_photos'];

    /* --------------------------------------------------------------------- */
    /*  Отношения                                                            */
    /* --------------------------------------------------------------------- */

    /** Пользователь */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Услуги мастера */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** Активные услуги */
    public function activeServices(): HasMany
    {
        return $this->services()->where('status', 'active');
    }

    /** Районы обслуживания */
    public function workZones(): HasMany
    {
        return $this->hasMany(WorkZone::class);
    }

    /** Расписание */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /** Исключения в расписании */
    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    /** Бронирования */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Отзывы */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Подписки */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MasterSubscription::class);
    }

    /** Активная подписка */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(MasterSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest();
    }

    /* --------------------------------------------------------------------- */
    /*  📸  Фото (добавлено)                                                 */
    /* --------------------------------------------------------------------- */

    /**
     * Галерея всех фото мастера.
     */
    public function photos(): HasMany
    {
        // Если FK переименуете, передайте его вторым аргументом
        return $this->hasMany(MasterPhoto::class, 'master_profile_id');
    }

    /**
     * Главное фото (is_main = true).
     */
    public function mainPhoto(): HasOne
    {
        return $this->hasOne(MasterPhoto::class, 'master_profile_id')
                    ->where('is_main', true);
    }

    /* --------------------------------------------------------------------- */
    /*  Логика                                                                */
    /* --------------------------------------------------------------------- */

    /** Проверка премиум-статуса */
    public function isPremium(): bool
    {
        return $this->is_premium && $this->premium_until && $this->premium_until->isFuture();
    }

    /** Проверка активности профиля */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Увеличение счётчика просмотров */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /** Обновление рейтинга */
    public function updateRating(): void
    {
        $avgRating = $this->reviews()
            ->where('status', 'approved')
            ->avg('rating_overall');

        $this->update([
            'rating'         => round($avgRating, 2),
            'reviews_count'  => $this->reviews()->where('status', 'approved')->count(),
        ]);
    }
    
    /**
     * 🔥 ДОБАВЛЕН МЕТОД: Проверить, доступен ли мастер сейчас
     * 
     * @return bool
     */
    public function isAvailableNow(): bool
    {
        // Проверяем активность профиля
        if (!$this->isActive()) {
            return false;
        }
        
        // Проверяем текущий день недели и время
        $now = now();
        $dayOfWeek = $now->dayOfWeek; // 0 = воскресенье, 6 = суббота
        $currentTime = $now->format('H:i');
        
        // Проверяем расписание на сегодня
        $schedule = $this->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();
            
        if (!$schedule) {
            // Если расписания нет, проверяем рабочие зоны
            // Мастер доступен если есть активные рабочие зоны
            return $this->workZones()->where('is_active', true)->exists();
        }
        
        // Проверяем, попадает ли текущее время в рабочие часы
        return $currentTime >= $schedule->start_time && $currentTime <= $schedule->end_time;
    }
    
    /**
     * 🔥 НОВЫЙ МЕТОД: Генерировать SEO мета-теги автоматически
     * 
     * Создаёт title и description для поисковиков и соцсетей
     * @return self
     */
    public function generateMetaTags(): self
    {
        // Генерируем meta_title если он пустой
        if (empty($this->meta_title)) {
            $parts = [];
            
            // 1. Имя мастера - всегда первое
            $parts[] = $this->display_name;
            
            // 2. Основная услуга (если есть)
            $mainService = $this->services()
                ->orderBy('bookings_count', 'desc') // Самая популярная услуга
                ->first();
                
            if ($mainService) {
                $parts[] = $mainService->name;
            } else {
                $parts[] = 'Массажист'; // Дефолтное значение
            }
            
            // 3. Локация
            if ($this->district && $this->city) {
                $parts[] = "{$this->district}, {$this->city}";
            } else {
                $parts[] = $this->city;
            }
            
            // Собираем title через разделитель
            $this->meta_title = implode(' • ', $parts);
        }
        
        // Генерируем meta_description если он пустой
        if (empty($this->meta_description)) {
            $description = [];
            
            // 1. Представление
            $intro = $this->is_verified ? "✓ Верифицированный массажист" : "Массажист";
            $description[] = "{$intro} {$this->display_name}";
            
            // 2. Локация с уточнением
            if ($this->metro_station) {
                $description[] = "у метро {$this->metro_station}";
            } elseif ($this->district) {
                $description[] = "в районе {$this->district} ({$this->city})";
            } else {
                $description[] = "в городе {$this->city}";
            }
            
            // 3. Услуги (максимум 3)
            $services = $this->services()
                ->where('status', 'active')
                ->orderBy('bookings_count', 'desc')
                ->take(3)
                ->pluck('name');
                
            if ($services->isNotEmpty()) {
                $servicesList = $services->implode(', ');
                $description[] = "Услуги: {$servicesList}";
            }
            
            // 4. Опыт работы (если указан)
            if ($this->experience_years > 0) {
                $years = $this->experience_years;
                $yearWord = $this->getYearWord($years);
                $description[] = "Опыт {$years} {$yearWord}";
            }
            
            // 5. Рейтинг (если есть отзывы)
            if ($this->rating > 0 && $this->reviews_count > 0) {
                $stars = str_repeat('★', round($this->rating)); // Звёздочки
                $description[] = "Рейтинг {$this->rating} {$stars} ({$this->reviews_count} отзывов)";
            }
            
            // 6. Цены (если есть услуги)
            $minPrice = $this->services()
                ->where('status', 'active')
                ->min('price');
                
            if ($minPrice) {
                $description[] = "Цены от " . number_format($minPrice, 0, '', ' ') . " ₽";
            }
            
            // Собираем описание
            $this->meta_description = implode('. ', $description) . '.';
            
            // Обрезаем если слишком длинное (оптимально 150-160 символов)
            if (mb_strlen($this->meta_description) > 160) {
                $this->meta_description = mb_substr($this->meta_description, 0, 157) . '...';
            }
        }
        
        return $this; // Возвращаем $this для цепочки вызовов
    }
    
    /**
     * 🔥 НОВЫЙ МЕТОД: Вспомогательный метод для склонения слова "год"
     * 
     * @param int $years Количество лет
     * @return string год/года/лет
     */
    private function getYearWord(int $years): string
    {
        // Последняя цифра
        $lastDigit = $years % 10;
        // Последние две цифры
        $lastTwoDigits = $years % 100;
        
        // Особые случаи: 11-14 лет (не 11 год!)
        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) {
            return 'лет';
        }
        
        // Обычные правила по последней цифре
        return match($lastDigit) {
            1 => 'год',        // 1, 21, 31 год
            2, 3, 4 => 'года', // 2-4, 22-24 года  
            default => 'лет'   // 0, 5-9 лет
        };
    }

    /* --------------------------------------------------------------------- */
    /*  Accessors & Mutators                                                 */
    /* --------------------------------------------------------------------- */

    /** URL профиля */
    public function getUrlAttribute(): string
{
    return route('masters.show', [
        'slug' => $this->slug,
        'master' => $this->id
    ]);
}

    /** Полный адрес салона */
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
     * 🔥 НОВЫЙ МЕТОД: Полный URL с доменом
     * @return string
     */
    public function getFullUrlAttribute(): string
    {
        return config('app.url') . '/master/' . $this->slug;
    }
    
    /**
     * 🔥 НОВЫЙ МЕТОД: URL для шаринга в соцсетях (без https://)
     * @return string
     */
    public function getShareUrlAttribute(): string
    {
        return str_replace(['https://', 'http://'], '', $this->full_url);
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute(): string
    {
        return \App\Helpers\ImageHelper::getImageUrl($this->avatar, '/images/no-avatar.jpg');
    }

    /**
     * Получить все фотографии (включая аватар)
     */
    public function getAllPhotosAttribute(): array
    {
        $photos = [];
        
        // Добавляем аватар как первое фото
        if ($this->avatar) {
            $photos[] = $this->avatar_url;
        }
        
        // Добавляем фотографии из галереи
        if ($this->photos) {
            foreach ($this->photos as $photo) {
                $photos[] = \App\Helpers\ImageHelper::getImageUrl($photo->path);
            }
        }
        
        // Если нет фотографий, возвращаем заглушку
        if (empty($photos)) {
            $photos[] = '/images/no-photo.jpg';
        }
        
        return $photos;
    }

    /* --------------------------------------------------------------------- */
    /*  Scopes                                                               */
    /* --------------------------------------------------------------------- */

    /** Scope: активные мастера */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /** Scope: премиум мастера */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true)
                     ->where('premium_until', '>=', now());
    }

    /** Scope: верифицированные мастера */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /** Scope: поиск по городу */
    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    /** Scope: поиск по району */
    public function scopeInDistrict($query, $district)
    {
        return $query->where('district', $district);
    }
}