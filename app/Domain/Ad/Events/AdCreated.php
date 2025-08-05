<?php

namespace App\Domain\Ad\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания нового объявления
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class AdCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $adId;
    public int $userId;
    public array $adData;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $adId, int $userId, array $adData, array $metadata = [])
    {
        $this->adId = $adId;
        $this->userId = $userId;
        $this->adData = $adData;
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
            'title' => $this->adData['title'] ?? '',
            'category' => $this->adData['category'] ?? '',
            'price' => $this->adData['price'] ?? null,
            'status' => $this->adData['status'] ?? 'draft',
            'metadata' => $this->metadata,
            'event' => 'AdCreated',
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
            "ads.global"
        ];
    }

    /**
     * Название события для broadcasting
     */
    public function broadcastAs(): string
    {
        return 'ad.created';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'ad_id' => $this->adId,
            'user_id' => $this->userId,
            'title' => $this->adData['title'] ?? '',
            'status' => $this->adData['status'] ?? 'draft',
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Получить статус объявления
     */
    public function getStatus(): string
    {
        return $this->adData['status'] ?? 'draft';
    }

    /**
     * Получить заголовок объявления
     */
    public function getTitle(): string
    {
        return $this->adData['title'] ?? '';
    }

    /**
     * Получить категорию объявления
     */
    public function getCategory(): string
    {
        return $this->adData['category'] ?? '';
    }

    /**
     * Получить цену объявления
     */
    public function getPrice(): ?float
    {
        return $this->adData['price'] ?? null;
    }
}