<?php

namespace App\Domain\Favorite\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие удаления объявления из избранного
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class FavoriteRemoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public int $adId;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, int $adId, array $metadata = [])
    {
        $this->userId = $userId;
        $this->adId = $adId;
        $this->metadata = $metadata;
    }

    /**
     * Получить данные события для логирования
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'ad_id' => $this->adId,
            'metadata' => $this->metadata,
            'event' => 'FavoriteRemoved',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить каналы для broadcasting (если нужно)
     */
    public function broadcastOn()
    {
        return [
            "user.{$this->userId}.favorites",
            "ad.{$this->adId}.stats"
        ];
    }

    /**
     * Название события для broadcasting
     */
    public function broadcastAs(): string
    {
        return 'favorite.removed';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'ad_id' => $this->adId,
            'removed_at' => now()->toISOString(),
        ];
    }
}