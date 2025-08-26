<?php

namespace App\Application\Services\Query;

/**
 * Сервис модерации мастеров
 */
class MasterModerationService
{
    public function __construct(
        private MasterScoringService $scoringService
    ) {}

    /**
     * Рассчитать приоритет модерации
     */
    public function calculateModerationPriority(object $master, ?array $userInfo): string
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
     * Получить детали модерации мастера
     */
    public function getModerationDetails(object $master, ?array $userInfo): array
    {
        return [
            'moderation_priority' => $this->calculateModerationPriority($master, $userInfo),
            'risk_score' => $this->scoringService->calculateRiskScore($master, $userInfo),
            'trust_level' => $this->scoringService->calculateTrustLevel($master, $userInfo),
            'suspicious_factors' => $this->getSuspiciousFactors($master, $userInfo),
        ];
    }

    /**
     * Получить подозрительные факторы
     */
    public function getSuspiciousFactors(object $master, ?array $userInfo): array
    {
        $factors = [];
        
        if (empty($master->description) || strlen($master->description) < 50) {
            $factors[] = [
                'type' => 'short_description',
                'severity' => 'medium',
                'description' => 'Слишком короткое описание'
            ];
        }
        
        if (empty($userInfo['email_verified_at'])) {
            $factors[] = [
                'type' => 'unverified_email',
                'severity' => 'high',
                'description' => 'Неподтвержденный email'
            ];
        }
        
        if (count($master->services ?? []) > 10) {
            $factors[] = [
                'type' => 'too_many_services',
                'severity' => 'low',
                'description' => 'Слишком много услуг'
            ];
        }
        
        if (!empty($master->pricing) && min($master->pricing) < 500) {
            $factors[] = [
                'type' => 'suspicious_pricing',
                'severity' => 'medium',
                'description' => 'Подозрительно низкие цены'
            ];
        }
        
        return $factors;
    }
}