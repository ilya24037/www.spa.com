<?php

namespace App\Application\Services\Query;

use App\Domain\Master\Contracts\MasterQueryInterface;
use App\Domain\User\Contracts\UserQueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Сервис запросов для User-Master интеграции - координатор
 * Реализует CQRS паттерн для чтения междоменных данных
 */
class UserMasterQueryService
{
    public function __construct(
        private MasterQueryInterface $masterQuery,
        private UserQueryInterface $userQuery,
        private UserInfoService $userInfoService,
        private MasterAnalyticsService $analyticsService,
        private MasterScoringService $scoringService,
        private MasterModerationService $moderationService,
        private MasterReactivationService $reactivationService
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
            $userInfo = $this->userInfoService->getUserInfoForMaster($master->user_id);
            
            $result[] = [
                'master' => $master,
                'user' => $userInfo,
                'registration_date' => $userInfo['created_at'] ?? null,
                'profile_completion' => $this->scoringService->calculateProfileCompletion($master, $userInfo),
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
            $userInfo = $this->userInfoService->getUserInfoForMaster($master->user_id);
            $masterRating = $this->analyticsService->getMasterRatingAnalytics($master->id);
            
            $result[] = [
                'master' => $master,
                'user' => $userInfo,
                'rating_analytics' => $masterRating,
                'popularity_score' => $this->analyticsService->calculatePopularityScore($master),
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
            $userInfo = $this->userInfoService->getUserInfoForMaster($master->user_id);
            
            $master->user_details = $userInfo;
            $master->moderation_priority = $this->moderationService->calculateModerationPriority($master, $userInfo);
            $master->risk_score = $this->scoringService->calculateRiskScore($master, $userInfo);
            
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
                        'user' => $this->userInfoService->getUserInfoForMaster($master->user_id)
                    ];
                }),
                'demographics' => $cityDemographics,
                'market_saturation' => $this->analyticsService->calculateMarketSaturation($cityData, $cityDemographics),
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
            $userInfo = $this->userInfoService->getUserInfoForMaster($master->user_id);
            $activityAnalysis = $this->analyticsService->analyzeUserActivity($master->user_id, $days);
            
            $result[] = [
                'master' => $master,
                'user' => $userInfo,
                'activity_analysis' => $activityAnalysis,
                'reactivation_analysis' => $this->reactivationService->getReactivationAnalysis($master, $activityAnalysis),
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
            $userInfo = $this->userInfoService->getUserInfoForMaster($master->user_id);
            
            $master->user_info = $userInfo;
            $master->profile_score = $this->scoringService->calculateProfileScore($master, $userInfo);
            $master->trust_level = $this->scoringService->calculateTrustLevel($master, $userInfo);
            
            return $master;
        });
    }
}