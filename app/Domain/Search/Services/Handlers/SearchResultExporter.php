<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Services\SearchResult;

/**
 * Экспортер результатов поиска в различные форматы
 */
class SearchResultExporter
{
    private SearchResult $searchResult;

    public function __construct(SearchResult $searchResult)
    {
        $this->searchResult = $searchResult;
    }

    /**
     * Экспорт результатов в массив
     */
    public function toArray(): array
    {
        return [
            'query' => $this->searchResult->getQuery(),
            'search_type' => $this->searchResult->getSearchType()->value,
            'sort_by' => $this->searchResult->getSortBy()->value,
            'filters' => $this->searchResult->getFilters()->toArray(),
            'results' => $this->searchResult->getItems()->toArray(),
            'pagination' => $this->getPaginationData(),
            'statistics' => $this->getStatisticsData(),
            'facets' => $this->searchResult->getFacets(),
            'suggestions' => $this->searchResult->getSuggestions(),
            'metadata' => $this->searchResult->getMetadata(),
            'execution_time' => $this->searchResult->getExecutionTime(),
        ];
    }

    /**
     * Экспорт в JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Экспорт для API ответа
     */
    public function toApiResponse(): array
    {
        $results = $this->searchResult->getResults();
        
        return [
            'data' => $this->searchResult->getItems()->toArray(),
            'meta' => [
                'query' => $this->searchResult->getQuery(),
                'total' => $this->searchResult->getTotal(),
                'per_page' => $results->perPage(),
                'current_page' => $this->searchResult->getCurrentPage(),
                'last_page' => $this->searchResult->getLastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'links' => [
                'first' => $results->url(1),
                'last' => $results->url($this->searchResult->getLastPage()),
                'prev' => $results->previousPageUrl(),
                'next' => $results->nextPageUrl(),
            ],
            'facets' => $this->searchResult->getFacets(),
            'suggestions' => $this->searchResult->getSuggestions(),
            'execution_time' => $this->searchResult->getExecutionTime(),
        ];
    }

    /**
     * Экспорт минимальной версии для кеширования
     */
    public function toMinimalCache(): array
    {
        return [
            'items' => $this->searchResult->getItems()->toArray(),
            'total' => $this->searchResult->getTotal(),
            'execution_time' => $this->searchResult->getExecutionTime(),
            'facets' => $this->searchResult->getFacets(),
        ];
    }

    /**
     * Экспорт для CSV
     */
    public function toCsv(array $fields = []): string
    {
        $items = $this->searchResult->getItems();
        
        if ($items->isEmpty()) {
            return '';
        }

        // Если поля не указаны, берем все из первого элемента
        if (empty($fields)) {
            $fields = array_keys($items->first()->toArray());
        }

        $csv = [];
        
        // Заголовки
        $csv[] = implode(',', array_map(fn($field) => '"' . $field . '"', $fields));
        
        // Данные
        foreach ($items as $item) {
            $row = [];
            foreach ($fields as $field) {
                $value = data_get($item, $field, '');
                $row[] = '"' . str_replace('"', '""', $value) . '"';
            }
            $csv[] = implode(',', $row);
        }

        return implode("\n", $csv);
    }

    /**
     * Экспорт для Excel (массив данных)
     */
    public function toExcel(array $fields = []): array
    {
        $items = $this->searchResult->getItems();
        
        if ($items->isEmpty()) {
            return [];
        }

        // Если поля не указаны, берем все из первого элемента
        if (empty($fields)) {
            $fields = array_keys($items->first()->toArray());
        }

        $data = [];
        
        // Заголовки
        $data[] = $fields;
        
        // Данные
        foreach ($items as $item) {
            $row = [];
            foreach ($fields as $field) {
                $row[] = data_get($item, $field, '');
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Экспорт для RSS/XML
     */
    public function toRss(): array
    {
        return [
            'title' => $this->searchResult->getQuery() ?: 'Результаты поиска',
            'description' => $this->getSearchDescription(),
            'items' => $this->searchResult->getItems()->map(function ($item) {
                return [
                    'title' => data_get($item, 'title') ?: data_get($item, 'name', 'Без названия'),
                    'description' => data_get($item, 'description', ''),
                    'link' => data_get($item, 'url', ''),
                    'pubDate' => data_get($item, 'created_at', now()),
                ];
            })->toArray(),
        ];
    }

    /**
     * Получить данные пагинации для экспорта
     */
    private function getPaginationData(): array
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
     * Получить данные статистики для экспорта
     */
    private function getStatisticsData(): array
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
     * Получить описание поиска
     */
    private function getSearchDescription(): string
    {
        $query = $this->searchResult->getQuery();
        $total = $this->searchResult->getTotal();
        $type = $this->searchResult->getSearchType()->getLabel();
        
        if (empty($query)) {
            return "Результаты поиска в категории \"{$type}\" - найдено {$total}";
        }
        
        return "Поиск \"{$query}\" в категории \"{$type}\" - найдено {$total} результатов";
    }
}