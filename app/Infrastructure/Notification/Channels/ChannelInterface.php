<?php

namespace App\Infrastructure\Notification\Channels;

use App\Models\NotificationDelivery;

/**
 * Интерфейс для каналов уведомлений
 */
interface ChannelInterface
{
    /**
     * Отправить уведомление
     */
    public function send(NotificationDelivery $delivery): array;

    /**
     * Проверить доступность канала
     */
    public function isAvailable(): bool;

    /**
     * Получить название канала
     */
    public function getName(): string;

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int;

    /**
     * Поддерживает ли канал подтверждение доставки
     */
    public function supportsDeliveryConfirmation(): bool;
}