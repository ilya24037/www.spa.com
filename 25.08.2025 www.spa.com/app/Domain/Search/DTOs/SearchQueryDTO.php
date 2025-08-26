<?php

namespace App\Domain\Search\DTOs;

/**
 * DTO для поискового запроса
 */
class SearchQueryDTO
{
    public function __construct(
        public string $query,
        public array $types = [],
        public array $filters = [],
        public int $limit = 20,
        public int $offset = 0,
        public ?string $sortBy = null,
        public ?string $sortOrder = 'desc'
    ) {}

    /**
     * Валидация запроса
     */
    public function validate(): bool
    {
        return !empty($this->query) && strlen(trim($this->query)) > 0;
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'types' => $this->types,
            'filters' => $this->filters,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
        ];
    }
}