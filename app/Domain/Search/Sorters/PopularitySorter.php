<?php

namespace App\Domain\Search\Sorters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Сортировщик по популярности (просмотры, бронирования, отзывы)
 * Соответствует DDD архитектуре - размещен в Domain\Search\Sorters
 */
class PopularitySorter implements SorterInterface
{
    protected string $direction = 'desc';
    protected array $factors = [];
    protected array $weights = [];

    public function __construct(array $params = [])
    {
        $this->direction = $this->validateDirection($params['direction'] ?? 'desc');
        
        // Факторы популярности
        $this->factors = $params['factors'] ?? [
            'views_count' => true,
            'bookings_count' => true,
            'reviews_count' => true,
            'rating' => true,
        ];

        // Веса факторов (по умолчанию)
        $this->weights = $params['weights'] ?? [
            'views_count' => 0.1,      // 10% веса
            'bookings_count' => 0.4,   // 40% веса
            'reviews_count' => 0.3,    // 30% веса
            'rating' => 0.2,          // 20% веса
        ];
    }

    /**
     * Применить сортировку к запросу
     */
    public function apply(Builder $query): Builder
    {
        $formula = $this->buildPopularityFormula();
        
        if (!$formula) {
            return $query;
        }

        return $query->orderByRaw("{$formula} {$this->direction}");
    }

    /**
     * Построить формулу расчета популярности
     */
    protected function buildPopularityFormula(): string
    {
        $parts = [];
        $totalWeight = 0;

        foreach ($this->factors as $factor => $enabled) {
            if (!$enabled || !isset($this->weights[$factor])) {
                continue;
            }

            $weight = $this->weights[$factor];
            $totalWeight += $weight;

            switch ($factor) {
                case 'views_count':
                    // Логарифмическая шкала для просмотров
                    $parts[] = "(LOG10(COALESCE(views_count, 0) + 1) * {$weight})";
                    break;

                case 'bookings_count':
                    // Линейная шкала для бронирований
                    $parts[] = "(COALESCE(bookings_count, 0) * {$weight})";
                    break;

                case 'reviews_count':
                    // Корневая шкала для отзывов
                    $parts[] = "(SQRT(COALESCE(reviews_count, 0)) * {$weight})";
                    break;

                case 'rating':
                    // Нормализованный рейтинг (0-5 -> 0-1)
                    $parts[] = "((COALESCE(rating, 0) / 5) * {$weight})";
                    break;

                default:
                    // Для кастомных факторов
                    $parts[] = "(COALESCE({$factor}, 0) * {$weight})";
            }
        }

        if (empty($parts)) {
            return '';
        }

        // Нормализуем по общему весу
        if ($totalWeight > 0 && $totalWeight != 1) {
            return '((' . implode(' + ', $parts) . ") / {$totalWeight})";
        }

        return '(' . implode(' + ', $parts) . ')';
    }

    /**
     * Получить параметры сортировки
     */
    public function getParams(): array
    {
        return [
            'direction' => $this->direction,
            'factors' => $this->factors,
            'weights' => $this->weights,
        ];
    }

    /**
     * Получить описание сортировки
     */
    public function getDescription(): string
    {
        $activeFactors = array_keys(array_filter($this->factors));
        
        if (empty($activeFactors)) {
            return 'По популярности (факторы не выбраны)';
        }

        $factorNames = array_map(function($factor) {
            return $this->getFactorName($factor);
        }, $activeFactors);

        return 'По популярности (' . implode(', ', $factorNames) . ')';
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
     * Установить факторы
     */
    public function setFactors(array $factors): self
    {
        $this->factors = $factors;
        return $this;
    }

    /**
     * Включить фактор
     */
    public function enableFactor(string $factor, bool $enable = true): self
    {
        $this->factors[$factor] = $enable;
        return $this;
    }

    /**
     * Установить веса
     */
    public function setWeights(array $weights): self
    {
        $this->weights = $weights;
        return $this;
    }

    /**
     * Установить вес фактора
     */
    public function setWeight(string $factor, float $weight): self
    {
        $this->weights[$factor] = max(0, $weight);
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
     * Получить название фактора
     */
    protected function getFactorName(string $factor): string
    {
        return match($factor) {
            'views_count' => 'просмотры',
            'bookings_count' => 'бронирования',
            'reviews_count' => 'отзывы',
            'rating' => 'рейтинг',
            default => $factor,
        };
    }

    /**
     * Добавить счетчик популярности к выборке
     */
    public function withPopularityScore(Builder $query, string $alias = 'popularity_score'): Builder
    {
        $formula = $this->buildPopularityFormula();
        
        if (!$formula) {
            return $query;
        }

        return $query->selectRaw("*, {$formula} as {$alias}");
    }
}