<?php

namespace App\Domain\Review\Services\Moderation;

use App\Domain\Review\Models\Review;
use App\Enums\ReviewStatus;
use Illuminate\Support\Facades\Log;

/**
 * Движок автоматической модерации отзывов
 */
class AutoModerationEngine
{
    private ContentAnalyzer $contentAnalyzer;
    private UserReputationService $reputationService;

    public function __construct(
        ContentAnalyzer $contentAnalyzer,
        UserReputationService $reputationService
    ) {
        $this->contentAnalyzer = $contentAnalyzer;
        $this->reputationService = $reputationService;
    }

    /**
     * Автоматическая модерация отзыва
     */
    public function autoModerate(Review $review): array
    {
        $checks = [
            'spam_score' => $this->contentAnalyzer->calculateSpamScore($review),
            'has_banned_words' => $this->contentAnalyzer->hasBannedWords($review),
            'has_suspicious_patterns' => $this->contentAnalyzer->hasSuspiciousPatterns($review),
            'is_duplicate' => $this->contentAnalyzer->isDuplicate($review),
            'user_reputation' => $this->reputationService->checkUserReputation($review->user),
        ];

        $autoAction = $this->determineAutoAction($checks);

        if ($autoAction !== 'manual') {
            $this->executeAutoAction($review, $autoAction, $checks);
        }

        Log::info('Auto moderation completed', [
            'review_id' => $review->id,
            'checks' => $checks,
            'auto_action' => $autoAction,
        ]);

        return [
            'action' => $autoAction,
            'checks' => $checks,
            'confidence' => $this->calculateConfidence($checks),
            'reasons' => $this->generateAutoModerationReason($checks),
        ];
    }

    /**
     * Определить автоматическое действие
     */
    private function determineAutoAction(array $checks): string
    {
        // Автоматическое отклонение
        if (
            $checks['spam_score'] > 70 ||
            $checks['has_banned_words'] ||
            $checks['is_duplicate'] ||
            $checks['user_reputation']['is_suspicious']
        ) {
            return 'reject';
        }

        // Автоматическое одобрение для доверенных пользователей
        if (
            $checks['spam_score'] < 20 &&
            !$checks['has_suspicious_patterns'] &&
            $checks['user_reputation']['is_trusted']
        ) {
            return 'approve';
        }

        // Автоматическое помещение в спам
        if ($checks['spam_score'] > 85) {
            return 'spam';
        }

        // Требует ручной модерации
        return 'manual';
    }

    /**
     * Выполнить автоматическое действие
     */
    private function executeAutoAction(Review $review, string $action, array $checks): void
    {
        $reason = $this->generateAutoModerationReason($checks);

        switch ($action) {
            case 'approve':
                $review->update([
                    'status' => ReviewStatus::APPROVED,
                    'moderated_at' => now(),
                    'moderation_notes' => 'Автоматически одобрено: ' . $reason,
                ]);
                break;

            case 'reject':
                $review->update([
                    'status' => ReviewStatus::REJECTED,
                    'moderated_at' => now(),
                    'moderation_notes' => 'Автоматически отклонено: ' . $reason,
                ]);
                break;

            case 'spam':
                $review->update([
                    'status' => ReviewStatus::SPAM,
                    'moderated_at' => now(),
                    'moderation_notes' => 'Автоматически помечено как спам: ' . $reason,
                ]);
                $this->reputationService->incrementUserSpamCount($review->user);
                break;
        }
    }

    /**
     * Сгенерировать причину автомодерации
     */
    private function generateAutoModerationReason(array $checks): string
    {
        $reasons = [];

        if ($checks['spam_score'] > 50) {
            $reasons[] = 'высокий спам-скор (' . $checks['spam_score'] . ')';
        }

        if ($checks['has_banned_words']) {
            $reasons[] = 'запрещенные слова';
        }

        if ($checks['has_suspicious_patterns']) {
            $reasons[] = 'подозрительный контент';
        }

        if ($checks['is_duplicate']) {
            $reasons[] = 'дублирование';
        }

        if ($checks['user_reputation']['is_trusted']) {
            $reasons[] = 'доверенный пользователь';
        }

        if ($checks['user_reputation']['is_suspicious']) {
            $reasons[] = 'подозрительный пользователь';
        }

        return implode(', ', $reasons);
    }

    /**
     * Рассчитать уверенность в решении
     */
    private function calculateConfidence(array $checks): int
    {
        $confidence = 50; // Базовая уверенность

        if ($checks['user_reputation']['is_trusted']) {
            $confidence += 20;
        }

        if ($checks['spam_score'] > 70) {
            $confidence += 30;
        }

        if ($checks['has_banned_words']) {
            $confidence += 25;
        }

        if ($checks['is_duplicate']) {
            $confidence += 35;
        }

        if ($checks['user_reputation']['is_suspicious']) {
            $confidence += 25;
        }

        return min(100, $confidence);
    }

    /**
     * Получить статистику автомодерации
     */
    public function getAutoModerationStats(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $autoApproved = Review::where('status', ReviewStatus::APPROVED)
            ->whereNull('moderated_by')
            ->where('created_at', '>=', $startDate)
            ->count();

        $autoRejected = Review::where('status', ReviewStatus::REJECTED)
            ->whereNull('moderated_by')
            ->where('created_at', '>=', $startDate)
            ->count();

        $autoSpam = Review::where('status', ReviewStatus::SPAM)
            ->whereNull('moderated_by')
            ->where('created_at', '>=', $startDate)
            ->count();

        $total = $autoApproved + $autoRejected + $autoSpam;

        return [
            'auto_approved' => $autoApproved,
            'auto_rejected' => $autoRejected,
            'auto_spam' => $autoSpam,
            'total_auto_moderated' => $total,
            'auto_moderation_rate' => $total > 0 ? round(($total / ($total + $this->getManualModerationCount($days))) * 100, 2) : 0,
        ];
    }

    /**
     * Получить количество ручных модераций
     */
    private function getManualModerationCount(int $days): int
    {
        $startDate = now()->subDays($days);

        return Review::whereIn('status', [ReviewStatus::APPROVED, ReviewStatus::REJECTED, ReviewStatus::SPAM])
            ->whereNotNull('moderated_by')
            ->where('moderated_at', '>=', $startDate)
            ->count();
    }
}