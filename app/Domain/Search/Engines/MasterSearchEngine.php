<?php

namespace App\Domain\Search\Engines;

use App\Domain\Search\Services\BaseSearchEngine;
use App\Domain\Search\Engines\Handlers\MasterQueryBuilder;
use App\Domain\Search\Engines\Handlers\MasterFilterHandler;
use App\Domain\Search\Engines\Handlers\MasterResultFormatter;
use App\Domain\Search\Engines\Handlers\MasterFacetCalculator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Упрощенный движок поиска мастеров
 * Делегирует работу специализированным обработчикам
 */
class MasterSearchEngine extends BaseSearchEngine
{
    private MasterQueryBuilder $queryBuilder;
    private MasterFilterHandler $filterHandler;
    private MasterResultFormatter $resultFormatter;
    private MasterFacetCalculator $facetCalculator;

    public function __construct(
        MasterQueryBuilder $queryBuilder,
        MasterFilterHandler $filterHandler,
        MasterResultFormatter $resultFormatter,
        MasterFacetCalculator $facetCalculator
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->filterHandler = $filterHandler;
        $this->resultFormatter = $resultFormatter;
        $this->facetCalculator = $facetCalculator;
    }

    /**
     * Получить базовый запрос
     */
    protected function getBaseQuery(): Builder
    {
        return $this->queryBuilder->getBaseQuery();
    }

    /**
     * Применить текстовый поиск
     */
    protected function applyTextSearch(Builder $builder, string $query): void
    {
        $this->queryBuilder->applyTextSearch($builder, $query);
    }

    /**
     * Применить фильтры
     */
    protected function applyFilters(Builder $builder, array $filters): void
    {
        $this->filterHandler->applyFilters($builder, $filters);
    }

    /**
     * Применить продвинутые фильтры
     */
    protected function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        $this->filterHandler->applyAdvancedFilters($builder, $criteria);
    }

    /**
     * Применить фильтры для поиска похожих объектов
     */
    protected function applySimilarityFilters(Builder $builder, $master): void
    {
        $this->queryBuilder->applySimilarityFilters($builder, $master);
    }

    /**
     * Получить поле ID
     */
    protected function getIdField(): string
    {
        return $this->queryBuilder->getIdField();
    }

    /**
     * Получить алиас таблицы
     */
    protected function getTableAlias(): string
    {
        return $this->queryBuilder->getTableAlias();
    }

    /**
     * Форматировать результат быстрого поиска
     */
    protected function formatQuickResult($item): array
    {
        return $this->resultFormatter->formatQuickResult($item);
    }

    /**
     * Форматировать результат поиска похожих
     */
    protected function formatSimilarResult($item): array
    {
        return $this->resultFormatter->formatSimilarResult($item);
    }

    /**
     * Форматировать результат геопоиска
     */
    protected function formatGeoResult($item): array
    {
        return $this->resultFormatter->formatGeoResult($item);
    }

    /**
     * Вычислить фасет
     */
    protected function calculateFacet(Builder $builder, string $facet): array
    {
        return $this->facetCalculator->calculateFacet($builder, $facet);
    }

    /**
     * Получить заголовки для CSV
     */
    protected function getCsvHeaders(): array
    {
        return $this->resultFormatter->getCsvHeaders();
    }

    /**
     * Форматировать строку CSV
     */
    protected function formatCsvRow($item): array
    {
        return $this->resultFormatter->formatCsvRow($item);
    }

    // === ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ ===

    /**
     * Получить доступные фасеты
     */
    public function getAvailableFacets(): array
    {
        return $this->facetCalculator->getAvailableFacets();
    }

    /**
     * Получить локализованные названия фасетов
     */
    public function getFacetLabels(): array
    {
        return $this->facetCalculator->getFacetLabels();
    }

    /**
     * Построить сложный поисковый запрос
     */
    public function buildComplexSearchQuery(array $params): Builder
    {
        $builder = $this->getBaseQuery();

        if (!empty($params['query'])) {
            $this->applyTextSearch($builder, $params['query']);
        }

        if (!empty($params['filters'])) {
            $this->applyFilters($builder, $params['filters']);
        }

        if (!empty($params['advanced'])) {
            $this->applyAdvancedFilters($builder, $params['advanced']);
        }

        return $builder;
    }

    /**
     * Получить статистику поиска
     */
    public function getSearchStats(Builder $builder): array
    {
        $totalCount = $builder->count();
        
        $facets = [];
        foreach ($this->getAvailableFacets() as $facetType) {
            $facets[$facetType] = $this->calculateFacet($builder, $facetType);
        }

        return [
            'total_results' => $totalCount,
            'facets' => $facets,
            'has_results' => $totalCount > 0,
            'search_time' => microtime(true),
        ];
    }
}