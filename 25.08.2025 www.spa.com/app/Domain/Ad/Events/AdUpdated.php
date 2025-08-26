<?php

namespace App\Domain\Ad\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие обновления объявления
 * Часть DDD подхода - события для междоменного взаимодействия
 */
class AdUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $adId;
    public int $userId;
    public array $updatedData;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(int $adId, int $userId, array $updatedData, array $metadata = [])
    {
        $this->adId = $adId;
        $this->userId = $userId;
        $this->updatedData = $updatedData;
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
            'updated_fields' => array_keys($this->updatedData),
            'new_title' => $this->updatedData['title'] ?? null,
            'new_price' => $this->updatedData['price'] ?? null,
            'new_status' => $this->updatedData['status'] ?? null,
            'metadata' => $this->metadata,
            'event' => 'AdUpdated',
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
        return 'ad.updated';
    }

    /**
     * Данные для broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'ad_id' => $this->adId,
            'user_id' => $this->userId,
            'updated_fields' => array_keys($this->updatedData),
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Получить новый заголовок (если обновлялся)
     */
    public function getNewTitle(): ?string
    {
        return $this->updatedData['title'] ?? null;
    }

    /**
     * Проверить обновлялся ли заголовок
     */
    public function titleWasUpdated(): bool
    {
        return isset($this->updatedData['title']);
    }

    /**
     * Проверить обновлялась ли цена
     */
    public function priceWasUpdated(): bool
    {
        return isset($this->updatedData['price']);
    }

    /**
     * Проверить обновлялся ли статус
     */
    public function statusWasUpdated(): bool
    {
        return isset($this->updatedData['status']);
    }

    /**
     * Получить список обновленных полей
     */
    public function getUpdatedFields(): array
    {
        return array_keys($this->updatedData);
    }

    /**
     * Получить новое значение поля
     */
    public function getNewValue(string $field)
    {
        return $this->updatedData[$field] ?? null;
    }
}