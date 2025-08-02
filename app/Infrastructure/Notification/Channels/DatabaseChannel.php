<?php

namespace App\Infrastructure\Notification\Channels;

use App\Domain\Notification\Models\NotificationDelivery;

/**
 * Канал уведомлений в базе данных
 */
class DatabaseChannel implements ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array
    {
        try {
            // Для базы данных уведомление уже сохранено
            // Просто помечаем как отправленное
            return [
                'success' => true,
                'message' => 'Notification saved to database',
                'delivery_time' => 0,
            ];

        } catch (\Exception $e) {
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
        return true; // База данных всегда доступна
    }

    /**
     * Получить название канала
     */
    public function getName(): string
    {
        return 'Database';
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
        return false;
    }
}