<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\User\Models\User;
use App\Enums\ReviewStatus;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Domain\Review\Services\Moderation\AutoModerationEngine;
use App\Domain\Review\Services\Moderation\ModerationActionsService;
use App\Domain\Review\Services\Moderation\ModerationNotificationService;
use App\Domain\Review\Services\Moderation\ContentAnalyzer;
use App\Domain\Review\Services\Moderation\UserReputationService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Упрощенный сервис модерации отзывов
 * Делегирует работу специализированным сервисам модерации
 */
class ReviewModerationService
{
    private ReviewRepository $repository;
    private AutoModerationEngine $autoModerationEngine;
    private ModerationActionsService $actionsService;
    private ModerationNotificationService $notificationService;
    private ContentAnalyzer $contentAnalyzer;
    private UserReputationService $reputationService;

    public function __construct(
        ReviewRepository $repository,
        AutoModerationEngine $autoModerationEngine,
        ModerationActionsService $actionsService,
        ModerationNotificationService $notificationService,
        ContentAnalyzer $contentAnalyzer,
        UserReputationService $reputationService
    ) {
        $this->repository = $repository;
        $this->autoModerationEngine = $autoModerationEngine;
        $this->actionsService = $actionsService;
        $this->notificationService = $notificationService;
        $this->contentAnalyzer = $contentAnalyzer;
        $this->reputationService = $reputationService;
    }

    // === АВТОМАТИЧЕСКАЯ МОДЕРАЦИЯ ===

    /**
     * Автоматическая модерация отзыва
     */
    public function autoModerate(Review $review): array
    {
        $result = $this->autoModerationEngine->autoModerate($review);
        
        // Отправляем уведомления если нужно
        if ($result['action'] === 'approve') {
            $this->notificationService->sendApprovalNotification($review);
        } elseif ($result['action'] === 'reject') {
            $this->notificationService->sendRejectionNotification($review, $result['reasons']);
        } elseif ($result['action'] === 'spam') {
            $this->notificationService->sendSpamNotification($review, $result['reasons']);
        }

        return $result;
    }

    // === РУЧНЫЕ ДЕЙСТВИЯ МОДЕРАТОРА ===

    /**
     * Одобрить отзыв
     */
    public function approve(int $reviewId, User $moderator, ?string $notes = null): Review
    {
        $review = $this->actionsService->approve($reviewId, $moderator, $notes);
        $this->notificationService->sendApprovalNotification($review);
        return $review;
    }

    /**
     * Отклонить отзыв
     */
    public function reject(int $reviewId, User $moderator, string $reason): Review
    {
        $review = $this->actionsService->reject($reviewId, $moderator, $reason);
        $this->notificationService->sendRejectionNotification($review, $reason);
        return $review;
    }

    /**
     * Пометить отзыв как спам
     */
    public function markAsSpam(int $reviewId, User $moderator, ?string $reason = null): Review
    {
        $review = $this->actionsService->markAsSpam($reviewId, $moderator, $reason);
        $this->notificationService->sendSpamNotification($review, $reason);
        return $review;
    }

    // === МАССОВЫЕ ОПЕРАЦИИ ===

    /**
     * Массовое одобрение отзывов
     */
    public function batchApprove(array $reviewIds, User $moderator): array
    {
        return $this->actionsService->batchApprove($reviewIds, $moderator);
    }

    /**
     * Массовое отклонение отзывов
     */
    public function batchReject(array $reviewIds, User $moderator, string $reason): array
    {
        return $this->actionsService->batchReject($reviewIds, $moderator, $reason);
    }

    /**
     * Массовое помечение как спам
     */
    public function batchMarkAsSpam(array $reviewIds, User $moderator, string $reason = 'Массовое помечение как спам'): array
    {
        return $this->actionsService->batchMarkAsSpam($reviewIds, $moderator, $reason);
    }

    // === ПОЛУЧЕНИЕ ДАННЫХ ===

