<?php

namespace App\Domain\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Фильтр по цене (диапазон цен)
 * Соответствует DDD архитектуре - размещен в Domain\Search\Filters
 */
class PriceFilter implements FilterInterface
{
    protected ?float $minPrice = null;
    protected ?float $maxPrice = null;
    protected string $priceField = 'price';
    protected bool $includeNullPrices = false;

    public function __construct(array $params = [])
    {
        $this->minPrice = isset($params['min_price']) ? (float) $params['min_price'] : null;
        $this->maxPrice = isset($params['max_price']) ? (float) $params['max_price'] : null;
        $this->priceField = $params['price_field'] ?? 'price';
        $this->includeNullPrices = (bool) ($params['include_null_prices'] ?? false);
    }

    /**
     * Создать из диапазона
     */
    public static function fromRange(?float $min, ?float $max): self
    {
        return new self([
            'min_price' => $min,
            'max_price' => $max,
        ]);
    }

    /**
     * Применить фильтр к запросу
     */
    public function apply(Builder $query): Builder
    {
        // Если фильтр не активен, возвращаем запрос без изменений
        if (!$this->isActive()) {
            return $query;
        }

        $query->where(function($q) {
            // Основная логика фильтрации по цене
            $q->where(function($priceQuery) {
                // Минимальная цена
                if ($this->minPrice !== null) {
                    $priceQuery->where($this->priceField, '>=', $this->minPrice);
                }

                // Максимальная цена
                if ($this->maxPrice !== null) {
                    $priceQuery->where($this->priceField, '<=', $this->maxPrice);
                }
            });

            // Включать записи с NULL ценами
            if ($this->includeNullPrices) {
                $q->orWhereNull($this->priceField);
            }
        });

        return $query;
    }

    /**
     * Применить фильтр к связанной таблице
     */
    public function applyToRelation(Builder $query, string $relation, string $priceField = 'price'): Builder
    {
        return $query->whereHas($relation, function($q) use ($priceField) {
            $originalField = $this->priceField;
            $this->priceField = $priceField;
            $this->apply($q);
            $this->priceField = $originalField;
        });
    }

    /**
     * Получить активные параметры фильтра
     */
    public function getActiveParams(): array
    {
        $params = [];

        if ($this->minPrice !== null) {
            $params['min_price'] = $this->minPrice;
        }

        if ($this->maxPrice !== null) {
            $params['max_price'] = $this->maxPrice;
        }

        if ($this->includeNullPrices) {
            $params['include_null_prices'] = true;
        }

        if ($this->priceField !== 'price') {
            $params['price_field'] = $this->priceField;
        }

        return $params;
    }

    /**
     * Проверить, активен ли фильтр
     */
    public function isActive(): bool
    {
        return $this->minPrice !== null || $this->maxPrice !== null;
    }

    /**
     * Получить описание фильтра
     */
    public function getDescription(): string
    {
        if (!$this->isActive()) {
            return '';
        }

        if ($this->minPrice !== null && $this->maxPrice !== null) {
            return sprintf(
                'Цена: от %s до %s руб.',
                number_format($this->minPrice, 0, '.', ' '),
                number_format($this->maxPrice, 0, '.', ' ')
            );
        }

        if ($this->minPrice !== null) {
            return sprintf(
                'Цена: от %s руб.',
                number_format($this->minPrice, 0, '.', ' ')
            );
        }

        return sprintf(
            'Цена: до %s руб.',
            number_format($this->maxPrice, 0, '.', ' ')
        );
    }

    /**
     * Клонировать фильтр
     */
    public function clone(): self
    {
        return new self($this->getActiveParams());
    }

    /**
     * Сбросить фильтр
     */
    public function reset(): self
    {
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->includeNullPrices = false;

        return $this;
    }

    /**
     * Установить минимальную цену
     */
    public function setMinPrice(?float $minPrice): self
    {
        $this->minPrice = $minPrice !== null && $minPrice >= 0 ? $minPrice : null;
        return $this;
    }

    /**
     * Установить максимальную цену
     */
    public function setMaxPrice(?float $maxPrice): self
    {
        $this->maxPrice = $maxPrice !== null && $maxPrice >= 0 ? $maxPrice : null;
        return $this;
    }

    /**
     * Установить диапазон цен
     */
    public function setRange(?float $min, ?float $max): self
    {
        $this->setMinPrice($min);
        $this->setMaxPrice($max);
        return $this;
    }

    /**
     * Установить поле цены
     */
    public function setPriceField(string $field): self
    {
        $this->priceField = $field;
        return $this;
    }

    /**
     * Включить записи с NULL ценами
     */
    public function includeNullPrices(bool $include = true): self
    {
        $this->includeNullPrices = $include;
        return $this;
    }

    /**
     * Получить минимальную цену
     */
    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    /**
     * Получить максимальную цену
     */
    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    /**
     * Проверить, находится ли цена в диапазоне
     */
    public function inRange(float $price): bool
    {
        if ($this->minPrice !== null && $price < $this->minPrice) {
            return false;
        }

        if ($this->maxPrice !== null && $price > $this->maxPrice) {
            return false;
        }

        return true;
    }

    /**
     * Получить процент от диапазона
     */
    public function getPercentage(float $price): ?float
    {
        if ($this->minPrice === null || $this->maxPrice === null) {
            return null;
        }

        $range = $this->maxPrice - $this->minPrice;
        if ($range <= 0) {
            return null;
        }

        $offset = $price - $this->minPrice;
        return ($offset / $range) * 100;
    }
}