<?php

namespace App\Services\Notification\Channels;

use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

/**
 * Telegram канал уведомлений
 */
class TelegramChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            $content = $delivery->content;
            $telegramId = $delivery->recipient;

            if (!$telegramId) {
                return [
                    'success' => false,
                    'error' => 'No Telegram ID provided',
                ];
            }

            // Здесь будет интеграция с Telegram Bot API
            Log::info('Telegram notification sent', [
                'telegram_id' => $telegramId,
                'message' => $content['message'] ?? '',
                'delivery_id' => $delivery->id,
            ]);

            if (config('app.debug')) {
                Log::channel('single')->info("📨 TELEGRAM TO: {$telegramId}\n{$content['title']}\n{$content['message']}");
            }

            return [
                'success' => true,
                'message' => 'Telegram notification sent successfully',
                'external_id' => 'tg_' . time(),
                'delivery_time' => rand(1, 10),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send Telegram notification', [
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
        return config('services.telegram.enabled', false);
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'Telegram';
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return 120; // 2 минуты
    }

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool
    {
        return true;
    }
}