<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\Review\Models\ReviewReply;
use App\Domain\User\Models\User;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Infrastructure\Notification\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис модерации отзывов
 */
class ReviewModerationService
{
    protected ReviewRepository $repository;
    protected NotificationService $notificationService;

    // Списки запрещенных слов (можно вынести в конфиг)
    protected array $bannedWords = [
        'спам', 'мошенник', 'развод', 'обман', 'кидала',
        // Добавить другие запрещенные слова
    ];

    protected array $suspiciousPatterns = [
        '/\b\d{10,}\b/', // Телефонные номера
        '/https?:\/\/[^\s]+/', // Ссылки
        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', // Email
    ];

    public function __construct(
        ReviewRepository $repository,
        NotificationService $notificationService
    ) {
        $this->repository = $repository;
        $this->notificationService = $notificationService;
    }

    /**
     * Автоматическая модерация отзыва
     */
    public function autoModerate(Review $review): array
    {
        $checks = [
            'spam_score' => $this->calculateSpamScore($review),
            'has_banned_words' => $this->hasBannedWords($review),
            'has_suspicious_patterns' => $this->hasSuspiciousPatterns($review),
            'is_duplicate' => $this->isDuplicate($review),
            'user_reputation' => $this->checkUserReputation($review->user),
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
        ];
    }

