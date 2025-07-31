<?php

namespace App\Services\Search;

use App\Enums\SearchType;
use App\Enums\SortBy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Класс для работы с результатами поиска
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

    /**
     * Получить результаты
     */
    public function getResults(): LengthAwarePaginator
    {
        return $this->results;
    }

    /**
     * Получить элементы результатов
     */
    public function getItems(): Collection
    {
        return collect($this->results->items());
    }

    /**
     * Получить тип поиска
     */
    public function getSearchType(): SearchType
    {
        return $this->searchType;
    }

    /**
     * Получить поисковый запрос
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Получить фильтры
     */
    public function getFilters(): SearchFilter
    {
        return $this->filters;
    }

    /**
     * Получить сортировку
     */
    public function getSortBy(): SortBy
    {
        return $this->sortBy;
    }

    /**
     * Получить общее количество результатов
     */
    public function getTotal(): int
    {
        return $this->results->total();
    }

    /**
     * Получить количество результатов на странице
     */
    public function getCount(): int
    {
        return $this->results->count();
    }

    /**
     * Получить текущую страницу
     */
    public function getCurrentPage(): int
    {
        return $this->results->currentPage();
    }

    /**
     * Получить общее количество страниц
     */
    public function getLastPage(): int
    {
        return $this->results->lastPage();
    }

    /**
     * Проверить наличие результатов
     */
    public function hasResults(): bool
    {
        return $this->getTotal() > 0;
    }

    /**
     * Проверить, есть ли следующая страница
     */
    public function hasNextPage(): bool
    {
        return $this->results->hasMorePages();
    }

    /**
     * Проверить, есть ли предыдущая страница
     */
    public function hasPreviousPage(): bool
    {
        return $this->getCurrentPage() > 1;
    }

    /**
     * Установить метаданные
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Получить метаданные
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Установить время выполнения
     */
    public function setExecutionTime(float $time): self
    {
        $this->executionTime = $time;
        return $this;
    }

    /**
     * Получить время выполнения
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Установить фасеты
     */
    public function setFacets(array $facets): self
    {
        $this->facets = $facets;
        return $this;
    }

    /**
     * Получить фасеты
     */
    public function getFacets(): array
    {
        return $this->facets;
    }

    /**
     * Установить подсказки
     */
    public function setSuggestions(array $suggestions): self
    {
        $this->suggestions = $suggestions;
        return $this;
    }

    /**
     * Получить подсказки
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * Получить статистику результатов
     */
    public function getStatistics(): array
    {
        return [
            'total_results' => $this->getTotal(),
            'results_per_page' => $this->results->perPage(),
            'current_page' => $this->getCurrentPage(),
            'total_pages' => $this->getLastPage(),
            'execution_time' => $this->getExecutionTime(),
            'has_filters' => $this->filters->hasActiveFilters(),
            'active_filters_count' => $this->filters->getActiveFiltersCount(),
            'search_type' => $this->searchType->value,
            'sort_by' => $this->sortBy->value,
        ];
    }

    /**
     * Получить информацию о пагинации
     */
    public function getPaginationInfo(): array
    {
        return [
            'current_page' => $this->getCurrentPage(),
            'last_page' => $this->getLastPage(),
            'per_page' => $this->results->perPage(),
            'total' => $this->getTotal(),
            'from' => $this->results->firstItem(),
            'to' => $this->results->lastItem(),
            'has_more_pages' => $this->hasNextPage(),
            'has_previous_page' => $this->hasPreviousPage(),
        ];
    }

    /**
     * Получить диапазон результатов на странице
     */
    public function getResultsRange(): array
    {
        return [
            'from' => $this->results->firstItem() ?? 0,
            'to' => $this->results->lastItem() ?? 0,
            'total' => $this->getTotal(),
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
     * Получить топ результатов
     */
    public function getTopResults(int $limit = 5): Collection
    {
        return $this->getItems()->take($limit);
    }

    /**
     * Группировать результаты по полю
     */
    public function groupBy(string $field): Collection
    {
        return $this->getItems()->groupBy($field);
    }

    /**
     * Фильтровать результаты
     */
    public function filter(callable $callback): Collection
    {
        return $this->getItems()->filter($callback);
    }

    /**
     * Трансформировать результаты
     */
    public function transform(callable $callback): Collection
    {
        return $this->getItems()->map($callback);
    }

    /**
     * Получить уникальные значения поля
     */
    public function pluck(string $field): Collection
    {
        return $this->getItems()->pluck($field)->unique();
    }

    /**
     * Получить среднее значение поля
     */
    public function average(string $field): float
    {
        return $this->getItems()->avg($field) ?? 0.0;
    }

    /**
     * Получить сумму поля
     */
    public function sum(string $field): float
    {
        return $this->getItems()->sum($field);
    }

    /**
     * Получить минимальное значение поля
     */
    public function min(string $field)
    {
        return $this->getItems()->min($field);
    }

    /**
     * Получить максимальное значение поля
     */
    public function max(string $field)
    {
        return $this->getItems()->max($field);
    }

    /**
     * Экспорт результатов в массив
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'search_type' => $this->searchType->value,
            'sort_by' => $this->sortBy->value,
            'filters' => $this->filters->toArray(),
            'results' => $this->getItems()->toArray(),
            'pagination' => $this->getPaginationInfo(),
            'statistics' => $this->getStatistics(),
            'facets' => $this->facets,
            'suggestions' => $this->suggestions,
            'metadata' => $this->metadata,
            'execution_time' => $this->executionTime,
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
     * Экспорт для API
     */
    public function toApiResponse(): array
    {
        return [
            'data' => $this->getItems()->toArray(),
            'meta' => [
                'query' => $this->query,
                'total' => $this->getTotal(),
                'per_page' => $this->results->perPage(),
                'current_page' => $this->getCurrentPage(),
                'last_page' => $this->getLastPage(),
                'from' => $this->results->firstItem(),
                'to' => $this->results->lastItem(),
            ],
            'links' => [
                'first' => $this->results->url(1),
                'last' => $this->results->url($this->getLastPage()),
                'prev' => $this->results->previousPageUrl(),
                'next' => $this->results->nextPageUrl(),
            ],
            'facets' => $this->facets,
            'suggestions' => $this->suggestions,
            'execution_time' => $this->executionTime,
        ];
    }

    /**
     * Создать сообщение о результатах
     */
    public function getResultsMessage(): string
    {
        $total = $this->getTotal();
        
        if ($total === 0) {
            return $this->getNoResultsMessage();
        }
        
        $typeLabel = $this->searchType->getLabel();
        $queryText = !empty($this->query) ? " по запросу \"{$this->query}\"" : '';
        
        if ($total === 1) {
            return "Найден 1 результат в разделе \"{$typeLabel}\"{$queryText}";
        }
        
        $pluralForm = $this->getPluralForm($total, ['результат', 'результата', 'результатов']);
        return "Найдено {$total} {$pluralForm} в разделе \"{$typeLabel}\"{$queryText}";
    }

    /**
     * Получить сообщение об отсутствии результатов
     */
    public function getNoResultsMessage(): string
    {
        $queryText = !empty($this->query) ? " по запросу \"{$this->query}\"" : '';
        return "К сожалению, ничего не найдено{$queryText}. Попробуйте изменить параметры поиска.";
    }

    /**
     * Получить рекомендации при отсутствии результатов
     */
    public function getNoResultsRecommendations(): array
    {
        return [
            'Проверьте правильность написания',
            'Попробуйте использовать более общие термы',
            'Уберите некоторые фильтры',
            'Расширьте географию поиска',
        ];
    }

    /**
     * Получить альтернативные запросы
     */
    public function getAlternativeQueries(): array
    {
        if (empty($this->suggestions['corrections'])) {
            return [];
        }
        
        return $this->suggestions['corrections'];
    }

    /**
     * Проверить, нужна ли пагинация
     */
    public function needsPagination(): bool
    {
        return $this->getLastPage() > 1;
    }

    /**
     * Получить SEO заголовок
     */
    public function getSeoTitle(): string
    {
        $typeLabel = $this->searchType->getLabel();
        $queryText = !empty($this->query) ? " \"{$this->query}\" - " : ' - ';
        $page = $this->getCurrentPage() > 1 ? " - страница {$this->getCurrentPage()}" : '';
        
        return "{$typeLabel}{$queryText}поиск и заказ услуг{$page}";
    }

    /**
     * Получить SEO описание
     */
    public function getSeoDescription(): string
    {
        $total = $this->getTotal();
        $typeLabel = mb_strtolower($this->searchType->getLabel());
        $queryText = !empty($this->query) ? " по запросу \"{$this->query}\"" : '';
        
        if ($total === 0) {
            return "Поиск {$typeLabel}{$queryText}. Не найдено подходящих результатов.";
        }
        
        return "Найдено {$total} {$typeLabel}{$queryText}. Сравните цены, почитайте отзывы и выберите лучшее предложение.";
    }

    /**
     * Получить множественную форму слова
     */
    protected function getPluralForm(int $number, array $forms): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        return $forms[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * Клонировать результат с новыми фильтрами
     */
    public function cloneWithFilters(SearchFilter $newFilters): self
    {
        $clone = clone $this;
        $clone->filters = $newFilters;
        return $clone;
    }

    /**
     * Клонировать результат с новой сортировкой
     */
    public function cloneWithSorting(SortBy $newSortBy): self
    {
        $clone = clone $this;
        $clone->sortBy = $newSortBy;
        return $clone;
    }

    /**
     * Магический метод для преобразования в строку
     */
    public function __toString(): string
    {
        return $this->getResultsMessage();
    }
}