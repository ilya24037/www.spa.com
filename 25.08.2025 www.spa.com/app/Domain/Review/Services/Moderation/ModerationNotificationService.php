<?php

namespace App\Domain\Review\Services\Moderation;

use App\Domain\Review\Models\Review;
use App\Infrastructure\Notification\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;

/**
 * Сервис уведомлений для системы модерации отзывов
 */
class ModerationNotificationService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Отправить уведомление об одобрении
     */
    public function sendApprovalNotification(Review $review): void
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
                        'moderated_at' => $review->moderated_at?->format('Y-m-d H:i:s'),
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
    public function sendRejectionNotification(Review $review, string $reason): void
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
                        'moderated_at' => $review->moderated_at?->format('Y-m-d H:i:s'),
                        'appeal_url' => route('reviews.appeal', $review->id),
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
     * Отправить уведомление о помещении в спам
     */
    public function sendSpamNotification(Review $review, ?string $reason = null): void
    {
        try {
            $this->notificationService->create(
                \App\DTOs\Notification\CreateNotificationDTO::forUser(
                    $review->user_id,
                    NotificationType::REVIEW_RECEIVED,
                    'Отзыв помечен как спам',
                    'Ваш отзыв был помечен как спам и удален. ' . ($reason ?: 'Обратитесь к модераторам за разъяснениями.'),
                    [
                        'review_id' => $review->id,
                        'reason' => $reason,
                        'moderated_at' => $review->moderated_at?->format('Y-m-d H:i:s'),
                        'support_url' => route('support.contact'),
                    ]
                )
            );
        } catch (\Exception $e) {
            Log::error('Failed to send spam notification', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление модераторам о новом отзыве на проверку
     */
    public function notifyModeratorsAboutNewReview(Review $review): void
    {
        try {
            // Получить список модераторов (логика зависит от структуры ролей)
            $moderators = $this->getModerators();

            foreach ($moderators as $moderator) {
                $this->notificationService->create(
                    \App\DTOs\Notification\CreateNotificationDTO::forUser(
                        $moderator->id,
                        NotificationType::MODERATION_REQUIRED,
                        'Новый отзыв на модерации',
                        'Поступил новый отзыв, требующий проверки',
                        [
                            'review_id' => $review->id,
                            'author' => $review->user->name ?? 'Аноним',
                            'moderation_url' => route('admin.reviews.moderate', $review->id),
                            'created_at' => $review->created_at->format('Y-m-d H:i:s'),
                        ]
                    )
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify moderators about new review', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление о жалобе на отзыв
     */
    public function notifyModeratorsAboutFlag(Review $review, string $flagReason): void
    {
        try {
            $moderators = $this->getModerators();

            foreach ($moderators as $moderator) {
                $this->notificationService->create(
                    \App\DTOs\Notification\CreateNotificationDTO::forUser(
                        $moderator->id,
                        NotificationType::REVIEW_FLAGGED,
                        'Жалоба на отзыв',
                        'Поступила жалоба на отзыв: ' . $flagReason,
                        [
                            'review_id' => $review->id,
                            'flag_reason' => $flagReason,
                            'author' => $review->user->name ?? 'Аноним',
                            'moderation_url' => route('admin.reviews.moderate', $review->id),
                            'flagged_at' => now()->format('Y-m-d H:i:s'),
                        ]
                    )
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify moderators about flag', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить сводку по модерации модераторам
     */
    public function sendModerationSummary(array $stats): void
    {
        try {
            $moderators = $this->getModerators();

            foreach ($moderators as $moderator) {
                $this->notificationService->create(
                    \App\DTOs\Notification\CreateNotificationDTO::forUser(
                        $moderator->id,
                        NotificationType::MODERATION_SUMMARY,
                        'Сводка по модерации',
                        $this->buildSummaryMessage($stats),
                        [
                            'stats' => $stats,
                            'dashboard_url' => route('admin.moderation.dashboard'),
                        ]
                    )
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send moderation summary', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Построить сообщение сводки
     */
    private function buildSummaryMessage(array $stats): string
    {
        return sprintf(
            'На модерации: %d отзывов, С жалобами: %d, Обработано за день: %d',
            $stats['pending_count'] ?? 0,
            $stats['flagged_count'] ?? 0,
            ($stats['approved_count'] ?? 0) + ($stats['rejected_count'] ?? 0) + ($stats['spam_count'] ?? 0)
        );
    }

    /**
     * Получить список модераторов
     * TODO: Реализовать в зависимости от системы ролей
     */
    private function getModerators(): array
    {
        // Примерная реализация - адаптировать под конкретную систему ролей
        return \App\Domain\User\Models\User::where('role', 'moderator')
            ->orWhere('role', 'admin')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}