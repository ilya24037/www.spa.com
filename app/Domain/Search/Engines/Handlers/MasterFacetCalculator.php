<?php

namespace App\Domain\Search\Engines\Handlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Калькулятор фасетов для поиска мастеров
 */
class MasterFacetCalculator
{
    /**
     * Вычислить фасет по типу
     */
    public function calculateFacet(Builder $builder, string $facet): array
    {
        $clonedBuilder = clone $builder;
        
        return match($facet) {
            'cities' => $this->calculateCitiesFacet($clonedBuilder),
            'specialties' => $this->calculateSpecialtiesFacet($clonedBuilder),
            'experience_levels' => $this->calculateExperienceLevelsFacet($clonedBuilder),
            'price_ranges' => $this->calculatePriceRangesFacet($clonedBuilder),
            'ratings' => $this->calculateRatingsFacet($clonedBuilder),
            'genders' => $this->calculateGendersFacet($clonedBuilder),
            default => []
        };
    }

    /**
     * Вычислить фасет городов
     */
    public function calculateCitiesFacet(Builder $builder): array
    {
        return $builder
            ->select('users.city', DB::raw('count(*) as count'))
            ->groupBy('users.city')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->pluck('count', 'city')
            ->toArray();
    }

    /**
     * Вычислить фасет специализаций
     */
    public function calculateSpecialtiesFacet(Builder $builder): array
    {
        return $builder
            ->select('users.specialty', DB::raw('count(*) as count'))
            ->groupBy('users.specialty')
            ->orderBy('count', 'desc')
            ->limit(15)
            ->pluck('count', 'specialty')
            ->toArray();
    }

    /**
     * Вычислить фасет уровней опыта
     */
    public function calculateExperienceLevelsFacet(Builder $builder): array
    {
        $levels = [
            'Новичок (0-1 год)' => [0, 1],
            'Начинающий (1-3 года)' => [1, 3],
            'Опытный (3-7 лет)' => [3, 7],
            'Эксперт (7+ лет)' => [7, 100],
        ];
        
        $result = [];
        foreach ($levels as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('users.experience_years', $range)
                ->count();
                
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет ценовых диапазонов
     */
    public function calculatePriceRangesFacet(Builder $builder): array
    {
        $ranges = [
            'До 1500' => [0, 1500],
            '1500-2500' => [1500, 2500],
            '2500-4000' => [2500, 4000],
            '4000-6000' => [4000, 6000],
            'От 6000' => [6000, PHP_INT_MAX],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->whereBetween('users.min_price', $range)
                ->count();
                
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет рейтингов
     */
    public function calculateRatingsFacet(Builder $builder): array
    {
        $ranges = [
            '4.8+' => [4.8, 5.0],
            '4.5+' => [4.5, 5.0],
            '4.0+' => [4.0, 5.0],
            '3.5+' => [3.5, 5.0],
        ];
        
        $result = [];
        foreach ($ranges as $label => $range) {
            $count = (clone $builder)
                ->having('avg_rating', '>=', $range[0])
                ->count();
                
            if ($count > 0) {
                $result[$label] = $count;
            }
        }
        
        return $result;
    }

    /**
     * Вычислить фасет полов
     */
    public function calculateGendersFacet(Builder $builder): array
    {
        return $builder
            ->select('users.gender', DB::raw('count(*) as count'))
            ->whereNotNull('users.gender')
            ->groupBy('users.gender')
            ->orderBy('count', 'desc')
            ->pluck('count', 'gender')
            ->toArray();
    }

    /**
     * Получить все доступные типы фасетов
     */
    public function getAvailableFacets(): array
    {
        return [
            'cities',
            'specialties', 
            'experience_levels',
            'price_ranges',
            'ratings',
            'genders'
        ];
    }

    /**
     * Получить локализованные названия фасетов
     */
    public function getFacetLabels(): array
    {
        return [
            'cities' => 'Города',
            'specialties' => 'Специализации',
            'experience_levels' => 'Уровень опыта',
            'price_ranges' => 'Ценовые диапазоны',
            'ratings' => 'Рейтинги',
            'genders' => 'Пол'
        ];
    }
}