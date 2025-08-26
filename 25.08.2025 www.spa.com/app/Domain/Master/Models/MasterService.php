<?php

namespace App\Domain\Master\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель услуги мастера
 * Отвечает за информацию об отдельной услуге
 */
class MasterService extends Model
{
    use HasFactory, JsonFieldsTrait;

    protected $fillable = [
        'master_profile_id', 'name', 'description', 'price', 'price_to',
        'duration_minutes', 'category', 'subcategory', 'features',
        'preparation_time', 'cleanup_time', 'is_popular', 'is_active',
        'sort_order', 'requirements', 'contraindications'
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'features',
        'requirements', 
        'contraindications'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_to' => 'decimal:2',
        'duration_minutes' => 'integer',
        'preparation_time' => 'integer',
        'cleanup_time' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Связь с профилем мастера
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Получить полное время услуги (с подготовкой и уборкой)
     */
    public function getTotalDurationAttribute(): int
    {
        return $this->duration_minutes + $this->preparation_time + $this->cleanup_time;
    }

    /**
     * Получить диапазон цен
     */
    public function getPriceRangeAttribute(): string
    {
        if ($this->price_to && $this->price_to > $this->price) {
            return "от {$this->price} до {$this->price_to} ₽";
        }
        return "{$this->price} ₽";
    }
}