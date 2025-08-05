<?php

namespace App\Domain\Review\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие обновления отзыва
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class ReviewUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $reviewId;
    public int $reviewerId;
    public int $targetUserId;
    public array $updatedData;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $reviewId, int $reviewerId, int $targetUserId, array $updatedData, array $metadata = [])
    {
        $this->reviewId = $reviewId;
        $this->reviewerId = $reviewerId;
        $this->targetUserId = $targetUserId;
        $this->updatedData = $updatedData;
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
            'updated_fields' => array_keys($this->updatedData),
            'new_rating' => $this->updatedData['rating'] ?? null,
            'comment_updated' => isset($this->updatedData['comment']),
            'anonymity_changed' => isset($this->updatedData['is_anonymous']),
            'metadata' => $this->metadata,
            'event' => 'ReviewUpdated',
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
        return 'review.updated';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'review_id' => $this->reviewId,
            'target_user_id' => $this->targetUserId,
            'updated_fields' => array_keys($this->updatedData),
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Получить новый рейтинг (если обновлялся)
     */
    public function getNewRating(): ?int
    {
        return $this->updatedData['rating'] ?? null;
    }

    /**
     * Проверить обновлялся ли рейтинг
     */
    public function ratingWasUpdated(): bool
    {
        return isset($this->updatedData['rating']);
    }

    /**
     * Проверить обновлялся ли комментарий
     */
    public function commentWasUpdated(): bool
    {
        return isset($this->updatedData['comment']);
    }

    /**
     * Проверить изменялись ли настройки анонимности
     */
    public function anonymityWasChanged(): bool
    {
        return isset($this->updatedData['is_anonymous']);
    }

    /**
     * Получить список обновленных полей
     */
    public function getUpdatedFields(): array
    {
        return array_keys($this->updatedData);
    }
}