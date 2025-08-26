<?php

namespace App\Domain\Review\Services;

use App\Domain\Review\Models\Review;
use App\Domain\User\Models\User;
use App\Domain\Review\Repositories\ReviewRepository;
use App\Infrastructure\Notification\NotificationService;
use App\Enums\NotificationType;
use App\Enums\ReviewStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

/**
 * Обработчик модерации отзывов
 */
class ReviewModerationHandler
{
    public function __construct(
        private ReviewRepository $repository,
        private NotificationService $notificationService
    ) {}

    /**
     * Одобрить отзыв
     */
    public function approve(int $reviewId, User $moderator): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->approve($moderator);

        // Уведомляем автора
        $this->sendApprovalNotification($review);

        Log::info('Review approved', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
        ]);

        return $review;
    }

    /**
     * Отклонить отзыв
     */
    public function reject(int $reviewId, User $moderator, ?string $reason = null): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        $review->reject($moderator, $reason);

        // Уведомляем автора
        $this->sendRejectionNotification($review, $reason);

        Log::info('Review rejected', [
            'review_id' => $reviewId,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Пожаловаться на отзыв
     */
    public function flag(int $reviewId, User $flagger, string $reason): Review
    {
        $review = $this->repository->findOrFail($reviewId);
        
        if ($review->user_id === $flagger->id) {
            throw new \InvalidArgumentException('You cannot flag your own review');
        }

        $review->flag($flagger, $reason);

        $this->sendFlagNotification($review, $flagger, $reason);

        Log::info('Review flagged', [
            'review_id' => $reviewId,
            'flagger_id' => $flagger->id,
            'reason' => $reason,
        ]);

        return $review;
    }

    /**
     * Массовое одобрение отзывов
     */
    public function batchApprove(array $ids, User $moderator): int
    {
        $count = $this->repository->batchApprove($ids);

        // Отправляем уведомления для каждого одобренного отзыва
        $reviews = $this->repository->findMany($ids);
        foreach ($reviews as $review) {
            $this->sendApprovalNotification($review);
        }

        Log::info('Batch approve reviews', [
            'count' => $count,
            'moderator_id' => $moderator->id,
            'review_ids' => $ids,
        ]);

        return $count;
    }

    /**
     * Массовое отклонение отзывов
     */
    public function batchReject(array $ids, User $moderator, ?string $reason = null): int
    {
        $count = $this->repository->batchReject($ids, $reason);

        // Отправляем уведомления для каждого отклоненного отзыва
        $reviews = $this->repository->findMany($ids);
        foreach ($reviews as $review) {
            $this->sendRejectionNotification($review, $reason);
        }

        Log::info('Batch reject reviews', [
            'count' => $count,
            'moderator_id' => $moderator->id,
            'reason' => $reason,
            'review_ids' => $ids,
        ]);

        return $count;
    }

    /**
     * Получить отзывы на модерации
     */
    public function getPendingModeration(int $limit = 50): Collection
    {
        return $this->repository->getPendingModeration($limit);
    }

    /**
     * Получить отзывы по жалобам
     */
    public function getFlagged(int $limit = 50): Collection
    {
        return $this->repository->getFlagged($limit);
    }

    /**
     * Получить статистику модерации
     */
    public function getModerationStats(int $days = 30): array
    {
        return [
            'pending_count' => $this->repository->countByStatus(ReviewStatus::PENDING),
            'flagged_count' => $this->repository->countFlagged(),
            'approved_today' => $this->repository->countApprovedToday(),
            'rejected_today' => $this->repository->countRejectedToday(),
            'moderation_queue_age' => $this->repository->getOldestPendingAge(),
        ];
    }

    /**
     * Автоматическая модерация на основе правил
     */
    public function autoModerate(): array
    {
        $results = [
            'auto_approved' => 0,
            'auto_rejected' => 0,
            'flagged_for_review' => 0,
        ];

        // Получаем отзывы на автомодерации
        $pendingReviews = $this->repository->getPendingAutoModeration(100);

        foreach ($pendingReviews as $review) {
            $action = $this->determineAutoModerationAction($review);

            switch ($action) {
                case 'approve':
                    $review->update(['status' => ReviewStatus::APPROVED]);
                    $results['auto_approved']++;
                    break;
                
                case 'reject':
                    $review->update(['status' => ReviewStatus::REJECTED]);
                    $results['auto_rejected']++;
                    break;
                
                case 'flag':
                    $review->update(['requires_manual_review' => true]);
                    $results['flagged_for_review']++;
                    break;
            }
        }

        Log::info('Auto moderation completed', $results);
        return $results;
    }

    /**
     * Отправить уведомление об одобрении
     */
    private function sendApprovalNotification(Review $review): void
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
    private function sendRejectionNotification(Review $review, ?string $reason): void
    {
        try {
            $this->notificationService->create(
                \App\DTOs\Notification\CreateNotificationDTO::forUser(
                    $review->user_id,
                    NotificationType::REVIEW_RECEIVED,
                    'Отзыв отклонен',
                    'Ваш отзыв не прошел модерацию. Причина: ' . ($reason ?: 'Не указана'),
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
     * Отправить уведомление о жалобе
     */
    private function sendFlagNotification(Review $review, User $flagger, string $reason): void
    {
        try {
            // Уведомляем модераторов о новой жалобе
            $this->notificationService->notifyModerators(
                'Новая жалоба на отзыв',
                "Пользователь пожаловался на отзыв. Причина: {$reason}",
                [
                    'review_id' => $review->id,
                    'flagger_id' => $flagger->id,
                    'reason' => $reason,
                    'action_url' => route('admin.reviews.flagged'),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send flag notification', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Определить действие автоматической модерации
     */
    private function determineAutoModerationAction(Review $review): string
    {
        // Правила автоматической модерации
        
        // Спам-фильтр
        if ($this->isSpam($review)) {
            return 'reject';
        }

        // Проверка на токсичность
        if ($this->isToxic($review)) {
            return 'flag';
        }

        // Короткие положительные отзывы с высоким рейтингом
        if ($review->rating?->value >= 4 && 
            mb_strlen($review->comment ?? '') < 50 && 
            !$this->containsOffensiveWords($review)) {
            return 'approve';
        }

        // Отзывы от верифицированных покупок
        if ($review->is_verified && $review->rating?->value >= 3) {
            return 'approve';
        }

        // По умолчанию - требует ручной проверки
        return 'flag';
    }

    /**
     * Проверка на спам
     */
    private function isSpam(Review $review): bool
    {
        $spamPhrases = [
            'купить дешево',
            'скидка до 90%',
            'жми сюда',
            'переходи по ссылке',
        ];

        $text = mb_strtolower($review->comment ?? '');
        
        foreach ($spamPhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка на токсичность
     */
    private function isToxic(Review $review): bool
    {
        $offensiveWords = [
            'дурак', 'идиот', 'мошенник', 'обман', 'развод'
        ];

        $text = mb_strtolower($review->comment ?? '');
        
        foreach ($offensiveWords as $word) {
            if (str_contains($text, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверка на оскорбительные слова
     */
    private function containsOffensiveWords(Review $review): bool
    {
        return $this->isToxic($review);
    }
}