<?php

namespace App\Infrastructure\Adapters;

use App\Domain\Search\Services\SearchService as ModernSearchService;
use App\Domain\Search\DTOs\SearchData;
use App\Domain\Search\DTOs\SearchResultData;
use App\Enums\SearchType;
use App\Enums\SortBy;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

/**
 * Адаптер для поисковой системы
 * Использует современную архитектуру Domain/Search
 * Обеспечивает обратную совместимость со старыми методами
 */
class SearchServiceAdapter
{
    private ModernSearchService $searchService;
    private bool $enableLogging;

    public function __construct(ModernSearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->enableLogging = config('search.enable_logging', false);
    }

    /**
     * Глобальный поиск
     */
    public function search(string $query, array $options = []): array
    {
        try {
            $searchData = $this->createSearchData($query, $options);
            // SearchService принимает отдельные параметры, не DTO
            $results = $this->searchService->search(
                $searchData->query,
                $searchData->type,
                $searchData->filters,
                $searchData->sortBy,
                $searchData->page,
                $searchData->perPage,
                $searchData->location
            );
            
            if ($this->enableLogging) {
                Log::info('Search performed', [
                    'query' => $query,
                    'options' => $options,
                    'results_count' => $results->total()
                ]);
            }
            
            return $this->formatPaginatorResults($results);
        } catch (Throwable $e) {
            Log::error('Search failed', [
                'error' => $e->getMessage(),
                'query' => $query,
                'options' => $options
            ]);
            
            return $this->emptyResults();
        }
    }

    /**
     * Поиск мастеров
     */
    public function searchMasters(string $query, array $filters = []): array
    {
        try {
            $searchData = new SearchData(
                query: $query,
                type: SearchType::MASTERS,
                filters: $filters,
                sortBy: isset($filters['sort']) ? SortBy::from($filters['sort']) : SortBy::RELEVANCE,
                sortOrder: $filters['sort_order'] ?? 'desc',
                page: isset($filters['page']) ? (int)$filters['page'] : 1,
                perPage: isset($filters['limit']) ? (int)$filters['limit'] : 20,
                location: $filters['location'] ?? null,
                radius: $filters['radius'] ?? null,
                priceRange: $filters['price_range'] ?? null,
                categories: $filters['categories'] ?? null,
                withPhotos: $filters['with_photos'] ?? null,
                verified: $filters['verified'] ?? null,
                metadata: $filters['metadata'] ?? []
            );
            
            // SearchService принимает отдельные параметры, не DTO
            $results = $this->searchService->search(
                $searchData->query,
                $searchData->type,
                $searchData->filters,
                $searchData->sortBy,
                $searchData->page,
                $searchData->perPage,
                $searchData->location
            );
            
            if ($this->enableLogging) {
                Log::info('Master search performed', [
                    'query' => $query,
                    'filters' => $filters,
                    'results_count' => $results->total()
                ]);
            }
            
            return $this->formatPaginatorToArray($results);
        } catch (Throwable $e) {
            Log::error('Master search failed', [
                'error' => $e->getMessage(),
                'query' => $query,
                'filters' => $filters
            ]);
            
            return [];
        }
    }

    /**
     * Поиск услуг
     */
    public function searchServices(string $query, array $filters = []): array
    {
        try {
            $searchData = new SearchData(
                query: $query,
                type: SearchType::SERVICES,
                filters: $filters,
                sortBy: isset($filters['sort']) ? SortBy::from($filters['sort']) : SortBy::RELEVANCE,
                sortOrder: $filters['sort_order'] ?? 'desc',
                page: isset($filters['page']) ? (int)$filters['page'] : 1,
                perPage: isset($filters['limit']) ? (int)$filters['limit'] : 20,
                location: $filters['location'] ?? null,
                radius: $filters['radius'] ?? null,
                priceRange: $filters['price_range'] ?? null,
                categories: $filters['categories'] ?? null,
                withPhotos: $filters['with_photos'] ?? null,
                verified: $filters['verified'] ?? null,
                metadata: $filters['metadata'] ?? []
            );
            
            // SearchService принимает отдельные параметры, не DTO
            $results = $this->searchService->search(
                $searchData->query,
                $searchData->type,
                $searchData->filters,
                $searchData->sortBy,
                $searchData->page,
                $searchData->perPage,
                $searchData->location
            );
            
            if ($this->enableLogging) {
                Log::info('Service search performed', [
                    'query' => $query,
                    'filters' => $filters,
                    'results_count' => $results->total()
                ]);
            }
            
            return $this->formatPaginatorToArray($results);
        } catch (Throwable $e) {
            Log::error('Service search failed', [
                'error' => $e->getMessage(),
                'query' => $query,
                'filters' => $filters
            ]);
            
            return [];
        }
    }

