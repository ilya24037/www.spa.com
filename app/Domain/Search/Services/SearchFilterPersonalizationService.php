<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;

/**
 * Сервис персонализации поисковых фильтров
 */
class SearchFilterPersonalizationService
{
    /**
     * Получить персонализированные фильтры для пользователя
     */
    public function getPersonalizedFilters(int $userId, SearchType $searchType): array
    {
        // Получаем историю поиска пользователя
        $searchHistory = $this->getUserSearchHistory($userId, $searchType);
        
        // Получаем предпочтения пользователя
        $userPreferences = $this->getUserPreferences($userId);
        
        // Получаем геолокацию пользователя
        $userLocation = $this->getUserLocation($userId);
        
        $personalizedFilters = [];
        
        // Добавляем фильтр по городу на основе истории или профиля
        if ($userLocation && !empty($userLocation['city'])) {
            $personalizedFilters['city'] = $userLocation['city'];
        }
        
        // Добавляем фильтры на основе истории поиска
        if ($searchHistory) {
            $personalizedFilters = array_merge($personalizedFilters, $this->extractFiltersFromHistory($searchHistory));
        }
        
        // Добавляем фильтры на основе предпочтений
        if ($userPreferences) {
            $personalizedFilters = array_merge($personalizedFilters, $this->extractFiltersFromPreferences($userPreferences, $searchType));
        }
        
        return $personalizedFilters;
    }

    /**
     * Получить рекомендуемые фильтры на основе поведения
     */
    public function getRecommendedFilters(int $userId, SearchType $searchType): array
    {
        $userBehavior = $this->getUserBehaviorAnalytics($userId);
        
        $recommendations = [];
        
        // Рекомендация по рейтингу на основе выборок пользователя
        if (isset($userBehavior['average_selected_rating'])) {
            $recommendations['rating'] = max(3.0, $userBehavior['average_selected_rating'] - 0.5);
        }
        
        // Рекомендация по ценовому диапазону
        if (isset($userBehavior['preferred_price_range'])) {
            $recommendations['price_range'] = $userBehavior['preferred_price_range'];
        }
        
        // Рекомендация по верификации
        if (isset($userBehavior['prefers_verified']) && $userBehavior['prefers_verified']) {
            $recommendations['verified'] = true;
        }
        
        return $recommendations;
    }

    /**
     * Применить умные настройки для времени дня
     */
    public function applyTimeBasedFilters(array $filters, SearchType $searchType): array
    {
        $currentHour = now()->hour;
        
        // В рабочие часы показываем более доступных мастеров
        if ($currentHour >= 9 && $currentHour <= 18) {
            $filters['availability'] = true;
        }
        
        // Вечером и выходными показываем мастеров с выездом
        if ($currentHour >= 19 || now()->isWeekend()) {
            $filters['home_service'] = true;
        }
        
        return $filters;
    }

    /**
     * Получить историю поиска пользователя
     */
    private function getUserSearchHistory(int $userId, SearchType $searchType): ?array
    {
        // Здесь должна быть интеграция с системой аналитики
        // Пока возвращаем заглушку
        return [
            'most_searched_city' => 'Москва',
            'frequent_filters' => ['rating' => 4.0, 'verified' => true],
            'last_searches' => [],
        ];
    }

    /**
     * Получить предпочтения пользователя
     */
    private function getUserPreferences(int $userId): ?array
    {
        // Здесь должна быть интеграция с профилем пользователя
        return [
            'preferred_city' => 'Москва',
            'budget_range' => [1000, 5000],
            'quality_priority' => 'high',
        ];
    }

    /**
     * Получить геолокацию пользователя
     */
    private function getUserLocation(int $userId): ?array
    {
        // Здесь должна быть интеграция с сервисом геолокации
        return [
            'city' => 'Москва',
            'coordinates' => [55.7558, 37.6173],
        ];
    }

    /**
     * Извлечь фильтры из истории поиска
     */
    private function extractFiltersFromHistory(array $searchHistory): array
    {
        $filters = [];
        
        if (isset($searchHistory['most_searched_city'])) {
            $filters['city'] = $searchHistory['most_searched_city'];
        }
        
        if (isset($searchHistory['frequent_filters'])) {
            $filters = array_merge($filters, $searchHistory['frequent_filters']);
        }
        
        return $filters;
    }

    /**
     * Извлечь фильтры из предпочтений пользователя
     */
    private function extractFiltersFromPreferences(array $preferences, SearchType $searchType): array
    {
        $filters = [];
        
        if (isset($preferences['preferred_city'])) {
            $filters['city'] = $preferences['preferred_city'];
        }
        
        if (isset($preferences['budget_range']) && $searchType !== SearchType::MASTERS) {
            $filters['price_range'] = $preferences['budget_range'];
        }
        
        if (isset($preferences['quality_priority']) && $preferences['quality_priority'] === 'high') {
            $filters['rating'] = 4.5;
            $filters['verified'] = true;
        }
        
        return $filters;
    }

    /**
     * Получить аналитику поведения пользователя
     */
    private function getUserBehaviorAnalytics(int $userId): array
    {
        // Здесь должна быть интеграция с системой аналитики
        return [
            'average_selected_rating' => 4.2,
            'preferred_price_range' => [1500, 4000],
            'prefers_verified' => true,
        ];
    }
}