    /**
     * Получить отзывы на модерации
     */
    public function getPendingReviews(int $limit = 50): Collection
    {
        return Review::where('status', ReviewStatus::PENDING)
            ->with(['user', 'reviewable'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить отзывы с жалобами
     */
    public function getFlaggedReviews(int $limit = 50): Collection
    {
        return Review::where('status', ReviewStatus::FLAGGED)
            ->with(['user', 'reviewable', 'flagger'])
            ->orderBy('flagged_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику модерации
     */
    public function getModerationStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $stats = [
            'pending_count' => Review::where('status', ReviewStatus::PENDING)->count(),
            'flagged_count' => Review::where('status', ReviewStatus::FLAGGED)->count(),
            'approved_count' => Review::where('status', ReviewStatus::APPROVED)
                ->where('moderated_at', '>=', $startDate)->count(),
            'rejected_count' => Review::where('status', ReviewStatus::REJECTED)
                ->where('moderated_at', '>=', $startDate)->count(),
            'spam_count' => Review::where('status', ReviewStatus::SPAM)
                ->where('moderated_at', '>=', $startDate)->count(),
            'auto_approved' => Review::where('status', ReviewStatus::APPROVED)
                ->whereNull('moderated_by')
                ->where('created_at', '>=', $startDate)->count(),
        ];

        // Добавляем статистику автомодерации
        $autoStats = $this->autoModerationEngine->getAutoModerationStats($days);
        return array_merge($stats, $autoStats);
    }

    // === АНАЛИЗ КОНТЕНТА И РЕПУТАЦИИ ===

    /**
     * Анализировать контент отзыва
     */
    public function analyzeContent(Review $review): array
    {
        return $this->contentAnalyzer->analyzeContent($review);
    }

    /**
     * Проверить репутацию пользователя
     */
    public function checkUserReputation(User $user): array
    {
        return $this->reputationService->checkUserReputation($user);
    }

    /**
     * Получить статистику модератора
     */
    public function getModeratorStats(User $moderator, int $days = 30): array
    {
        return $this->actionsService->getModeratorStats($moderator, $days);
    }

    // === УВЕДОМЛЕНИЯ ===

    /**
     * Уведомить модераторов о новом отзыве
     */
    public function notifyModeratorsAboutNewReview(Review $review): void
    {
        $this->notificationService->notifyModeratorsAboutNewReview($review);
    }

    /**
     * Уведомить модераторов о жалобе
     */
    public function notifyModeratorsAboutFlag(Review $review, string $flagReason): void
    {
        $this->notificationService->notifyModeratorsAboutFlag($review, $flagReason);
    }

    /**
     * Отправить сводку по модерации
     */
    public function sendModerationSummary(): void
    {
        $stats = $this->getModerationStats(1); // За последний день
        $this->notificationService->sendModerationSummary($stats);
    }

    // === УСТАРЕВШИЕ МЕТОДЫ ДЛЯ СОВМЕСТИМОСТИ ===

    /**
     * @deprecated Используйте analyzeContent()
     */
    protected function calculateSpamScore(Review $review): int
    {
        return $this->contentAnalyzer->calculateSpamScore($review);
    }

    /**
     * @deprecated Используйте analyzeContent()
     */
    protected function hasBannedWords(Review $review): bool
    {
        return $this->contentAnalyzer->hasBannedWords($review);
    }

    /**
     * @deprecated Используйте analyzeContent()
     */
    protected function hasSuspiciousPatterns(Review $review): bool
    {
        return $this->contentAnalyzer->hasSuspiciousPatterns($review);
    }

    /**
     * @deprecated Используйте analyzeContent()
     */
    protected function isDuplicate(Review $review): bool
    {
        return $this->contentAnalyzer->isDuplicate($review);
    }

    /**
     * @deprecated Используйте checkUserReputation()
     */
    protected function checkUserReputation(User $user): array
    {
        return $this->reputationService->checkUserReputation($user);
    }
}