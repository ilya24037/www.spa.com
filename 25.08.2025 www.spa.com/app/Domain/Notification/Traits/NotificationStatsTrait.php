<?php

namespace App\Domain\Notification\Traits;

/**
 * Трейт для статистики уведомлений
 */
trait NotificationStatsTrait
{
    /**
     * Статистика доставки
     */
    public function getDeliveryStats(): array
    {
        $deliveries = $this->deliveries->groupBy('channel');
        
        return $deliveries->map(function($channelDeliveries) {
            return [
                'total' => $channelDeliveries->count(),
                'sent' => $channelDeliveries->where('status', 'sent')->count(),
                'delivered' => $channelDeliveries->where('status', 'delivered')->count(),
                'failed' => $channelDeliveries->where('status', 'failed')->count(),
            ];
        })->toArray();
    }

    /**
     * Время обработки уведомления
     */
    public function getProcessingTime(): ?int
    {
        if ($this->sent_at && $this->created_at) {
            return $this->sent_at->diffInSeconds($this->created_at);
        }
        
        return null;
    }

    /**
     * Время доставки уведомления
     */
    public function getDeliveryTime(): ?int
    {
        if ($this->delivered_at && $this->sent_at) {
            return $this->delivered_at->diffInSeconds($this->sent_at);
        }
        
        return null;
    }
}