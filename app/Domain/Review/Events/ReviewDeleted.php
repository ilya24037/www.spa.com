<?php

namespace App\Domain\Review\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие удаления отзыва
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class ReviewDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $reviewId;
    public int $reviewerId;
    public int $targetUserId;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $reviewId, int $reviewerId, int $targetUserId, array $metadata = [])
    {
        $this->reviewId = $reviewId;
        $this->reviewerId = $reviewerId;
        $this->targetUserId = $targetUserId;
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
            'metadata' => $this->metadata,
            'event' => 'ReviewDeleted',
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
            "review.{$this->reviewId}.updates"
        ];
    }

    /**
     * Название события для broadcasting
     */
    public function broadcastAs(): string
    {
        return 'review.deleted';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'review_id' => $this->reviewId,
            'target_user_id' => $this->targetUserId,
            'deleted_at' => now()->toISOString(),
        ];
    }
}