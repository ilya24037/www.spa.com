<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Services\SearchResult;
use Illuminate\Support\Collection;

/**
 * Агрегатор данных результатов поиска
 */
class SearchResultAggregator
{
    private SearchResult $searchResult;

    public function __construct(SearchResult $searchResult)
    {
        $this->searchResult = $searchResult;
    }

    /**
     * Получить топ результатов
     */
    public function getTopResults(int $limit = 5): Collection
    {
        return $this->searchResult->getItems()->take($limit);
    }

    /**
     * Группировать результаты по полю
     */
    public function groupBy(string $field): Collection
    {
        return $this->searchResult->getItems()->groupBy($field);
    }

    /**
     * Фильтровать результаты
     */
    public function filter(callable $callback): Collection
    {
        return $this->searchResult->getItems()->filter($callback);
    }

    /**
     * Трансформировать результаты
     */
    public function transform(callable $callback): Collection
    {
        return $this->searchResult->getItems()->map($callback);
    }

    /**
     * Получить уникальные значения поля
     */
    public function pluck(string $field): Collection
    {
        return $this->searchResult->getItems()->pluck($field)->unique();
    }

    /**
     * Получить среднее значение поля
     */
    public function average(string $field): float
    {
        return $this->searchResult->getItems()->avg($field) ?? 0.0;
    }

    /**
     * Получить сумму поля
     */
    public function sum(string $field): float
    {
        return $this->searchResult->getItems()->sum($field);
    }

    /**
     * Получить минимальное значение поля
     */
    public function min(string $field)
    {
        return $this->searchResult->getItems()->min($field);
    }

    /**
     * Получить максимальное значение поля
     */
    public function max(string $field)
    {
        return $this->searchResult->getItems()->max($field);
    }

    /**
     * Получить распределение по категориям
     */
    public function getDistribution(string $field): array
    {
        return $this->searchResult->getItems()
            ->groupBy($field)
            ->map->count()
            ->sortDesc()
            ->toArray();
    }

    /**
     * Получить статистику по числовому полю
     */
    public function getNumericStats(string $field): array
    {
        $values = $this->searchResult->getItems()->pluck($field)->filter()->values();
        
        if ($values->isEmpty()) {
            return [
                'count' => 0,
                'sum' => 0,
                'min' => null,
                'max' => null,
                'avg' => null,
                'median' => null,
            ];
        }

        $sorted = $values->sort()->values();
        $count = $values->count();
        $median = $count % 2 === 0
            ? ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2
            : $sorted[intval($count / 2)];

        return [
            'count' => $count,
            'sum' => $values->sum(),
            'min' => $values->min(),
            'max' => $values->max(),
            'avg' => round($values->avg(), 2),
            'median' => $median,
        ];
    }

    /**
     * Получить топ значений по полю
     */
    public function getTopValues(string $field, int $limit = 10): array
    {
        return $this->searchResult->getItems()
            ->pluck($field)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->toArray();
    }

    /**
     * Найти похожие результаты
     */
    public function findSimilar(string $field, $value, int $limit = 5): Collection
    {
        return $this->searchResult->getItems()
            ->filter(fn($item) => $item->{$field} === $value)
            ->take($limit);
    }

    /**
     * Получить случайную выборку
     */
    public function getRandomSample(int $count = 10): Collection
    {
        return $this->searchResult->getItems()->random(
            min($count, $this->searchResult->getItems()->count())
        );
    }

    /**
     * Получить результаты с высоким рейтингом
     */
    public function getHighRated(string $ratingField = 'rating', float $threshold = 4.0): Collection
    {
        return $this->searchResult->getItems()
            ->filter(fn($item) => ($item->{$ratingField} ?? 0) >= $threshold);
    }

    /**
     * Получить недавние результаты
     */
    public function getRecent(string $dateField = 'created_at', int $days = 7): Collection
    {
        $cutoff = now()->subDays($days);
        
        return $this->searchResult->getItems()
            ->filter(fn($item) => $item->{$dateField} >= $cutoff);
    }
}