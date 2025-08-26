<?php

namespace App\Application\Services\Query;

/**
 * Сервис оценки и скоринга мастеров
 */
class MasterScoringService
{
    /**
     * Рассчитать процент заполнения профиля
     */
    public function calculateProfileCompletion(object $master, ?array $userInfo): int
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
     * Рассчитать оценку профиля
     */
    public function calculateProfileScore(object $master, ?array $userInfo): int
    {
        $completion = $this->calculateProfileCompletion($master, $userInfo);
        $rating = ($master->rating ?? 0) * 20;
        $reviews = min(($master->reviews_count ?? 0) * 2, 20);
        
        return min($completion + $rating + $reviews, 100);
    }

    /**
     * Рассчитать уровень доверия
     */
    public function calculateTrustLevel(object $master, ?array $userInfo): string
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
     * Рассчитать оценку риска
     */
    public function calculateRiskScore(object $master, ?array $userInfo): int
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
}