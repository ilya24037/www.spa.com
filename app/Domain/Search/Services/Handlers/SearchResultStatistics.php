<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Services\SearchResult;

/**
 * Обработчик статистики результатов поиска
 */
class SearchResultStatistics
{
    private SearchResult $searchResult;

    public function __construct(SearchResult $searchResult)
    {
        $this->searchResult = $searchResult;
    }

    /**
     * Получить статистику результатов
     */
    public function getStatistics(): array
    {
        return [
            'total_results' => $this->searchResult->getTotal(),
            'results_per_page' => $this->searchResult->getResults()->perPage(),
            'current_page' => $this->searchResult->getCurrentPage(),
            'total_pages' => $this->searchResult->getLastPage(),
            'execution_time' => $this->searchResult->getExecutionTime(),
            'has_filters' => $this->searchResult->getFilters()->hasActiveFilters(),
            'active_filters_count' => $this->searchResult->getFilters()->getActiveFiltersCount(),
            'search_type' => $this->searchResult->getSearchType()->value,
            'sort_by' => $this->searchResult->getSortBy()->value,
        ];
    }

    /**
     * Получить информацию о пагинации
     */
    public function getPaginationInfo(): array
    {
        $results = $this->searchResult->getResults();
        
        return [
            'current_page' => $this->searchResult->getCurrentPage(),
            'last_page' => $this->searchResult->getLastPage(),
            'per_page' => $results->perPage(),
            'total' => $this->searchResult->getTotal(),
            'from' => $results->firstItem(),
            'to' => $results->lastItem(),
            'has_more_pages' => $this->searchResult->hasNextPage(),
            'has_previous_page' => $this->searchResult->hasPreviousPage(),
        ];
    }

    /**
     * Получить диапазон результатов на странице
     */
    public function getResultsRange(): array
    {
        $results = $this->searchResult->getResults();
        
        return [
            'from' => $results->firstItem() ?? 0,
            'to' => $results->lastItem() ?? 0,
            'total' => $this->searchResult->getTotal(),
        ];
    }

    /**
     * Форматировать диапазон результатов
     */
    public function getFormattedResultsRange(): string
    {
        $range = $this->getResultsRange();
        
        if ($range['total'] === 0) {
            return 'Ничего не найдено';
        }
        
        if ($range['from'] === $range['to']) {
            return "Результат {$range['from']} из {$range['total']}";
        }
        
        return "Результаты {$range['from']}–{$range['to']} из {$range['total']}";
    }

    /**
     * Проверить, нужна ли пагинация
     */
    public function needsPagination(): bool
    {
        return $this->searchResult->getLastPage() > 1;
    }

    /**
     * Получить общую статистику производительности
     */
    public function getPerformanceStats(): array
    {
        return [
            'execution_time_ms' => round($this->searchResult->getExecutionTime() * 1000, 2),
            'results_per_second' => $this->searchResult->getExecutionTime() > 0 
                ? round($this->searchResult->getTotal() / $this->searchResult->getExecutionTime(), 0) 
                : 0,
            'is_fast_query' => $this->searchResult->getExecutionTime() < 0.5,
            'is_slow_query' => $this->searchResult->getExecutionTime() > 2.0,
        ];
    }

    /**
     * Получить статистику фильтрации
     */
    public function getFilterStats(): array
    {
        $filters = $this->searchResult->getFilters();
        
        return [
            'has_active_filters' => $filters->hasActiveFilters(),
            'active_filters_count' => $filters->getActiveFiltersCount(),
            'filter_types' => array_keys($filters->getActiveFilters()),
            'is_heavily_filtered' => $filters->getActiveFiltersCount() > 3,
        ];
    }
}