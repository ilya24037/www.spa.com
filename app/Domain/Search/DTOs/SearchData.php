<?php

namespace App\Domain\Search\DTOs;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;

class SearchData
{
    public function __construct(
        public readonly string $query,
        public readonly SearchType $type,
        public readonly array $filters,
        public readonly SortBy $sortBy,
        public readonly string $sortOrder,
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?array $location,
        public readonly ?float $radius,
        public readonly ?array $priceRange,
        public readonly ?array $categories,
        public readonly ?bool $withPhotos,
        public readonly ?bool $verified,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'] ?? '',
            type: isset($data['type']) ? SearchType::from($data['type']) : SearchType::ADS,
            filters: $data['filters'] ?? [],
            sortBy: isset($data['sort_by']) ? SortBy::from($data['sort_by']) : SortBy::RELEVANCE,
            sortOrder: $data['sort_order'] ?? 'desc',
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 20,
            location: $data['location'] ?? null,
            radius: $data['radius'] ?? null,
            priceRange: $data['price_range'] ?? null,
            categories: $data['categories'] ?? null,
            withPhotos: $data['with_photos'] ?? null,
            verified: $data['verified'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'query' => $this->query,
            'type' => $this->type->value,
            'filters' => $this->filters,
            'sort_by' => $this->sortBy->value,
            'sort_order' => $this->sortOrder,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'location' => $this->location,
            'radius' => $this->radius,
            'price_range' => $this->priceRange,
            'categories' => $this->categories,
            'with_photos' => $this->withPhotos,
            'verified' => $this->verified,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function hasLocationFilter(): bool
    {
        return $this->location !== null && $this->radius !== null;
    }

    public function hasPriceFilter(): bool
    {
        return $this->priceRange !== null && 
               (isset($this->priceRange['min']) || isset($this->priceRange['max']));
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}