<?php

namespace App\Application\Services\Query;

/**
 * Сервис реактивации мастеров
 */
class MasterReactivationService
{
    public function __construct(
        private MasterScoringService $scoringService
    ) {}

    /**
     * Рассчитать потенциал реактивации
     */
    public function calculateReactivationPotential(object $master, array $activityAnalysis): string
    {
        $score = $activityAnalysis['activity_score'];
        $profileCompletion = $this->scoringService->calculateProfileCompletion($master, null);
        
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
    public function getReactivationRecommendations(object $master, array $activityAnalysis): array
    {
        $recommendations = [];
        
        if ($activityAnalysis['profile_updates'] < 2) {
            $recommendations[] = [
                'type' => 'profile_update',
                'priority' => 'high',
                'action' => 'Предложить обновить профиль',
                'description' => 'Профиль давно не обновлялся'
            ];
        }
        
        if ($activityAnalysis['bookings_received'] === 0) {
            $recommendations[] = [
                'type' => 'marketing',
                'priority' => 'medium',
                'action' => 'Провести рекламную кампанию',
                'description' => 'Нет активных бронирований'
            ];
        }
        
        if ($activityAnalysis['activity_score'] < 30) {
            $recommendations[] = [
                'type' => 'personal_offer',
                'priority' => 'high',
                'action' => 'Отправить персональное предложение',
                'description' => 'Очень низкая активность'
            ];
        }
        
        if (($master->rating ?? 0) < 4.0) {
            $recommendations[] = [
                'type' => 'quality_improvement',
                'priority' => 'medium',
                'action' => 'Предложить тренинг по качеству услуг',
                'description' => 'Низкий рейтинг'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Получить полный анализ реактивации
     */
    public function getReactivationAnalysis(object $master, array $activityAnalysis): array
    {
        return [
            'potential' => $this->calculateReactivationPotential($master, $activityAnalysis),
            'recommendations' => $this->getReactivationRecommendations($master, $activityAnalysis),
            'priority_score' => $this->calculateReactivationPriority($master, $activityAnalysis),
            'estimated_effort' => $this->estimateReactivationEffort($master, $activityAnalysis),
        ];
    }

    /**
     * Рассчитать приоритет реактивации
     */
    private function calculateReactivationPriority(object $master, array $activityAnalysis): int
    {
        $priority = 0;
        
        // Высокий рейтинг - высокий приоритет
        if (($master->rating ?? 0) >= 4.5) {
            $priority += 30;
        }
        
        // Много отзывов - высокий приоритет
        if (($master->reviews_count ?? 0) >= 10) {
            $priority += 25;
        }
        
        // Недавняя активность - средний приоритет
        if ($activityAnalysis['activity_score'] > 50) {
            $priority += 20;
        }
        
        // Полный профиль - средний приоритет
        if ($this->scoringService->calculateProfileCompletion($master, null) > 80) {
            $priority += 25;
        }
        
        return min($priority, 100);
    }

    /**
     * Оценить усилия для реактивации
     */
    private function estimateReactivationEffort(object $master, array $activityAnalysis): string
    {
        $effortScore = 0;
        
        // Низкая активность = больше усилий
        if ($activityAnalysis['activity_score'] < 30) {
            $effortScore += 30;
        }
        
        // Неполный профиль = больше усилий
        if ($this->scoringService->calculateProfileCompletion($master, null) < 50) {
            $effortScore += 25;
        }
        
        // Низкий рейтинг = больше усилий
        if (($master->rating ?? 0) < 3.5) {
            $effortScore += 25;
        }
        
        // Нет отзывов = больше усилий
        if (($master->reviews_count ?? 0) === 0) {
            $effortScore += 20;
        }
        
        return match(true) {
            $effortScore >= 70 => 'high',
            $effortScore >= 40 => 'medium',
            default => 'low'
        };
    }
}