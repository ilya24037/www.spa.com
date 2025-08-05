<?php

namespace App\Domain\Service\Models;

use App\Support\Traits\JsonFieldsTrait;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Booking\Models\Booking;
use App\Domain\Review\Models\Review;
use App\Domain\Service\Models\MassageCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory, JsonFieldsTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'master_profile_id',
        'massage_category_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'price_home',
        'price_sale',
        'sale_percentage',
        'sale_until',
        'is_complex',
        'included_services',
        'contraindications',
        'preparation',
        'bookings_count',
        'rating',
        'views_count',
        'status',
        'is_featured',
        'is_new',
        'slug',
        'meta_title',
        'meta_description',
        'instant_booking',
        'advance_booking_hours',
        'cancellation_hours',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'included_services',
        'contraindications',
        'preparation',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sale_until' => 'datetime',
        'is_complex' => 'boolean',
        // JSON поля обрабатываются через JsonFieldsTrait
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'instant_booking' => 'boolean',
        'price' => 'decimal:2',
        'price_home' => 'decimal:2',
        'price_sale' => 'decimal:2',
        'rating' => 'decimal:2',
    ];


    /**
     * Мастер
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Категория
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MassageCategory::class, 'massage_category_id');
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
     * Активные отзывы
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /**
     * Медиа файлы услуги (фотографии, видео)
     */
    public function media(): MorphMany
    {
        return $this->morphMany(\App\Domain\Media\Models\Media::class, 'mediable');
    }

    /**
     * Получить текущую цену с учётом скидки
     */
    public function getCurrentPriceAttribute(): float
    {
        if ($this->hasActiveSale()) {
            return $this->price_sale;
        }
        
        return $this->price;
    }

    /**
     * Получить цену с выездом с учётом скидки
     */
    public function getCurrentHomePriceAttribute(): float
    {
        $homePrice = $this->price_home ?? $this->price;
        
        if ($this->hasActiveSale() && $this->sale_percentage > 0) {
            return round($homePrice * (1 - $this->sale_percentage / 100), 2);
        }
        
        return $homePrice;
    }

    /**
     * Проверка активной скидки
     */
    public function hasActiveSale(): bool
    {
        return $this->price_sale && 
               $this->sale_until && 
               $this->sale_until->isFuture();
    }

    /**
     * Получить процент экономии
     */
    public function getSavingsPercentageAttribute(): int
    {
        if (!$this->hasActiveSale()) {
            return 0;
        }
        
        return round((($this->price - $this->price_sale) / $this->price) * 100);
    }

    /**
     * Получить URL услуги
     */
    public function getUrlAttribute(): string
    {
        return route('services.show', $this->slug);
    }

    /**
     * Проверка доступности для бронирования
     */
    public function isAvailableForBooking(): bool
    {
        return $this->status === 'active' && 
               $this->masterProfile->isActive();
    }

    /**
     * Увеличить счётчик просмотров
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Обновить рейтинг услуги
     */
    public function updateRating(): void
    {
        $avgRating = $this->approvedReviews()->avg('rating_overall');
        
        $this->update([
            'rating' => round($avgRating ?? 0, 2),
        ]);
    }

    /**
     * Получить длительность в формате часов и минут
     */
    public function getDurationFormatAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours} ч {$minutes} мин";
        } elseif ($hours > 0) {
            return "{$hours} ч";
        } else {
            return "{$minutes} мин";
        }
    }

    /**
     * Scope для активных услуг
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope для рекомендуемых услуг
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope для услуг со скидкой
     */
    public function scopeOnSale($query)
    {
        return $query->whereNotNull('price_sale')
            ->where('sale_until', '>', now());
    }

    /**
     * Scope для популярных услуг
     */
    public function scopePopular($query)
    {
        return $query->orderBy('bookings_count', 'desc')
            ->orderBy('rating', 'desc');
    }

    /**
     * Scope для поиска по цене
     */
    public function scopePriceBetween($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope для фильтрации по категории с подкатегориями
     */
    public function scopeInCategoryWithChildren($query, $categoryId)
    {
        $category = MassageCategory::find($categoryId);
        
        if (!$category) {
            return $query;
        }
        
        $categoryIds = collect([$categoryId]);
        $categoryIds = $categoryIds->merge($category->children->pluck('id'));
        
        return $query->whereIn('massage_category_id', $categoryIds);
    }
}