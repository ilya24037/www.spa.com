<?php

namespace App\Domain\Notification\Push;

use App\Domain\Notification\Models\Notification;

/**
 * Интерфейс провайдера Push уведомлений
 */
interface PushProviderInterface
{
    /**
     * Отправить push уведомление
     */
    public function send(string $token, array $payload, Notification $notification): array;

    /**
     * Обработать callback от провайдера
     */
    public function handleCallback(array $data): bool;
}