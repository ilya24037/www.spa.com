<?php

namespace App\Domain\Service\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MassageCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'parent_id',
        'sort_order',
        'services_count',
        'is_popular',
        'is_featured',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'properties',
        'min_price',
        'avg_price',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_popular' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'properties' => 'array',
        'min_price' => 'decimal:2',
        'avg_price' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Автоматически создаём slug
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        // Обновляем slug при изменении названия
        static::updating(function ($model) {
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->name);
            }
        });

        // При удалении категории обновляем счётчики родительской
        static::deleted(function ($model) {
            if ($model->parent) {
                $model->parent->updateServicesCount();
            }
        });
    }

    /**
     * Родительская категория
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MassageCategory::class, 'parent_id');
    }

    /**
     * Дочерние категории (основное отношение)
     */
    public function children(): HasMany
    {
        return $this->hasMany(MassageCategory::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Подкатегории (alias для совместимости)
     */
    public function subcategories(): HasMany
    {
        return $this->children();
    }

    /**
     * Активные дочерние категории
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    /**
     * Услуги в категории
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Активные услуги в категории
     */
    public function activeServices(): HasMany
    {
        return $this->services()->where('status', 'active');
    }

    /**
     * Получить все родительские категории
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Получить полный путь категории
     */
    public function getFullPathAttribute(): string
    {
        $path = $this->ancestors()->pluck('name')->push($this->name);
        return $path->implode(' > ');
    }

    /**
     * Получить URL категории
     */
    public function getUrlAttribute(): string
    {
        return route('categories.show', $this->slug);
    }

    /**
     * Обновить количество услуг
     */
    public function updateServicesCount(): void
    {
        $count = $this->services()->count();
        
        // Добавляем услуги из дочерних категорий
        $this->children->each(function ($child) use (&$count) {
            $count += $child->services()->count();
        });

        $this->update(['services_count' => $count]);
    }

    /**
     * Обновить ценовую статистику
     */
    public function updatePriceStats(): void
    {
        $prices = $this->activeServices()->pluck('price');
        
        if ($prices->isNotEmpty()) {
            $this->update([
                'min_price' => $prices->min(),
                'avg_price' => round($prices->avg(), 2),
            ]);
        }
    }

    /**
     * Проверка, является ли категория корневой
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Проверка, есть ли дочерние категории
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Scope для активных категорий
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для популярных категорий
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope для рекомендуемых категорий
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope для корневых категорий
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope для сортировки
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Получить иконку или изображение по умолчанию
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return Storage::url($this->image);
        }
        
        // Возвращаем дефолтное изображение в зависимости от slug
        $defaultImages = [
            'lechebnyi-massazh' => '/images/categories/medical.jpg',
            'rasslablyayushchii-massazh' => '/images/categories/relax.jpg',
            'sportivnyi-massazh' => '/images/categories/sport.jpg',
            'spa-procedury' => '/images/categories/spa.jpg',
            'detskii-massazh' => '/images/categories/kids.jpg',
            'massazh-dlya-beremennykh' => '/images/categories/pregnancy.jpg',
        ];

        return $defaultImages[$this->slug] ?? '/images/categories/default.jpg';
    }

    /**
     * Получить список для выпадающего меню
     */
    public static function getSelectOptions($excludeId = null): array
    {
        $categories = self::active()->ordered()->get();
        $options = [];

        foreach ($categories as $category) {
            if ($category->id !== $excludeId) {
                $prefix = $category->isRoot() ? '' : str_repeat('— ', $category->ancestors()->count());
                $options[$category->id] = $prefix . $category->name;
            }
        }

        return $options;
    }
}
