<?php

namespace App\Domain\Review\Services\Moderation;

use App\Domain\Review\Models\Review;
use App\Domain\User\Models\User;
use App\Enums\ReviewStatus;

/**
 * Сервис для работы с репутацией пользователей в контексте отзывов
 */
class UserReputationService
{
    /**
     * Проверить репутацию пользователя
     */
    public function checkUserReputation(User $user): array
    {
        $totalReviews = Review::where('user_id', $user->id)->count();
        $spamReviews = Review::where('user_id', $user->id)
            ->where('status', ReviewStatus::SPAM)->count();
        $rejectedReviews = Review::where('user_id', $user->id)
            ->where('status', ReviewStatus::REJECTED)->count();
        $approvedReviews = Review::where('user_id', $user->id)
            ->where('status', ReviewStatus::APPROVED)->count();

        $spamRatio = $totalReviews > 0 ? $spamReviews / $totalReviews : 0;
        $rejectedRatio = $totalReviews > 0 ? $rejectedReviews / $totalReviews : 0;
        $approvalRatio = $totalReviews > 0 ? $approvedReviews / $totalReviews : 0;

        return [
            'total_reviews' => $totalReviews,
            'approved_reviews' => $approvedReviews,
            'spam_reviews' => $spamReviews,
            'rejected_reviews' => $rejectedReviews,
            'spam_ratio' => round($spamRatio, 3),
            'rejected_ratio' => round($rejectedRatio, 3),
            'approval_ratio' => round($approvalRatio, 3),
            'is_trusted' => $this->isTrustedUser($totalReviews, $spamRatio, $rejectedRatio),
            'is_suspicious' => $this->isSuspiciousUser($spamRatio, $rejectedRatio),
            'reputation_score' => $this->calculateReputationScore($totalReviews, $spamRatio, $rejectedRatio, $approvalRatio),
        ];
    }

    /**
     * Увеличить счетчик спама пользователя
     */
    public function incrementUserSpamCount(User $user): void
    {
        // Если у пользователя есть поле spam_count
        if ($user->hasAttribute('spam_count')) {
            $user->increment('spam_count');
        }
    }

    /**
     * Получить пользователей с подозрительной активностью
     */
    public function getSuspiciousUsers(int $limit = 20): array
    {
        return Review::select('user_id')
            ->selectRaw('COUNT(*) as total_reviews')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as spam_count', [ReviewStatus::SPAM])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected_count', [ReviewStatus::REJECTED])
            ->groupBy('user_id')
            ->having('total_reviews', '>=', 3)
            ->havingRaw('(spam_count + rejected_count) / total_reviews > 0.5')
            ->with('user:id,name,email,created_at')
            ->orderByDesc('spam_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Получить топ доверенных пользователей
     */
    public function getTrustedUsers(int $limit = 20): array
    {
        return Review::select('user_id')
            ->selectRaw('COUNT(*) as total_reviews')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_count', [ReviewStatus::APPROVED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as spam_count', [ReviewStatus::SPAM])
            ->groupBy('user_id')
            ->having('total_reviews', '>=', 5)
            ->havingRaw('approved_count / total_reviews > 0.8')
            ->havingRaw('spam_count / total_reviews < 0.1')
            ->with('user:id,name,email,created_at')
            ->orderByDesc('total_reviews')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Рассчитать репутационный скор пользователя
     */
    private function calculateReputationScore(
        int $totalReviews, 
        float $spamRatio, 
        float $rejectedRatio, 
        float $approvalRatio
    ): int {
        $score = 50; // Базовый скор

        // Бонусы за активность
        if ($totalReviews >= 10) $score += 10;
        if ($totalReviews >= 50) $score += 15;
        if ($totalReviews >= 100) $score += 20;

        // Бонусы за одобренные отзывы
        $score += $approvalRatio * 30;

        // Штрафы за спам и отклонения
        $score -= $spamRatio * 40;
        $score -= $rejectedRatio * 30;

        return max(0, min(100, round($score)));
    }

    /**
     * Проверить является ли пользователь доверенным
     */
    private function isTrustedUser(int $totalReviews, float $spamRatio, float $rejectedRatio): bool
    {
        return $totalReviews >= 5 && $spamRatio < 0.1 && $rejectedRatio < 0.2;
    }

    /**
     * Проверить является ли пользователь подозрительным
     */
    private function isSuspiciousUser(float $spamRatio, float $rejectedRatio): bool
    {
        return $spamRatio > 0.3 || $rejectedRatio > 0.5;
    }

    /**
     * Получить рекомендацию по автомодерации для пользователя
     */
    public function getModerationRecommendation(User $user): string
    {
        $reputation = $this->checkUserReputation($user);

        if ($reputation['is_trusted']) {
            return 'auto_approve';
        }

        if ($reputation['is_suspicious']) {
            return 'auto_reject';
        }

        if ($reputation['reputation_score'] > 70) {
            return 'quick_approve';
        }

        if ($reputation['reputation_score'] < 30) {
            return 'careful_review';
        }

        return 'standard_review';
    }
}