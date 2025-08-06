<?php

namespace App\Domain\Search\Engines\Facets;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Калькулятор фасетов для поиска объявлений
 */
class AdFacetCalculator
{
    /**
     * Рассчитать фасет
     */
    public function calculateFacet(Builder $builder, string $facet): array
    {
        return match($facet) {
            'cities' => $this->calculateCitiesFacet($builder),
            'specialties' => $this->calculateSpecialtiesFacet($builder),
            'price_ranges' => $this->calculatePriceRangesFacet($builder),
            'ratings' => $this->calculateRatingsFacet($builder),
            'work_formats' => $this->calculateWorkFormatsFacet($builder),
            default => []
        };
    }

    /**
     * Рассчитать фасет городов
     */
    public function calculateCitiesFacet(Builder $builder): array
    {
        return (clone $builder)
            ->select('ads.city as value', DB::raw('COUNT(*) as count'))
            ->groupBy('ads.city')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'value')
            ->toArray();
    }

    /**
     * Рассчитать фасет специальностей
     */
    public function calculateSpecialtiesFacet(Builder $builder): array
    {
        return (clone $builder)
            ->select('ads.specialty as value', DB::raw('COUNT(*) as count'))
            ->whereNotNull('ads.specialty')
            ->groupBy('ads.specialty')
            ->orderBy('count', 'desc')
            ->limit(15)
            ->pluck('count', 'value')
            ->toArray();
    }

    /**
     * Рассчитать фасет ценовых диапазонов
     */
    public function calculatePriceRangesFacet(Builder $builder): array
    {
        $ranges = [
            '0-1000' => [0, 1000],
            '1000-3000' => [1000, 3000],
            '3000-5000' => [3000, 5000],
            '5000-10000' => [5000, 10000],
            '10000+' => [10000, PHP_INT_MAX]
        ];

        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('ads.price', $range)
                ->count();
            
            if ($count > 0) {
                $result[$label] = $count;
            }
        }

        return $result;
    }

    /**
     * Рассчитать фасет рейтингов
     */
    public function calculateRatingsFacet(Builder $builder): array
    {
        $ranges = [
            '4.5+' => 4.5,
            '4.0+' => 4.0,
            '3.5+' => 3.5,
            '3.0+' => 3.0
        ];

        $result = [];
        foreach ($ranges as $label => $minRating) {
            $count = (clone $builder)
                ->where('users.rating', '>=', $minRating)
                ->count();
            
            if ($count > 0) {
                $result[$label] = $count;
            }
        }

        return $result;
    }

    /**
     * Рассчитать фасет форматов работы
     */
    public function calculateWorkFormatsFacet(Builder $builder): array
    {
        return [
            'at_salon' => (clone $builder)
                ->whereJsonContains('ads.work_format', 'at_salon')
                ->count(),
            'at_home' => (clone $builder)
                ->whereJsonContains('ads.work_format', 'at_home')
                ->count(),
            'mobile' => (clone $builder)
                ->whereJsonContains('ads.work_format', 'mobile')
                ->count(),
        ];
    }
}