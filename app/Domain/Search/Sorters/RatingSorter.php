<?php

namespace App\Domain\Search\Sorters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Сортировщик по рейтингу
 * Соответствует DDD архитектуре - размещен в Domain\Search\Sorters
 */
class RatingSorter implements SorterInterface
{
    protected string $direction = 'desc';
    protected string $ratingField = 'rating';
    protected string $reviewsCountField = 'reviews_count';
    protected bool $considerReviewsCount = true;
    protected int $minReviewsThreshold = 3;

    public function __construct(array $params = [])
    {
        $this->direction = $this->validateDirection($params['direction'] ?? 'desc');
        $this->ratingField = $params['rating_field'] ?? 'rating';
        $this->reviewsCountField = $params['reviews_count_field'] ?? 'reviews_count';
        $this->considerReviewsCount = (bool) ($params['consider_reviews_count'] ?? true);
        $this->minReviewsThreshold = (int) ($params['min_reviews_threshold'] ?? 3);
    }

    /**
     * Применить сортировку к запросу
     */
    public function apply(Builder $query): Builder
    {
        if ($this->considerReviewsCount) {
            // Сортировка с учетом количества отзывов
            // Используем формулу Байесовского среднего для более справедливой сортировки
            return $this->applyBayesianSort($query);
        }

        // Простая сортировка по рейтингу
        return $query->orderBy($this->ratingField, $this->direction);
    }

    /**
     * Применить Байесовскую сортировку
     * Учитывает количество отзывов для более справедливого ранжирования
     */
    protected function applyBayesianSort(Builder $query): Builder
    {
        // Байесовская формула: (R * v + m * C) / (v + m)
        // R = средний рейтинг элемента
        // v = количество отзывов элемента
        // m = минимальное количество отзывов (порог)
        // C = средний рейтинг по всей выборке (обычно 3.5-4.0)

        $averageRating = 3.5; // Можно вычислить динамически
        $m = $this->minReviewsThreshold;

        $bayesianFormula = "((COALESCE({$this->ratingField}, 0) * COALESCE({$this->reviewsCountField}, 0) + {$m} * {$averageRating}) / (COALESCE({$this->reviewsCountField}, 0) + {$m}))";

        return $query->orderByRaw("{$bayesianFormula} {$this->direction}");
    }

    /**
     * Получить параметры сортировки
     */
    public function getParams(): array
    {
        return [
            'direction' => $this->direction,
            'rating_field' => $this->ratingField,
            'reviews_count_field' => $this->reviewsCountField,
            'consider_reviews_count' => $this->considerReviewsCount,
            'min_reviews_threshold' => $this->minReviewsThreshold,
        ];
    }

    /**
     * Получить описание сортировки
     */
    public function getDescription(): string
    {
        $directionText = $this->direction === 'desc' ? 'убыванию' : 'возрастанию';
        
        if ($this->considerReviewsCount) {
            return "По рейтингу ({$directionText}) с учетом количества отзывов";
        }
        
        return "По рейтингу ({$directionText})";
    }

    /**
     * Установить направление сортировки
     */
    public function setDirection(string $direction): self
    {
        $this->direction = $this->validateDirection($direction);
        return $this;
    }

    /**
     * Установить по возрастанию
     */
    public function ascending(): self
    {
        $this->direction = 'asc';
        return $this;
    }

    /**
     * Установить по убыванию
     */
    public function descending(): self
    {
        $this->direction = 'desc';
        return $this;
    }

    /**
     * Учитывать количество отзывов
     */
    public function withReviewsCount(bool $consider = true): self
    {
        $this->considerReviewsCount = $consider;
        return $this;
    }

    /**
     * Установить минимальный порог отзывов
     */
    public function setMinReviewsThreshold(int $threshold): self
    {
        $this->minReviewsThreshold = max(1, $threshold);
        return $this;
    }

    /**
     * Установить поля для сортировки
     */
    public function setFields(string $ratingField, string $reviewsCountField): self
    {
        $this->ratingField = $ratingField;
        $this->reviewsCountField = $reviewsCountField;
        return $this;
    }

    /**
     * Клонировать сортировщик
     */
    public function clone(): self
    {
        return new self($this->getParams());
    }

    /**
     * Инвертировать направление
     */
    public function invert(): self
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        return $this;
    }

    /**
     * Проверить направление сортировки
     */
    protected function validateDirection(string $direction): string
    {
        $direction = strtolower($direction);
        return in_array($direction, ['asc', 'desc']) ? $direction : 'desc';
    }

    /**
     * Получить SQL для сортировки
     */
    public function toSql(): string
    {
        if ($this->considerReviewsCount) {
            $averageRating = 3.5;
            $m = $this->minReviewsThreshold;
            return "((COALESCE({$this->ratingField}, 0) * COALESCE({$this->reviewsCountField}, 0) + {$m} * {$averageRating}) / (COALESCE({$this->reviewsCountField}, 0) + {$m})) {$this->direction}";
        }

        return "{$this->ratingField} {$this->direction}";
    }

    /**
     * Проверить, является ли сортировка по убыванию
     */
    public function isDescending(): bool
    {
        return $this->direction === 'desc';
    }

    /**
     * Проверить, является ли сортировка по возрастанию
     */
    public function isAscending(): bool
    {
        return $this->direction === 'asc';
    }
}