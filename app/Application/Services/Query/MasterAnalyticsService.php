<?php

namespace App\Application\Services\Query;

/**
 * Сервис аналитики мастеров
 */
class MasterAnalyticsService
{
    /**
     * Получить аналитику рейтинга мастера
     */
    public function getMasterRatingAnalytics(int $masterId): array
    {
        // Здесь должна быть интеграция с доменом отзывов
        // Пока возвращаем заглушку
        return [
            'average_rating' => 4.5,
            'total_reviews' => 25,
            'rating_distribution' => [5 => 15, 4 => 8, 3 => 2, 2 => 0, 1 => 0],
            'recent_trend' => 'increasing'
        ];
    }

    /**
     * Рассчитать популярность мастера
     */
    public function calculatePopularityScore(object $master): float
    {
        $score = 0;
        
        // Рейтинг (40% веса)
        $score += ($master->rating ?? 0) * 0.4;
        
        // Количество отзывов (30% веса)
        $reviewsScore = min(($master->reviews_count ?? 0) / 100, 1) * 5;
        $score += $reviewsScore * 0.3;
        
        // Активность (30% веса)  
        $activityScore = $master->is_active ? 5 : 0;
        $score += $activityScore * 0.3;
        
        return round($score, 2);
    }

    /**
     * Проанализировать активность пользователя
     */
    public function analyzeUserActivity(int $userId, int $days): array
    {
        // Здесь должна быть интеграция с системой аналитики
        // Пока возвращаем заглушку
        return [
            'last_login' => now()->subDays(rand(1, $days)),
            'profile_updates' => rand(0, 5),
            'messages_sent' => rand(0, 20),
            'bookings_received' => rand(0, 10),
            'activity_score' => rand(0, 100)
        ];
    }

    /**
     * Рассчитать насыщенность рынка
     */
    public function calculateMarketSaturation(array $cityData, array $demographics): string
    {
        $mastersCount = $cityData['masters_count'] ?? 0;
        $population = $demographics['population'] ?? 100000;
        
        $mastersPerCapita = $population > 0 ? ($mastersCount / $population) * 1000 : 0;
        
        return match(true) {
            $mastersPerCapita > 5 => 'high',
            $mastersPerCapita > 2 => 'medium',
            default => 'low'
        };
    }
}