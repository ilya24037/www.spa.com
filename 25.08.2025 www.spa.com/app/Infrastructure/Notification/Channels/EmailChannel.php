<?php

namespace App\Infrastructure\Notification\Channels;

use App\Domain\Notification\Models\NotificationDelivery;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Email канал уведомлений
 */
class EmailChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            $content = $delivery->content;
            $recipient = $delivery->recipient;

            if (!$recipient) {
                return [
                    'success' => false,
                    'error' => 'No email address provided',
                ];
            }

            // Здесь будет реальная отправка email
            // Пока логируем для разработки
            Log::info('Email notification sent', [
                'to' => $recipient,
                'subject' => $content['title'] ?? 'Уведомление',
                'delivery_id' => $delivery->id,
            ]);

            if (config('app.debug')) {
                Log::channel('single')->info("📧 EMAIL TO: {$recipient}\nSUBJECT: {$content['title']}\nCONTENT:\n{$content['message']}");
            }

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'external_id' => 'email_' . time(),
                'delivery_time' => rand(1, 5), // Симуляция времени доставки
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
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
        return config('mail.enabled', true);
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'Email';
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return 600; // 10 минут
    }

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool
    {
        return true;
    }
}