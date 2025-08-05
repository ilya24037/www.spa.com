<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SortBy;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Интерфейс для движков поиска
 */
interface SearchEngineInterface
{
    /**
     * Выполнить поиск
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator;

    /**
     * Быстрый поиск (для автодополнения)
     */
    public function quickSearch(string $query, int $limit = 5): array;

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query): array;

    /**
     * Найти похожие объекты
     */
    public function findSimilar(int $objectId, int $limit = 10, array $excludeIds = []): array;

    /**
     * Продвинутый поиск
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator;

    /**
     * Экспорт результатов
     */
    public function exportResults(LengthAwarePaginator $results, string $format = 'csv'): string;

    /**
     * Фасетный поиск (опционально)
     */
    public function facetedSearch(string $query, array $facets = []): array;

    /**
     * Геопоиск (опционально)
     */
    public function geoSearch(array $location, float $radius, array $filters = [], int $limit = 20): array;
}