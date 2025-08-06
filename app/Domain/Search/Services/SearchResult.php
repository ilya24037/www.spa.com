<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use App\Domain\Search\Services\Handlers\SearchResultStatistics;
use App\Domain\Search\Services\Handlers\SearchResultAggregator;
use App\Domain\Search\Services\Handlers\SearchResultExporter;
use App\Domain\Search\Services\Handlers\SearchResultFormatter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Упрощенный класс для работы с результатами поиска
 * Делегирует работу специализированным обработчикам
 */
class SearchResult
{
    protected LengthAwarePaginator $results;
    protected SearchType $searchType;
    protected string $query;
    protected SearchFilter $filters;
    protected SortBy $sortBy;
    protected array $metadata = [];
    protected array $facets = [];
    protected array $suggestions = [];
    protected float $executionTime = 0.0;

    // Специализированные обработчики
    private ?SearchResultStatistics $statistics = null;
    private ?SearchResultAggregator $aggregator = null;
    private ?SearchResultExporter $exporter = null;
    private ?SearchResultFormatter $formatter = null;

    public function __construct(
        LengthAwarePaginator $results,
        SearchType $searchType,
        string $query,
        SearchFilter $filters,
        SortBy $sortBy
    ) {
        $this->results = $results;
        $this->searchType = $searchType;
        $this->query = $query;
        $this->filters = $filters;
        $this->sortBy = $sortBy;
    }

    /**
     * Создать из результатов пагинации
     */
    public static function fromPaginator(
        LengthAwarePaginator $results,
        SearchType $searchType,
        string $query,
        SearchFilter $filters,
        SortBy $sortBy
    ): self {
        return new self($results, $searchType, $query, $filters, $sortBy);
    }

    // === БАЗОВЫЕ ГЕТТЕРЫ ===

    public function getResults(): LengthAwarePaginator { return $this->results; }
    public function getItems(): Collection { return collect($this->results->items()); }
    public function getSearchType(): SearchType { return $this->searchType; }
    public function getQuery(): string { return $this->query; }
    public function getFilters(): SearchFilter { return $this->filters; }
    public function getSortBy(): SortBy { return $this->sortBy; }
    public function getTotal(): int { return $this->results->total(); }
    public function getCount(): int { return $this->results->count(); }
    public function getCurrentPage(): int { return $this->results->currentPage(); }
    public function getLastPage(): int { return $this->results->lastPage(); }
    public function hasResults(): bool { return $this->getTotal() > 0; }
    public function hasNextPage(): bool { return $this->results->hasMorePages(); }
    public function hasPreviousPage(): bool { return $this->getCurrentPage() > 1; }

    // === СЕТТЕРЫ ===

    public function setMetadata(array $metadata): self { $this->metadata = $metadata; return $this; }
    public function getMetadata(): array { return $this->metadata; }
    public function setExecutionTime(float $time): self { $this->executionTime = $time; return $this; }
    public function getExecutionTime(): float { return $this->executionTime; }
    public function setFacets(array $facets): self { $this->facets = $facets; return $this; }
    public function getFacets(): array { return $this->facets; }
    public function setSuggestions(array $suggestions): self { $this->suggestions = $suggestions; return $this; }
    public function getSuggestions(): array { return $this->suggestions; }

    // === ДЕЛЕГИРОВАННЫЕ МЕТОДЫ СТАТИСТИКИ ===

    public function getStatistics(): array
    {
        return $this->getStatisticsHandler()->getStatistics();
    }

    public function getPaginationInfo(): array
    {
        return $this->getStatisticsHandler()->getPaginationInfo();
    }

    public function getResultsRange(): array
    {
        return $this->getStatisticsHandler()->getResultsRange();
    }

    public function getFormattedResultsRange(): string
    {
        return $this->getStatisticsHandler()->getFormattedResultsRange();
    }

    public function needsPagination(): bool
    {
        return $this->getStatisticsHandler()->needsPagination();
    }

    // === ДЕЛЕГИРОВАННЫЕ МЕТОДЫ АГРЕГАЦИИ ===

    public function getTopResults(int $limit = 5): Collection
    {
        return $this->getAggregatorHandler()->getTopResults($limit);
    }

    public function groupBy(string $field): Collection
    {
        return $this->getAggregatorHandler()->groupBy($field);
    }

    public function filter(callable $callback): Collection
    {
        return $this->getAggregatorHandler()->filter($callback);
    }

    public function transform(callable $callback): Collection
    {
        return $this->getAggregatorHandler()->transform($callback);
    }

    public function pluck(string $field): Collection
    {
        return $this->getAggregatorHandler()->pluck($field);
    }

    public function average(string $field): float
    {
        return $this->getAggregatorHandler()->average($field);
    }

    public function sum(string $field): float
    {
        return $this->getAggregatorHandler()->sum($field);
    }

    public function min(string $field)
    {
        return $this->getAggregatorHandler()->min($field);
    }

    public function max(string $field)
    {
        return $this->getAggregatorHandler()->max($field);
    }

    // === ДЕЛЕГИРОВАННЫЕ МЕТОДЫ ЭКСПОРТА ===

    public function toArray(): array
    {
        return $this->getExporterHandler()->toArray();
    }

    public function toJson(): string
    {
        return $this->getExporterHandler()->toJson();
    }

    public function toApiResponse(): array
    {
        return $this->getExporterHandler()->toApiResponse();
    }

    // === ДЕЛЕГИРОВАННЫЕ МЕТОДЫ ФОРМАТИРОВАНИЯ ===

    public function getResultsMessage(): string
    {
        return $this->getFormatterHandler()->getResultsMessage();
    }

    public function getNoResultsMessage(): string
    {
        return $this->getFormatterHandler()->getNoResultsMessage();
    }

    public function getNoResultsRecommendations(): array
    {
        return $this->getFormatterHandler()->getNoResultsRecommendations();
    }

    public function getAlternativeQueries(): array
    {
        return $this->getFormatterHandler()->getAlternativeQueries();
    }

    public function getSeoTitle(): string
    {
        return $this->getFormatterHandler()->getSeoTitle();
    }

    public function getSeoDescription(): string
    {
        return $this->getFormatterHandler()->getSeoDescription();
    }

    // === МЕТОДЫ КЛОНИРОВАНИЯ ===

    public function cloneWithFilters(SearchFilter $newFilters): self
    {
        $clone = clone $this;
        $clone->filters = $newFilters;
        return $clone;
    }

    public function cloneWithSorting(SortBy $newSortBy): self
    {
        $clone = clone $this;
        $clone->sortBy = $newSortBy;
        return $clone;
    }

    // === МАГИЧЕСКИЕ МЕТОДЫ ===

    public function __toString(): string
    {
        return $this->getResultsMessage();
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ПОЛУЧЕНИЯ ОБРАБОТЧИКОВ ===

    private function getStatisticsHandler(): SearchResultStatistics
    {
        return $this->statistics ??= new SearchResultStatistics($this);
    }

    private function getAggregatorHandler(): SearchResultAggregator
    {
        return $this->aggregator ??= new SearchResultAggregator($this);
    }

    private function getExporterHandler(): SearchResultExporter
    {
        return $this->exporter ??= new SearchResultExporter($this);
    }

    private function getFormatterHandler(): SearchResultFormatter
    {
        return $this->formatter ??= new SearchResultFormatter($this);
    }
}