    /**
     * Одобрить отзыв
     */
    public function approve(int $reviewId, User $moderator, ?string $notes = null): Review
    {
        try {
            DB::beginTransaction();

            $review = $this->repository->findOrFail($reviewId);
            
            $review->update([
                'status' => ReviewStatus::APPROVED,
                'moderated_at' => now(),
                'moderated_by' => $moderator->id,
                'moderation_notes' => $notes,
            ]);

            // Уведомляем автора
            $this->sendApprovalNotification($review);

            DB::commit();

            Log::info('Review approved', [
                'review_id' => $reviewId,
                'moderator_id' => $moderator->id,
                'notes' => $notes,
            ]);

            return $review;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Отклонить отзыв
     */
    public function reject(int $reviewId, User $moderator, string $reason): Review
    {
        try {
            DB::beginTransaction();

            $review = $this->repository->findOrFail($reviewId);
            
            $review->update([
                'status' => ReviewStatus::REJECTED,
                'moderated_at' => now(),
                'moderated_by' => $moderator->id,
                'moderation_notes' => $reason,
            ]);

            // Уведомляем автора
            $this->sendRejectionNotification($review, $reason);

            DB::commit();

            Log::info('Review rejected', [
                'review_id' => $reviewId,
                'moderator_id' => $moderator->id,
                'reason' => $reason,
            ]);

            return $review;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Пометить отзыв как спам
     */
    public function markAsSpam(int $reviewId, User $moderator, ?string $reason = null): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->update([
            'status' => ReviewStatus::SPAM,
            'moderated_at' => now(),
            'moderated_by' => $moderator->id,
            'moderation_notes' => $reason ?: 'Помечено как спам',
        ]);

        // Увеличиваем счетчик спама у пользователя
        $this->incrementUserSpamCount($review->user);

        Log::info('Review marked as spam', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Массовое одобрение отзывов
     */
    public function batchApprove(array $reviewIds, User $moderator): array
    {
        $results = ['approved' => 0, 'failed' => 0];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->approve($reviewId, $moderator, 'Массовое одобрение');
                $results['approved']++;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to approve review in batch', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch approval completed', [
            'moderator_id' => $moderator->id,
            'results' => $results,
        ]);

        return $results;
    }

    /**
     * Массовое отклонение отзывов
     */
    public function batchReject(array $reviewIds, User $moderator, string $reason): array
    {
        $results = ['rejected' => 0, 'failed' => 0];

        foreach ($reviewIds as $reviewId) {
            try {
                $this->reject($reviewId, $moderator, $reason);
                $results['rejected']++;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to reject review in batch', [
                    'review_id' => $reviewId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch rejection completed', [
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'results' => $results,
        ]);

        return $results;
    }

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

        return [
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
    }

    // ============ HELPER METHODS ============

    /**
     * Рассчитать спам-скор отзыва
     */
    protected function calculateSpamScore(Review $review): int
    {
        $score = 0;

        // Проверка длины комментария
        $commentLength = strlen($review->comment);
        if ($commentLength < 10) {
            $score += 30;
        } elseif ($commentLength > 1000) {
            $score += 20;
        }

        // Проверка на повторяющиеся символы
        if (preg_match('/(.)\1{5,}/', $review->comment)) {
            $score += 25;
        }

        // Проверка на капс
        $capsRatio = strlen(preg_replace('/[^A-ZА-Я]/', '', $review->comment)) / max(1, $commentLength);
        if ($capsRatio > 0.5) {
            $score += 20;
        }

        // Проверка на множественные восклицательные знаки
        if (substr_count($review->comment, '!') > 5) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Проверить наличие запрещенных слов
     */
    protected function hasBannedWords(Review $review): bool
    {
        $text = strtolower($review->comment . ' ' . $review->title);
        
        foreach ($this->bannedWords as $word) {
            if (strpos($text, strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить подозрительные паттерны
     */
    protected function hasSuspiciousPatterns(Review $review): bool
    {
        $text = $review->comment . ' ' . $review->title;
        
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить дубликат
     */
    protected function isDuplicate(Review $review): bool
    {
        return Review::where('user_id', $review->user_id)
            ->where('reviewable_type', $review->reviewable_type)
            ->where('reviewable_id', $review->reviewable_id)
            ->where('id', '!=', $review->id)
            ->where('comment', $review->comment)
            ->exists();
    }

    /**
     * Проверить репутацию пользователя
     */
    protected function checkUserReputation(User $user): array
    {
        $totalReviews = Review::where('user_id', $user->id)->count();
        $spamReviews = Review::where('user_id', $user->id)
            ->where('status', ReviewStatus::SPAM)->count();
        $rejectedReviews = Review::where('user_id', $user->id)
            ->where('status', ReviewStatus::REJECTED)->count();

        $spamRatio = $totalReviews > 0 ? $spamReviews / $totalReviews : 0;
        $rejectedRatio = $totalReviews > 0 ? $rejectedReviews / $totalReviews : 0;

        return [
            'total_reviews' => $totalReviews,
            'spam_ratio' => $spamRatio,
            'rejected_ratio' => $rejectedRatio,
            'is_trusted' => $totalReviews >= 5 && $spamRatio < 0.1 && $rejectedRatio < 0.2,
            'is_suspicious' => $spamRatio > 0.3 || $rejectedRatio > 0.5,
        ];
    }

    /**
     * Определить автоматическое действие
     */
    protected function determineAutoAction(array $checks): string
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

        // Требует ручной модерации
        return 'manual';
    }

    /**
     * Выполнить автоматическое действие
     */
    protected function executeAutoAction(Review $review, string $action, array $checks): void
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
        }
    }

    /**
     * Сгенерировать причину автомодерации
     */
    protected function generateAutoModerationReason(array $checks): string
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

        return implode(', ', $reasons);
    }

    /**
     * Рассчитать уверенность в решении
     */
    protected function calculateConfidence(array $checks): int
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

        return min(100, $confidence);
    }

    /**
     * Отправить уведомление об одобрении
     */
    protected function sendApprovalNotification(Review $review): void
    {
        try {
            $this->notificationService->create(
                \App\DTOs\Notification\CreateNotificationDTO::forUser(
                    $review->user_id,
                    NotificationType::REVIEW_RECEIVED,
                    'Отзыв одобрен',
                    'Ваш отзыв прошел модерацию и опубликован',
                    [
                        'review_id' => $review->id,
                        'action_url' => route('reviews.show', $review->id),
                    ]
                )
            );
        } catch (\Exception $e) {
            Log::error('Failed to send approval notification', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление об отклонении
     */
    protected function sendRejectionNotification(Review $review, string $reason): void
    {
        try {
            $this->notificationService->create(
                \App\DTOs\Notification\CreateNotificationDTO::forUser(
                    $review->user_id,
                    NotificationType::REVIEW_RECEIVED,
                    'Отзыв отклонен',
                    'Ваш отзыв не прошел модерацию. Причина: ' . $reason,
                    [
                        'review_id' => $review->id,
                        'reason' => $reason,
                    ]
                )
            );
        } catch (\Exception $e) {
            Log::error('Failed to send rejection notification', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Увеличить счетчик спама пользователя
     */
    protected function incrementUserSpamCount(User $user): void
    {
        // Если у пользователя есть поле spam_count
        if ($user->hasAttribute('spam_count')) {
            $user->increment('spam_count');
        }
    }
}