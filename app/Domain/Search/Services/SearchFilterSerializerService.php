<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;

/**
 * Сервис сериализации и экспорта поисковых фильтров
 */
class SearchFilterSerializerService
{
    /**
     * Экспорт в JSON
     */
    public function toJson(SearchType $searchType, array $activeFilters, array $descriptions = []): string
    {
        return json_encode([
            'search_type' => $searchType->value,
            'filters' => $activeFilters,
            'description' => $descriptions,
            'timestamp' => now()->toISOString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Экспорт в массив
     */
    public function toArray(SearchType $searchType, array $filters, array $activeFilters, int $activeCount): array
    {
        return [
            'search_type' => $searchType->value,
            'filters' => $filters,
            'active_filters' => $activeFilters,
            'active_count' => $activeCount,
            'hash' => $this->getHash($activeFilters),
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Экспорт для кэширования
     */
    public function toCacheKey(SearchType $searchType, array $activeFilters): string
    {
        return 'search_filter:' . $searchType->value . ':' . $this->getHash($activeFilters);
    }

    /**
     * Экспорт для логирования
     */
    public function toLogFormat(SearchType $searchType, array $activeFilters): array
    {
        return [
            'search_type' => $searchType->value,
            'active_filters' => $activeFilters,
            'filter_count' => count($activeFilters),
            'filter_hash' => $this->getHash($activeFilters),
        ];
    }

    /**
     * Экспорт в URL параметры
     */
    public function toUrlFormat(array $activeFilters): string
    {
        $params = [];
        
        foreach ($activeFilters as $key => $value) {
            if (is_array($value)) {
                $params[$key] = implode(',', $value);
            } else {
                $params[$key] = (string) $value;
            }
        }
        
        return http_build_query($params);
    }

    /**
     * Получить хэш фильтров
     */
    public function getHash(array $activeFilters): string
    {
        // Сортируем для получения стабильного хэша
        ksort($activeFilters);
        return md5(serialize($activeFilters));
    }

    /**
     * Создать фингерпринт фильтров для аналитики
     */
    public function createFingerprint(SearchType $searchType, array $activeFilters): array
    {
        return [
            'type' => $searchType->value,
            'filters' => array_keys($activeFilters),
            'values_hash' => $this->getHash($activeFilters),
            'complexity' => $this->calculateComplexity($activeFilters),
        ];
    }

    /**
     * Экспорт для сохранения пользовательских настроек
     */
    public function toUserPreferences(SearchType $searchType, array $activeFilters): array
    {
        // Фильтруем только те фильтры, которые стоит сохранять как предпочтения
        $savableFilters = array_intersect_key($activeFilters, array_flip([
            'city', 'rating', 'verified', 'price_range', 'sort_by'
        ]));
        
        return [
            'search_type' => $searchType->value,
            'preferred_filters' => $savableFilters,
            'saved_at' => now()->toISOString(),
        ];
    }

    /**
     * Экспорт для API ответа
     */
    public function toApiResponse(SearchFilter $filter): array
    {
        return [
            'search_type' => $filter->getSearchType()->value,
            'filters' => $filter->getFilters(),
            'active_filters' => $filter->getActiveFilters(),
            'active_count' => $filter->getActiveFiltersCount(),
            'has_filters' => $filter->hasActiveFilters(),
            'query_string' => $filter->toQueryString(),
            'cache_key' => $this->toCacheKey($filter->getSearchType(), $filter->getActiveFilters()),
        ];
    }

    /**
     * Рассчитать сложность фильтров
     */
    private function calculateComplexity(array $activeFilters): string
    {
        $count = count($activeFilters);
        
        return match(true) {
            $count === 0 => 'none',
            $count <= 2 => 'simple',
            $count <= 5 => 'medium',
            default => 'complex',
        };
    }
}