<?php

namespace App\Application\Services\Query;

use App\Domain\Master\Contracts\MasterQueryInterface;
use App\Domain\User\Contracts\UserQueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Сервис запросов для User-Master интеграции
 * Реализует CQRS паттерн для чтения междоменных данных
 */
class UserMasterQueryService
{
    public function __construct(
        private MasterQueryInterface $masterQuery,
        private UserQueryInterface $userQuery
    ) {}

    /**
     * Получить мастеров-пользователей с пагинацией и фильтрами
     */
    public function getUserMastersPaginated(
        array $userFilters = [],
        array $masterFilters = [], 
        int $perPage = 15
    ): LengthAwarePaginator {
        // Объединяем фильтры пользователей и мастеров
        $combinedFilters = array_merge($userFilters, $masterFilters);
        
        return $this->masterQuery->getMastersPaginated($combinedFilters, $perPage);
    }

    /**
     * Получить новых мастеров с информацией о пользователях
     */
    public function getNewMastersWithUserInfo(int $days = 30, int $limit = 10): array
    {
        $newMasters = $this->masterQuery->getNewMasters($days, $limit);
        $result = [];

        foreach ($newMasters as $master) {
            $userInfo = $this->getUserInfoForMaster($master->user_id);
            
            $result[] = [
                'master' => $master,
                'user' => $userInfo,
                'registration_date' => $userInfo['created_at'] ?? null,
                'profile_completion' => $this->calculateProfileCompletion($master, $userInfo),
            ];
        }

        return $result;
    }

    /**
     * Получить популярных мастеров с рейтингами пользователей
     */
    public function getPopularMastersWithUserRatings(int $period = 30, int $limit = 10): array
    {
        $popularMasters = $this->masterQuery->getPopularMasters($period, $limit);
        $result = [];

        foreach ($popularMasters as $master) {
            $userInfo = $this->getUserInfoForMaster($master->user_id);
            $masterRating = $this->getMasterRatingAnalytics($master->id);
            
            $result[] = [
                'master' => $master,
                'user' => $userInfo,
                'rating_analytics' => $masterRating,
                'popularity_score' => $this->calculatePopularityScore($master),
            ];
        }

        return $result;
    }

    /**
     * Получить мастеров для модерации с полной информацией о пользователях
     */
    public function getMastersForModerationWithUserDetails(): Collection
    {
        $mastersForModeration = $this->masterQuery->getMastersForModeration();
        
        return $mastersForModeration->map(function ($master) {
            $userInfo = $this->getUserInfoForMaster($master->user_id);
            
            $master->user_details = $userInfo;
            $master->moderation_priority = $this->calculateModerationPriority($master, $userInfo);
            $master->risk_score = $this->calculateRiskScore($master, $userInfo);
            
            return $master;
        });
    }

    /**
     * Получить топ мастеров по городам с демографией пользователей
     */
    public function getTopMastersByCitiesWithDemographics(): array
    {
        $cityStatistics = $this->masterQuery->getCityStatistics();
        $userDemographics = $this->userQuery->getDemographics();
        
        $result = [];
        
        foreach ($cityStatistics as $cityData) {
            $city = $cityData['city'];
            $topMasters = $this->masterQuery->getMastersByRating(4.0, 5)
                ->where('city', $city);
                
            $cityDemographics = $userDemographics[$city] ?? [];
            
            $result[$city] = [
                'masters_count' => $cityData['masters_count'],
                'top_masters' => $topMasters->map(function ($master) {
                    return [
                        'master' => $master,
                        'user' => $this->getUserInfoForMaster($master->user_id)
                    ];
                }),
                'demographics' => $cityDemographics,
                'market_saturation' => $this->calculateMarketSaturation($cityData, $cityDemographics),
            ];
        }

        return $result;
    }

    /**
     * Получить неактивных мастеров с анализом пользовательской активности
     */
    public function getInactiveMastersWithUserActivity(int $days = 30): array
    {
        $inactiveMasters = $this->masterQuery->getInactiveMasters($days);
        $result = [];

        foreach ($inactiveMasters as $master) {
            $userInfo = $this->getUserInfoForMaster($master->user_id);
            $activityAnalysis = $this->analyzeUserActivity($master->user_id, $days);
            
            $result[] = [
                'master' => $master,
                'user' => $userInfo,
                'activity_analysis' => $activityAnalysis,
                'reactivation_potential' => $this->calculateReactivationPotential($master, $activityAnalysis),
                'recommended_actions' => $this->getReactivationRecommendations($master, $activityAnalysis),
            ];
        }

        return $result;
    }

    /**
     * Поиск мастеров с расширенной информацией о пользователях
     */
    public function searchMastersWithUserDetails(
        string $query, 
        array $filters = []
    ): Collection {
        $masters = $this->masterQuery->searchMasters($query, $filters);
        
        return $masters->map(function ($master) {
            $userInfo = $this->getUserInfoForMaster($master->user_id);
            
            $master->user_info = $userInfo;
            $master->profile_score = $this->calculateProfileScore($master, $userInfo);
            $master->trust_level = $this->calculateTrustLevel($master, $userInfo);
            
            return $master;
        });
    }

