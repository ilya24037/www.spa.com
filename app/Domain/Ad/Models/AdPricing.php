<?php

namespace App\Domain\Ad\Models;

use App\Enums\PriceUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для работы с ценообразованием объявления
 * Управляет ценами, скидками и предложениями
 */
class AdPricing extends Model
{
    protected $table = 'ad_pricing';

    protected $fillable = [
        'ad_id',
        'price',
        'price_unit',
        'is_starting_price',
        'pricing_data',
        'discount',
        'new_client_discount',
        'gift',
        'prepayment_required',
        'prepayment_percent',
        'contacts_per_hour',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_starting_price' => 'boolean',
        'pricing_data' => 'array',
        'discount' => 'integer',
        'new_client_discount' => 'integer',
        'prepayment_required' => 'boolean',
        'prepayment_percent' => 'integer',
        'contacts_per_hour' => 'integer',
        // Убираем каст к enum, так как используем mutator
        // 'price_unit' => PriceUnit::class,
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Accessor для price_unit - возвращает enum
     */
    public function getPriceUnitAttribute($value): ?PriceUnit
    {
        if (!$value) {
            return null;
        }
        
        try {
            return PriceUnit::from($value);
        } catch (\ValueError $e) {
            // Если значение не валидное, возвращаем значение по умолчанию
            return PriceUnit::SERVICE;
        }
    }

    /**
     * Мутатор для price_unit - преобразует неизвестные значения
     */
    public function setPriceUnitAttribute($value)
    {
        // Маппинг старых/неправильных значений к правильным
        $mapping = [
            'час' => 'hour',
            'сеанс' => 'session',
            'услуга' => 'service',
            'минута' => 'minute',
            'день' => 'day',
            'месяц' => 'month',
        ];

        // Если значение в маппинге, используем преобразованное
        if (isset($mapping[$value])) {
            $value = $mapping[$value];
        }

        // Проверяем, является ли значение валидным для enum
        $validValues = array_column(PriceUnit::cases(), 'value');
        if (!in_array($value, $validValues)) {
            // Если не валидное, используем значение по умолчанию
            $value = 'service';
        }

        $this->attributes['price_unit'] = $value;
    }

    /**
     * Проверка валидности цены
     */
    public function isValidPrice(): bool
    {
        return $this->price > 0 && $this->price_unit !== null;
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
        
        return $prefix . number_format($this->price, 0, ',', ' ') . ' ₽ ' . $unit;
    }

    /**
     * Получить минимальную цену из pricing_data
     */
    public function getMinPriceAttribute(): ?float
    {
        if (empty($this->pricing_data)) {
            return $this->price;
        }

        $prices = array_column($this->pricing_data, 'price');
        return !empty($prices) ? min($prices) : $this->price;
    }

    /**
     * Получить максимальную цену из pricing_data
     */
    public function getMaxPriceAttribute(): ?float
    {
        if (empty($this->pricing_data)) {
            return $this->price;
        }

        $prices = array_column($this->pricing_data, 'price');
        return !empty($prices) ? max($prices) : $this->price;
    }

    /**
     * Получить диапазон цен
     */
    public function getPriceRangeAttribute(): string
    {
        $min = $this->min_price;
        $max = $this->max_price;

        if ($min === $max || !$max) {
            return $this->formatted_price;
        }

        return number_format($min, 0, ',', ' ') . ' - ' . 
               number_format($max, 0, ',', ' ') . ' ₽';
    }

    /**
     * Проверка наличия скидок
     */
    public function hasDiscounts(): bool
    {
        return $this->discount > 0 || $this->new_client_discount > 0;
    }

    /**
     * Получить максимальную скидку
     */
    public function getMaxDiscountAttribute(): int
    {
        return max($this->discount ?? 0, $this->new_client_discount ?? 0);
    }

    /**
     * Применить скидку к цене
     */
    public function applyDiscount(float $price, bool $isNewClient = false): float
    {
        $discount = $isNewClient && $this->new_client_discount > 0
            ? $this->new_client_discount
            : $this->discount;

        if ($discount <= 0) {
            return $price;
        }

        return $price * (1 - $discount / 100);
    }

    /**
     * Получить цену с учетом скидки
     */
    public function getDiscountedPriceAttribute(): float
    {
        return $this->applyDiscount($this->price);
    }

    /**
     * Проверка требования предоплаты
     */
    public function requiresPrepayment(): bool
    {
        return $this->prepayment_required && $this->prepayment_percent > 0;
    }

    /**
     * Получить сумму предоплаты
     */
    public function getPrepaymentAmount(?float $totalPrice = null): float
    {
        if (!$this->requiresPrepayment()) {
            return 0;
        }

        $price = $totalPrice ?? $this->price;
        return $price * ($this->prepayment_percent / 100);
    }

    /**
     * Получить структурированные данные о ценах
     */
    public function getPricingStructure(): array
    {
        $structure = [
            'base_price' => $this->price,
            'unit' => $this->price_unit?->value,
            'is_starting_price' => $this->is_starting_price,
        ];

        if ($this->hasDiscounts()) {
            $structure['discounts'] = [
                'general' => $this->discount,
                'new_client' => $this->new_client_discount,
            ];
        }

        if ($this->requiresPrepayment()) {
            $structure['prepayment'] = [
                'required' => true,
                'percent' => $this->prepayment_percent,
            ];
        }

        if (!empty($this->pricing_data)) {
            $structure['additional_prices'] = $this->pricing_data;
        }

        return $structure;
    }
}