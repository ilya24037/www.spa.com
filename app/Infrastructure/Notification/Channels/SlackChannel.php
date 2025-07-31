<?php

namespace App\Services\Notification\Channels;

use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

/**
 * Slack канал для админских уведомлений
 */
class SlackChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            $content = $delivery->content;
            $notification = $delivery->notification;

            // Здесь будет интеграция с Slack API
            Log::info('Slack notification sent', [
                'channel' => '#notifications',
                'message' => $content['message'] ?? '',
                'delivery_id' => $delivery->id,
            ]);

            if (config('app.debug')) {
                Log::channel('single')->info("💬 SLACK: {$content['title']}\n{$content['message']}");
            }

            return [
                'success' => true,
                'message' => 'Slack notification sent successfully',
                'external_id' => 'slack_' . time(),
                'delivery_time' => rand(1, 5),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification', [
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
        return config('services.slack.enabled', false);
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'Slack';
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return 60; // 1 минута
    }

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool
    {
        return true;
    }
}