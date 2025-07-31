<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ExtendedServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'sort_order',
        'is_active',
        'is_adult_only',
        'min_age',
        'base_additional_cost',
        'default_restrictions',
        'icon',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_adult_only' => 'boolean',
        'base_additional_cost' => 'decimal:2',
        'default_restrictions' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Связь с услугами через промежуточную таблицу
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_extended_categories', 'extended_category_id', 'service_id')
            ->withPivot(['custom_additional_cost', 'custom_restrictions'])
            ->withTimestamps();
    }

    /**
     * Получить категории по типу
     */
    public static function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Получить все типы категорий
     */
    public static function getTypes(): array
    {
        return [
            'sex' => 'Секс услуги',
            'bdsm' => 'BDSM',
            'massage' => 'Массаж',
            'additional' => 'Дополнительно'
        ];
    }

    /**
     * Проверить возрастные ограничения
     */
    public function checkAgeRestriction(int $age): bool
    {
        return $age >= $this->min_age;
    }

    /**
     * Получить итоговую стоимость с учетом доплаты
     */
    public function getTotalCost(float $basePrice, ?float $customAdditionalCost = null): float
    {
        $additionalCost = $customAdditionalCost ?? $this->base_additional_cost;
        return $basePrice + $additionalCost;
    }
}