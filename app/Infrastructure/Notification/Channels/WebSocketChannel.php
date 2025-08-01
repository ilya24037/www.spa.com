<?php

namespace App\Infrastructure\Notification\Channels;

use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

/**
 * WebSocket канал для реального времени
 */
class WebSocketChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            $content = $delivery->content;
            $notification = $delivery->notification;

            // Здесь будет интеграция с WebSocket сервером (Pusher/Socket.io)
            Log::info('WebSocket notification sent', [
                'user_id' => $notification->user_id,
                'type' => $notification->type->value,
                'delivery_id' => $delivery->id,
            ]);

            if (config('app.debug')) {
                Log::channel('single')->info("⚡ WEBSOCKET: {$content['title']}\n{$content['message']}");
            }

            return [
                'success' => true,
                'message' => 'WebSocket notification sent successfully',
                'external_id' => 'ws_' . time(),
                'delivery_time' => 0, // Мгновенно
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send WebSocket notification', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Проверить доступность канала
     */
    public function isAvailable(): bool
    {
        return true; // WebSocket обычно всегда доступен
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'WebSocket';
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return 1;
    }

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool
    {
        return false; // Мгновенная доставка
    }
}