    /**
     * Получить подсказки (автодополнение)
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        try {
            // Используем правильный метод getSearchSuggestions
            return $this->searchService->getSearchSuggestions(
                $query,
                SearchType::ALL,
                $limit
            );
        } catch (Throwable $e) {
            Log::error('Get suggestions failed', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            
            return [];
        }
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularSearches(int $limit = 10): array
    {
        try {
            // Используем правильный метод getPopularQueries
            return $this->searchService->getPopularQueries(
                SearchType::ALL,
                $limit
            );
        } catch (Throwable $e) {
            Log::error('Get popular searches failed', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Индексировать модель (заглушка для обратной совместимости)
     * @deprecated Используйте события моделей для автоматической индексации
     */
    public function indexModel(Model $model): bool
    {
        if ($this->enableLogging) {
            Log::warning('SearchServiceAdapter::indexModel is deprecated. Use model events for indexing.', [
                'model' => get_class($model),
                'id' => $model->getKey()
            ]);
        }
        
        // Возвращаем true для обратной совместимости
        return true;
    }

    /**
     * Удалить из индекса (заглушка для обратной совместимости)
     * @deprecated Используйте события моделей для автоматического удаления из индекса
     */
    public function removeFromIndex(Model $model): bool
    {
        if ($this->enableLogging) {
            Log::warning('SearchServiceAdapter::removeFromIndex is deprecated. Use model events for index removal.', [
                'model' => get_class($model),
                'id' => $model->getKey()
            ]);
        }
        
        // Возвращаем true для обратной совместимости
        return true;
    }

    /**
     * Создать SearchData из параметров
     */
    private function createSearchData(string $query, array $options): SearchData
    {
        $type = isset($options['type']) 
            ? SearchType::from($options['type']) 
            : SearchType::ALL;
            
        $sortBy = isset($options['sort']) 
            ? SortBy::from($options['sort']) 
            : SortBy::RELEVANCE;
        
        return new SearchData(
            query: $query,
            type: $type,
            filters: $options['filters'] ?? [],
            sortBy: $sortBy,
            sortOrder: $options['sort_order'] ?? 'desc',
            page: isset($options['page']) ? (int)$options['page'] : 1,
            perPage: isset($options['limit']) ? (int)$options['limit'] : 20,
            location: $options['location'] ?? null,
            radius: $options['radius'] ?? null,
            priceRange: $options['price_range'] ?? null,
            categories: $options['categories'] ?? null,
            withPhotos: $options['with_photos'] ?? null,
            verified: $options['verified'] ?? null,
            metadata: $options['metadata'] ?? []
        );
    }

    /**
     * Форматировать результаты пагинатора для обратной совместимости
     */
    private function formatPaginatorResults(LengthAwarePaginator $paginator): array
    {
        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'path' => $paginator->path(),
            'links' => $paginator->linkCollection()->toArray()
        ];
    }

    /**
     * Форматировать пагинатор в простой массив
     */
    private function formatPaginatorToArray(LengthAwarePaginator $paginator): array
    {
        return $paginator->toArray()['data'] ?? [];
    }

    /**
     * Вернуть пустые результаты
     */
    private function emptyResults(): array
    {
        return [
            'items' => [],
            'total' => 0,
            'facets' => []
        ];
    }

    /**
     * Проксирование неопределенных методов для обратной совместимости
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->searchService, $method)) {
            Log::warning("SearchServiceAdapter: Proxying to SearchService::{$method}");
            return $this->searchService->$method(...$arguments);
        }
        
        Log::error("SearchServiceAdapter: Undefined method called: {$method}");
        throw new \BadMethodCallException("Method {$method} does not exist");
    }
}