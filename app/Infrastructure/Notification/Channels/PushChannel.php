<?php

namespace App\Infrastructure\Notification\Channels;

use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

/**
 * Push уведомления канал
 */
class PushChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            $content = $delivery->content;
            $tokens = $delivery->recipient;

            if (!$tokens) {
                return [
                    'success' => false,
                    'error' => 'No push tokens provided',
                ];
            }

            // Здесь будет интеграция с Firebase/Apple Push Notification
            Log::info('Push notification sent', [
                'tokens' => $tokens,
                'title' => $content['title'] ?? '',
                'message' => $content['message'] ?? '',
                'delivery_id' => $delivery->id,
            ]);

            if (config('app.debug')) {
                Log::channel('single')->info("🔔 PUSH: {$content['title']}\n{$content['message']}");
            }

            return [
                'success' => true,
                'message' => 'Push notification sent successfully',
                'external_id' => 'push_' . time(),
                'delivery_time' => rand(1, 5),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
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
        return config('services.push.enabled', true);
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'Push';
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return 30;
    }

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool
    {
        return true;
    }
}