    /**
     * Получить информацию о пользователе для мастера
     */
    private function getUserInfoForMaster(int $userId): ?array
    {
        $users = $this->userQuery->searchUsers('', ['id' => $userId]);
        $user = $users->first();
        
        return $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'email_verified_at' => $user->email_verified_at,
            'profile' => $user->profile ?? null,
            'settings' => $user->settings ?? null,
        ] : null;
    }

    /**
     * Рассчитать процент заполнения профиля
     */
    private function calculateProfileCompletion(object $master, ?array $userInfo): int
    {
        $requiredFields = [
            'name' => !empty($master->name),
            'city' => !empty($master->city),
            'services' => !empty($master->services),
            'description' => !empty($master->description),
            'phone' => !empty($master->phone),
            'email' => !empty($userInfo['email'] ?? ''),
            'avatar' => !empty($master->avatar),
            'work_zones' => !empty($master->work_zones),
            'schedule' => !empty($master->schedule),
            'pricing' => !empty($master->pricing),
        ];

        $completedFields = array_filter($requiredFields);
        
        return (int) round((count($completedFields) / count($requiredFields)) * 100);
    }

    /**
     * Получить аналитику рейтинга мастера
     */
    private function getMasterRatingAnalytics(int $masterId): array
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
    private function calculatePopularityScore(object $master): float
    {
        // Алгоритм расчета популярности на основе различных метрик
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
     * Рассчитать приоритет модерации
     */
    private function calculateModerationPriority(object $master, ?array $userInfo): string
    {
        $factors = [];
        
        // Проверяем подозрительные признаки
        if (empty($master->description) || strlen($master->description) < 50) {
            $factors[] = 'short_description';
        }
        
        if (empty($userInfo['email_verified_at'])) {
            $factors[] = 'unverified_email';
        }
        
        if (count($master->services ?? []) > 10) {
            $factors[] = 'too_many_services';
        }
        
        return match(count($factors)) {
            0 => 'low',
            1 => 'medium',
            default => 'high'
        };
    }

    /**
     * Рассчитать оценку риска
     */
    private function calculateRiskScore(object $master, ?array $userInfo): int
    {
        $riskScore = 0;
        
        // Новый аккаунт
        if ($userInfo && now()->diffInDays($userInfo['created_at']) < 7) {
            $riskScore += 20;
        }
        
        // Неподтвержденный email
        if (empty($userInfo['email_verified_at'])) {
            $riskScore += 15;
        }
        
        // Нет описания
        if (empty($master->description)) {
            $riskScore += 10;
        }
        
        // Подозрительно низкие цены
        if (!empty($master->pricing) && min($master->pricing) < 500) {
            $riskScore += 15;
        }
        
        return min($riskScore, 100);
    }

    /**
     * Проанализировать активность пользователя
     */
    private function analyzeUserActivity(int $userId, int $days): array
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
     * Рассчитать потенциал реактивации
     */
    private function calculateReactivationPotential(object $master, array $activityAnalysis): string
    {
        $score = $activityAnalysis['activity_score'];
        $profileCompletion = $this->calculateProfileCompletion($master, null);
        
        $potential = ($score + $profileCompletion) / 2;
        
        return match(true) {
            $potential >= 70 => 'high',
            $potential >= 40 => 'medium',
            default => 'low'
        };
    }

    /**
     * Получить рекомендации по реактивации
     */
    private function getReactivationRecommendations(object $master, array $activityAnalysis): array
    {
        $recommendations = [];
        
        if ($activityAnalysis['profile_updates'] < 2) {
            $recommendations[] = 'Предложить обновить профиль';
        }
        
        if ($activityAnalysis['bookings_received'] === 0) {
            $recommendations[] = 'Провести рекламную кампанию';
        }
        
        if ($activityAnalysis['activity_score'] < 30) {
            $recommendations[] = 'Отправить персональное предложение';
        }
        
        return $recommendations;
    }

    /**
     * Рассчитать оценку профиля
     */
    private function calculateProfileScore(object $master, ?array $userInfo): int
    {
        $completion = $this->calculateProfileCompletion($master, $userInfo);
        $rating = ($master->rating ?? 0) * 20;
        $reviews = min(($master->reviews_count ?? 0) * 2, 20);
        
        return min($completion + $rating + $reviews, 100);
    }

    /**
     * Рассчитать уровень доверия
     */
    private function calculateTrustLevel(object $master, ?array $userInfo): string
    {
        $trustScore = 0;
        
        // Подтвержденный email
        if (!empty($userInfo['email_verified_at'])) {
            $trustScore += 25;
        }
        
        // Высокий рейтинг
        if (($master->rating ?? 0) >= 4.5) {
            $trustScore += 25;
        }
        
        // Много отзывов
        if (($master->reviews_count ?? 0) >= 20) {
            $trustScore += 25;
        }
        
        // Активность
        if ($master->is_active ?? false) {
            $trustScore += 25;
        }
        
        return match(true) {
            $trustScore >= 75 => 'high',
            $trustScore >= 50 => 'medium',
            default => 'low'
        };
    }

    /**
     * Рассчитать насыщенность рынка
     */
    private function calculateMarketSaturation(array $cityData, array $demographics): string
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