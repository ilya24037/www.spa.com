<?php

namespace App\Domain\Search\Sorters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Сортировщик по цене
 * Соответствует DDD архитектуре - размещен в Domain\Search\Sorters
 */
class PriceSorter implements SorterInterface
{
    protected string $direction = 'asc';
    protected string $priceField = 'price';
    protected bool $nullsLast = true;

    public function __construct(array $params = [])
    {
        $this->direction = $this->validateDirection($params['direction'] ?? 'asc');
        $this->priceField = $params['price_field'] ?? 'price';
        $this->nullsLast = (bool) ($params['nulls_last'] ?? true);
    }

    /**
     * Применить сортировку к запросу
     */
    public function apply(Builder $query): Builder
    {
        if ($this->nullsLast) {
            // Сортировка с NULL значениями в конце
            return $query->orderByRaw("CASE WHEN {$this->priceField} IS NULL THEN 1 ELSE 0 END")
                        ->orderBy($this->priceField, $this->direction);
        }

        return $query->orderBy($this->priceField, $this->direction);
    }

    /**
     * Получить параметры сортировки
     */
    public function getParams(): array
    {
        return [
            'direction' => $this->direction,
            'price_field' => $this->priceField,
            'nulls_last' => $this->nullsLast,
        ];
    }

    /**
     * Получить описание сортировки
     */
    public function getDescription(): string
    {
        return $this->direction === 'asc' 
            ? 'По цене (сначала дешевле)' 
            : 'По цене (сначала дороже)';
    }

    /**
     * Установить направление сортировки
     */
    public function setDirection(string $direction): self
    {
        $this->direction = $this->validateDirection($direction);
        return $this;
    }

    /**
     * Сначала дешевле
     */
    public function cheapFirst(): self
    {
        $this->direction = 'asc';
        return $this;
    }

    /**
     * Сначала дороже
     */
    public function expensiveFirst(): self
    {
        $this->direction = 'desc';
        return $this;
    }

    /**
     * Клонировать сортировщик
     */
    public function clone(): self
    {
        return new self($this->getParams());
    }

    /**
     * Инвертировать направление
     */
    public function invert(): self
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        return $this;
    }

    /**
     * Проверить направление сортировки
     */
    protected function validateDirection(string $direction): string
    {
        $direction = strtolower($direction);
        return in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
    }
}