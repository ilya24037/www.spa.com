<?php

namespace App\Domain\Review\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания нового отзыва
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class ReviewCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $reviewId;
    public int $reviewerId;
    public int $targetUserId;
    public array $reviewData;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $reviewId, int $reviewerId, int $targetUserId, array $reviewData, array $metadata = [])
    {
        $this->reviewId = $reviewId;
        $this->reviewerId = $reviewerId;
        $this->targetUserId = $targetUserId;
        $this->reviewData = $reviewData;
        $this->metadata = $metadata;
    }

    /**
     * Получить данные события для логирования
     */
    public function toArray(): array
    {
        return [
            'review_id' => $this->reviewId,
            'reviewer_id' => $this->reviewerId,
            'target_user_id' => $this->targetUserId,
            'rating' => $this->reviewData['rating'] ?? null,
            'has_comment' => !empty($this->reviewData['comment']),
            'is_anonymous' => $this->reviewData['is_anonymous'] ?? false,
            'booking_id' => $this->reviewData['booking_id'] ?? null,
            'metadata' => $this->metadata,
            'event' => 'ReviewCreated',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить каналы для broadcasting (если нужно)
     */
    public function broadcastOn()
    {
        return [
            "user.{$this->targetUserId}.reviews",
            "user.{$this->reviewerId}.activity"
        ];
    }

    /**
     * Название события для broadcasting
     */
    public function broadcastAs(): string
    {
        return 'review.created';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'review_id' => $this->reviewId,
            'target_user_id' => $this->targetUserId,
            'rating' => $this->reviewData['rating'] ?? 0,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Получить рейтинг отзыва
     */
    public function getRating(): int
    {
        return $this->reviewData['rating'] ?? 0;
    }

    /**
     * Проверить является ли отзыв анонимным
     */
    public function isAnonymous(): bool
    {
        return $this->reviewData['is_anonymous'] ?? false;
    }

    /**
     * Проверить есть ли комментарий в отзыве
     */
    public function hasComment(): bool
    {
        return !empty($this->reviewData['comment']);
    }

    /**
     * Получить ID бронирования (если есть)
     */
    public function getBookingId(): ?int
    {
        return $this->reviewData['booking_id'] ?? null;
    }
}