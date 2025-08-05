<?php

namespace App\Domain\Search\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Движок рекомендаций
 */
class RecommendationEngine extends BaseSearchEngine
{
    protected function getBaseQuery(): Builder
    {
        $userId = auth()->id();
        
        return Ad::query()
            ->select([
                'ads.*',
                'users.name as master_name',
                'users.rating as master_rating'
            ])
            ->join('users', 'ads.user_id', '=', 'users.id')
            ->with(['user', 'media'])
            ->where('ads.status', 'active')
            ->where('ads.is_published', true)
            ->when($userId, function($query) use ($userId) {
                // Исключаем недавно просмотренные
                $recentlyViewed = $this->getRecentlyViewed($userId);
                if (!empty($recentlyViewed)) {
                    $query->whereNotIn('ads.id', $recentlyViewed);
                }
            });
    }

    protected function applyTextSearch(Builder $builder, string $query): void
    {
        $userId = auth()->id();
        $userPreferences = $this->getUserPreferences($userId);
        
        $personalizedScore = $this->getPersonalizedRelevanceScore($query, $userPreferences);
        $builder->addSelect(DB::raw($personalizedScore . ' as relevance_score'));
        
        if (!empty($query)) {
            $searchTerms = $this->parseSearchQuery($query);
            
            $builder->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('ads.title', 'LIKE', "%{$term}%")
                      ->orWhere('ads.description', 'LIKE', "%{$term}%")
                      ->orWhere('ads.specialty', 'LIKE', "%{$term}%");
                }
            });
        }
    }

    protected function applyFilters(Builder $builder, array $filters): void
    {
        $userId = auth()->id();
        $userPreferences = $this->getUserPreferences($userId);
        
        // Применяем персонализированные фильтры если не переопределены
        if (empty($filters['city']) && !empty($userPreferences['preferred_cities'])) {
            $builder->whereIn('ads.city', $userPreferences['preferred_cities']);
        }
        
        if (empty($filters['specialty']) && !empty($userPreferences['preferred_specialties'])) {
            $builder->whereIn('ads.specialty', $userPreferences['preferred_specialties']);
        }
        
        if (empty($filters['price_range']) && !empty($userPreferences['price_range'])) {
            [$min, $max] = $userPreferences['price_range'];
            $builder->whereBetween('ads.price', [$min, $max]);
        }
        
        // Предпочитаем высокорейтинговых мастеров
        $builder->where('users.rating', '>=', $userPreferences['min_rating'] ?? 3.5);
    }

    protected function getIdField(): string
    {
        return 'ads.id';
    }

    protected function formatQuickResult($item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'price' => $item->price,
            'city' => $item->city,
            'master_name' => $item->user->name,
            'master_rating' => $item->user->rating,
            'recommendation_reason' => $this->getRecommendationReason($item),
            'url' => route('ads.show', $item->id),
        ];
    }

    protected function formatSimilarResult($item): array
    {
        return $this->formatQuickResult($item);
    }

    protected function formatGeoResult($item): array
    {
        return $this->formatQuickResult($item);
    }

    /**
     * Получить персонализированный счет релевантности
     */
    protected function getPersonalizedRelevanceScore(string $query, array $preferences): string
    {
        $baseScore = empty($query) ? 'COALESCE(ads.popularity_score, 0)' : 
            $this->getRelevanceScore($query, [
                'ads.title' => 3.0,
                'ads.description' => 2.0,
                'ads.specialty' => 2.5,
            ]);

        $personalizedBonuses = [];

        // Бонус за предпочитаемые специализации
        if (!empty($preferences['preferred_specialties'])) {
            $specialties = "'" . implode("','", $preferences['preferred_specialties']) . "'";
            $personalizedBonuses[] = "CASE WHEN ads.specialty IN ({$specialties}) THEN 3.0 ELSE 0 END";
        }

        // Бонус за предпочитаемые города
        if (!empty($preferences['preferred_cities'])) {
            $cities = "'" . implode("','", $preferences['preferred_cities']) . "'";
            $personalizedBonuses[] = "CASE WHEN ads.city IN ({$cities}) THEN 2.0 ELSE 0 END";
        }

        // Бонус за новые объявления
        $personalizedBonuses[] = "CASE WHEN ads.created_at >= '" . now()->subDays(7)->toDateString() . "' THEN 1.5 ELSE 0 END";

        // Бонус за высокий рейтинг мастера
        $personalizedBonuses[] = "CASE WHEN users.rating >= 4.5 THEN 2.0 WHEN users.rating >= 4.0 THEN 1.0 ELSE 0 END";

        if (empty($personalizedBonuses)) {
            return $baseScore;
        }

        return $baseScore . ' + ' . implode(' + ', $personalizedBonuses);
    }

    /**
     * Получить недавно просмотренные объявления
     */
    protected function getRecentlyViewed(int $userId): array
    {
        return Cache::remember("recently_viewed_{$userId}", 3600, function() use ($userId) {
            // Здесь должна быть логика получения из БД истории просмотров
            return [];
        });
    }

    /**
     * Получить предпочтения пользователя
     */
    protected function getUserPreferences(int $userId): array
    {
        return Cache::remember("user_preferences_{$userId}", 3600, function() use ($userId) {
            $user = User::find($userId);
            if (!$user) return [];
            
            // Анализируем историю пользователя для определения предпочтений
            return [
                'preferred_cities' => [$user->city ?? 'Москва'],
                'preferred_specialties' => $this->inferPreferredSpecialties($userId),
                'price_range' => $this->inferPriceRange($userId),
                'min_rating' => 3.5,
            ];
        });
    }

    /**
     * Определить предпочитаемые специализации из истории
     */
    protected function inferPreferredSpecialties(int $userId): array
    {
        // Простая логика - можно улучшить через ML
        return ['классический массаж', 'антицеллюлитный массаж'];
    }

    /**
     * Определить предпочитаемый ценовой диапазон
     */
    protected function inferPriceRange(int $userId): array
    {
        // Простая логика - можно улучшить через анализ истории
        return [1500, 4000];
    }

    /**
     * Получить причину рекомендации
     */
    protected function getRecommendationReason($ad): string
    {
        $reasons = [];
        
        if ($ad->user->rating >= 4.5) {
            $reasons[] = 'Высокий рейтинг мастера';
        }
        
        if ($ad->created_at >= now()->subDays(7)) {
            $reasons[] = 'Новое объявление';
        }
        
        if ($ad->user->reviews_count >= 20) {
            $reasons[] = 'Много положительных отзывов';
        }
        
        return implode(', ', $reasons) ?: 'Подходит вашим предпочтениям';
    }
}