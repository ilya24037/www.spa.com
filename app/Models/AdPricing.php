<?php

namespace App\Models;

use App\Enums\PriceUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Информация о ценах объявления
 */
class AdPricing extends Model
{
    use HasFactory;

    protected $table = 'ad_pricings';

    protected $fillable = [
        'ad_id',
        'price',
        'price_unit',
        'is_starting_price',
        'discount',
        'new_client_discount',
        'gift',
        'contacts_per_hour',
        'pricing_data',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_starting_price' => 'boolean',
        'discount' => 'integer',
        'new_client_discount' => 'integer',
        'contacts_per_hour' => 'integer',
        'pricing_data' => 'array',
        'price_unit' => PriceUnit::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Получить форматированную цену
     */
    public function getFormattedPriceAttribute(): string
    {
        if (!$this->price) {
            return 'Цена не указана';
        }

        $prefix = $this->is_starting_price ? 'от ' : '';
        $unit = $this->price_unit?->getLabel() ?? '';
        
        return $prefix . number_format($this->price, 0, ',', ' ') . ' ₽' . ($unit ? ' ' . $unit : '');
    }

    /**
     * Получить цену со скидкой
     */
    public function getDiscountedPriceAttribute(): ?float
    {
        if (!$this->price || !$this->discount) {
            return null;
        }

        return $this->price * (1 - $this->discount / 100);
    }

    /**
     * Получить форматированную цену со скидкой
     */
    public function getFormattedDiscountedPriceAttribute(): ?string
    {
        $discountedPrice = $this->getDiscountedPriceAttribute();
        
        if (!$discountedPrice) {
            return null;
        }

        $prefix = $this->is_starting_price ? 'от ' : '';
        $unit = $this->price_unit?->getLabel() ?? '';
        
        return $prefix . number_format($discountedPrice, 0, ',', ' ') . ' ₽' . ($unit ? ' ' . $unit : '');
    }

    /**
     * Получить размер скидки в рублях
     */
    public function getDiscountAmountAttribute(): ?float
    {
        if (!$this->price || !$this->discount) {
            return null;
        }

        return $this->price * ($this->discount / 100);
    }

    /**
     * Проверить есть ли активные скидки
     */
    public function hasActiveDiscounts(): bool
    {
        return $this->discount > 0 || $this->new_client_discount > 0;
    }

    /**
     * Проверить валидность цены
     */
    public function isValidPrice(): bool
    {
        return $this->price > 0 && $this->price <= 1000000;
    }
}