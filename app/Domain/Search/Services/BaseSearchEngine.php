<?php

namespace App\Services\Search;

use App\Enums\SortBy;
use App\Repositories\SearchRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Базовый класс для движков поиска
 */
abstract class BaseSearchEngine implements SearchEngineInterface
{
    public function __construct(
        protected SearchRepository $repository
    ) {}

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
    ): LengthAwarePaginator {
        
        $builder = $this->getBaseQuery();
        
        // Применяем текстовый поиск
        if (!empty($query)) {
            $this->applyTextSearch($builder, $query);
        }
        
        // Применяем фильтры
        $this->applyFilters($builder, $filters);
        
        // Добавляем геопоиск если нужно
        if ($location && $sortBy === SortBy::DISTANCE) {
            $this->addDistanceCalculation($builder, $location['lat'], $location['lng']);
        }
        
        // Применяем сортировку
        $this->applySorting($builder, $sortBy);
        
        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Быстрый поиск
     */
    public function quickSearch(string $query, int $limit = 5): array
    {
        $builder = $this->getBaseQuery();
        $this->applyTextSearch($builder, $query);
        
        return $builder->limit($limit)
            ->get()
            ->map(fn($item) => $this->formatQuickResult($item))
            ->toArray();
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query): array
    {
        // Базовая реализация - можно переопределить в наследниках
        $terms = $this->parseSearchQuery($query);
        $related = [];
        
        foreach ($terms as $term) {
            $related = array_merge($related, $this->getRelatedTerms($term));
        }
        
        return array_unique(array_slice($related, 0, 10));
    }

    /**
     * Найти похожие объекты
     */
    public function findSimilar(int $objectId, int $limit = 10, array $excludeIds = []): array
    {
        $object = $this->findObject($objectId);
        if (!$object) {
            return [];
        }
        
        $builder = $this->getBaseQuery();
        $this->applySimilarityFilters($builder, $object);
        
        $builder->where($this->getIdField(), '!=', $objectId);
        
        if (!empty($excludeIds)) {
            $builder->whereNotIn($this->getIdField(), $excludeIds);
        }
        
        return $builder->limit($limit)
            ->get()
            ->map(fn($item) => $this->formatSimilarResult($item))
            ->toArray();
    }

    /**
     * Продвинутый поиск
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $builder = $this->getBaseQuery();
        
        // Текстовые критерии
        if (!empty($criteria['query'])) {
            $this->applyTextSearch($builder, $criteria['query']);
        }
        
        // Применяем все фильтры из критериев
        $this->applyFilters($builder, $criteria);
        
        // Специфичные для продвинутого поиска фильтры
        $this->applyAdvancedFilters($builder, $criteria);
        
        $sortBy = SortBy::tryFrom($criteria['sort'] ?? 'relevance') ?? SortBy::RELEVANCE;
        $this->applySorting($builder, $sortBy);
        
        $page = $criteria['page'] ?? 1;
        $perPage = $criteria['per_page'] ?? 20;
        
        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Экспорт результатов
     */
    public function exportResults(LengthAwarePaginator $results, string $format = 'csv'): string
    {
        return match($format) {
            'csv' => $this->exportToCsv($results),
            'json' => $this->exportToJson($results),
            'xlsx' => $this->exportToExcel($results),
            default => throw new \InvalidArgumentException("Неподдерживаемый формат: {$format}")
        };
    }

    /**
     * Фасетный поиск (базовая реализация)
     */
    public function facetedSearch(string $query, array $facets = []): array
    {
        $builder = $this->getBaseQuery();
        
        if (!empty($query)) {
            $this->applyTextSearch($builder, $query);
        }
        
        $results = [];
        foreach ($facets as $facet) {
            $results[$facet] = $this->calculateFacet($builder, $facet);
        }
        
        return $results;
    }

    /**
     * Геопоиск (базовая реализация)
     */
    public function geoSearch(array $location, float $radius, array $filters = [], int $limit = 20): array
    {
        $builder = $this->getBaseQuery();
        
        // Применяем фильтры
        $this->applyFilters($builder, $filters);
        
        // Добавляем геопоиск
        $this->addDistanceCalculation($builder, $location['lat'], $location['lng']);
        $builder->having('distance', '<=', $radius);
        
        return $builder->orderBy('distance')
            ->limit($limit)
            ->get()
            ->map(fn($item) => $this->formatGeoResult($item))
            ->toArray();
    }

    /**
     * Абстрактные методы для переопределения в наследниках
     */
    abstract protected function getBaseQuery(): Builder;
    abstract protected function applyTextSearch(Builder $builder, string $query): void;
    abstract protected function applyFilters(Builder $builder, array $filters): void;
    abstract protected function getIdField(): string;
    abstract protected function formatQuickResult($item): array;
    abstract protected function formatSimilarResult($item): array;
    abstract protected function formatGeoResult($item): array;

    /**
     * Применить сортировку
     */
    protected function applySorting(Builder $builder, SortBy $sortBy): void
    {
        $expression = $sortBy->getSqlExpression($this->getTableAlias());
        $builder->orderByRaw($expression);
    }

    /**
     * Добавить вычисление расстояния
     */
    protected function addDistanceCalculation(Builder $builder, float $lat, float $lng): void
    {
        $builder->addSelect([
            DB::raw("
                (6371 * acos(cos(radians({$lat})) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians({$lng})) 
                + sin(radians({$lat})) 
                * sin(radians(latitude)))) AS distance
            ")
        ]);
    }

    /**
     * Парсинг поискового запроса
     */
    protected function parseSearchQuery(string $query): array
    {
        $terms = array_filter(explode(' ', trim($query)));
        return array_filter($terms, fn($term) => mb_strlen($term) >= 2);
    }

    /**
     * Получить SQL для расчета релевантности
     */
    protected function getRelevanceScore(string $query, array $fieldWeights): string
    {
        if (empty($query)) {
            return 'COALESCE(popularity_score, 0)';
        }
        
        $terms = $this->parseSearchQuery($query);
        $scoreExpressions = [];
        
        foreach ($fieldWeights as $field => $weight) {
            foreach ($terms as $term) {
                $scoreExpressions[] = "
                    CASE 
                        WHEN {$field} LIKE '%{$term}%' THEN {$weight}
                        ELSE 0 
                    END
                ";
            }
        }
        
        return 'COALESCE((' . implode(' + ', $scoreExpressions) . '), 0)';
    }

    /**
     * Найти объект по ID
     */
    protected function findObject(int $id)
    {
        return $this->getBaseQuery()->find($id);
    }

    /**
     * Применить фильтры для поиска похожих объектов
     */
    protected function applySimilarityFilters(Builder $builder, $object): void
    {
        // Базовая реализация - переопределить в наследниках
    }

    /**
     * Применить продвинутые фильтры
     */
    protected function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        // Базовая реализация - переопределить в наследниках
    }

    /**
     * Вычислить фасет
     */
    protected function calculateFacet(Builder $builder, string $facet): array
    {
        // Базовая реализация - переопределить в наследниках
        return [];
    }

    /**
     * Получить связанные термы
     */
    protected function getRelatedTerms(string $term): array
    {
        // Базовая реализация - можно улучшить через ML или словари
        $synonyms = [
            'массаж' => ['релакс', 'спа', 'терапия'],
            'релакс' => ['массаж', 'отдых', 'расслабление'],
            'спа' => ['массаж', 'релакс', 'процедуры'],
        ];
        
        return $synonyms[mb_strtolower($term)] ?? [];
    }

    /**
     * Получить алиас таблицы
     */
    protected function getTableAlias(): ?string
    {
        return null; // Переопределить в наследниках если нужно
    }

    /**
     * Экспорт в CSV
     */
    protected function exportToCsv(LengthAwarePaginator $results): string
    {
        $output = '';
        $headers = $this->getCsvHeaders();
        $output .= implode(',', $headers) . "\n";
        
        foreach ($results->items() as $item) {
            $row = $this->formatCsvRow($item);
            $output .= implode(',', $row) . "\n";
        }
        
        return $output;
    }

    /**
     * Экспорт в JSON
     */
    protected function exportToJson(LengthAwarePaginator $results): string
    {
        return json_encode([
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Экспорт в Excel
     */
    protected function exportToExcel(LengthAwarePaginator $results): string
    {
        // Здесь должна быть интеграция с библиотекой для Excel (например, PhpSpreadsheet)
        throw new \Exception('Excel export not implemented yet');
    }

    /**
     * Получить заголовки для CSV
     */
    protected function getCsvHeaders(): array
    {
        return ['id', 'title', 'description']; // Переопределить в наследниках
    }

    /**
     * Форматировать строку CSV
     */
    protected function formatCsvRow($item): array
    {
        return [$item->id, $item->title ?? '', $item->description ?? '']; // Переопределить в наследниках
    }
}