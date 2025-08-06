<?php

namespace App\Domain\Search\Engines\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Фильтр для поиска похожих объявлений
 */
class AdSimilarityFilter
{
    /**
     * Применить фильтры для поиска похожих объектов
     */
    public function applySimilarityFilters(Builder $builder, $ad): void
    {
        // Похожие по специализации
        $builder->where('ads.specialty', $ad->specialty);
        
        // Похожий ценовой диапазон (±30%)
        $priceMin = $ad->price * 0.7;
        $priceMax = $ad->price * 1.3;
        $builder->whereBetween('ads.price', [$priceMin, $priceMax]);
        
        // Тот же город или регион
        $builder->where(function($q) use ($ad) {
            $q->where('ads.city', $ad->city)
              ->orWhere('ads.region', $ad->region);
        });
        
        // Добавляем счет похожести
        $builder->addSelect(DB::raw("
            (
                CASE WHEN ads.specialty = '{$ad->specialty}' THEN 3 ELSE 0 END +
                CASE WHEN ads.city = '{$ad->city}' THEN 2 ELSE 0 END +
                CASE WHEN ads.work_format = '{$ad->work_format}' THEN 1 ELSE 0 END +
                CASE WHEN ads.region = '{$ad->region}' THEN 1 ELSE 0 END
            ) as similarity_score
        "));
        
        $builder->orderBy('similarity_score', 'desc');
    }

    /**
     * Применить продвинутые фильтры
     */
    public function applyAdvancedFilters(Builder $builder, array $criteria): void
    {
        // Исключить объявления
        if (!empty($criteria['exclude_ids'])) {
            $builder->whereNotIn('ads.id', $criteria['exclude_ids']);
        }

        // Конкретные мастера
        if (!empty($criteria['master_ids'])) {
            $builder->whereIn('ads.user_id', $criteria['master_ids']);
        }

        // Диапазон дат
        if (!empty($criteria['date_from'])) {
            $builder->where('ads.created_at', '>=', $criteria['date_from']);
        }
        
        if (!empty($criteria['date_to'])) {
            $builder->where('ads.created_at', '<=', $criteria['date_to']);
        }

        // Сложные фильтры по рейтингу и отзывам
        if (!empty($criteria['rating_reviews_combo'])) {
            $builder->where(function($q) use ($criteria) {
                $combo = $criteria['rating_reviews_combo'];
                if ($combo === 'high_rated') {
                    $q->where('users.rating', '>=', 4.5)
                      ->where('users.reviews_count', '>=', 10);
                } elseif ($combo === 'popular') {
                    $q->where('users.reviews_count', '>=', 20);
                } elseif ($combo === 'new_good') {
                    $q->where('users.rating', '>=', 4.0)
                      ->where('ads.created_at', '>=', now()->subDays(30));
                }
            });
        }

        // Радиус поиска
        if (!empty($criteria['location']) && !empty($criteria['radius'])) {
            $location = $criteria['location'];
            $radius = $criteria['radius'];
            
            $this->addDistanceCalculation($builder, $location['lat'], $location['lng']);
            $builder->having('distance', '<=', $radius);
        }
    }

    /**
     * Добавить вычисление расстояния
     */
    protected function addDistanceCalculation(Builder $builder, float $lat, float $lng): void
    {
        $builder->addSelect(DB::raw("
            (6371 * acos(
                cos(radians($lat)) 
                * cos(radians(ads.latitude)) 
                * cos(radians(ads.longitude) - radians($lng)) 
                + sin(radians($lat)) 
                * sin(radians(ads.latitude))
            )) AS distance
        "));
    }
}