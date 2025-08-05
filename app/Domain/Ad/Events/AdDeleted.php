<?php

namespace App\Domain\Ad\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие удаления объявления
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class AdDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $adId;
    public int $userId;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $adId, int $userId, array $metadata = [])
    {
        $this->adId = $adId;
        $this->userId = $userId;
        $this->metadata = $metadata;
    }

    /**
     * Получить данные события для логирования
     */
    public function toArray(): array
    {
        return [
            'ad_id' => $this->adId,
            'user_id' => $this->userId,
            'metadata' => $this->metadata,
            'event' => 'AdDeleted',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить каналы для broadcasting (если нужно)
     */
    public function broadcastOn()
    {
        return [
            "user.{$this->userId}.ads",
            "ad.{$this->adId}.updates"
        ];
    }

    /**
     * Название события для broadcasting
     */
    public function broadcastAs(): string
    {
        return 'ad.deleted';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'ad_id' => $this->adId,
            'user_id' => $this->userId,
            'deleted_at' => now()->toISOString(),
        ];
    }
}