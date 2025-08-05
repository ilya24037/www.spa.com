<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationDelivery;

/**
 * Интерфейс для каналов уведомлений
 */
interface NotificationChannelInterface
{
    /**
     * Отправить уведомление через канал
     */
    public function send(Notification $notification): NotificationDelivery;

    /**
     * Проверить, может ли канал отправить уведомление
     */
    public function canSend(Notification $notification): bool;

    /**
     * Получить идентификатор канала
     */
    public function getChannelId(): string;

    /**
     * Получить название канала
     */
    public function getChannelName(): string;

    /**
     * Проверить доступность канала
     */
    public function isAvailable(): bool;

    /**
     * Получить лимиты канала (если есть)
     */
    public function getRateLimits(): array;

    /**
     * Подготовить контент для канала
     */
    public function prepareContent(Notification $notification): array;

    /**
     * Обработать webhook/callback от канала
     */
    public function handleCallback(array $data): bool;

    /**
     * Получить статус доставки из внешнего сервиса
     */
    public function getDeliveryStatus(NotificationDelivery $delivery): array;
}