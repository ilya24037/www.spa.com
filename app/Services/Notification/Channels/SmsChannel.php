<?php

namespace App\Services\Notification\Channels;

use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\Log;

/**
 * SMS канал уведомлений
 */
class SmsChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            $content = $delivery->content;
            $phone = $delivery->recipient;

            if (!$phone) {
                return [
                    'success' => false,
                    'error' => 'No phone number provided',
                ];
            }

            $message = $content['message'] ?? '';
            
            // Здесь будет интеграция с SMS провайдером
            Log::info('SMS notification sent', [
                'phone' => $phone,
                'message' => $message,
                'delivery_id' => $delivery->id,
            ]);

            if (config('app.debug')) {
                Log::channel('single')->info("📱 SMS TO: {$phone}\nMESSAGE: {$message}");
            }

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'external_id' => 'sms_' . time(),
                'delivery_time' => rand(1, 30), // Симуляция времени доставки
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
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
        return config('services.sms.enabled', true);
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'SMS';
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return 300; // 5 минут
    }

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool
    {
        return true;
    }
}