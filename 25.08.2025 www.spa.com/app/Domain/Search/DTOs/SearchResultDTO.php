<?php

namespace App\Domain\Search\DTOs;

use Illuminate\Support\Collection;

/**
 * DTO для результата поиска
 */
class SearchResultDTO
{
    public function __construct(
        public Collection $items,
        public int $total,
        public int $page = 1,
        public int $perPage = 20,
        public array $aggregations = [],
        public float $took = 0.0
    ) {}

    /**
     * Получить количество страниц
     */
    public function totalPages(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Есть ли результаты
     */
    public function hasResults(): bool
    {
        return $this->items->isNotEmpty();
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items->toArray(),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages(),
            'aggregations' => $this->aggregations,
            'took' => $this->took,
        ];
    }
}