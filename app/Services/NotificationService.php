<?php

namespace App\Services;

use App\Infrastructure\Notification\NotificationService as InfrastructureNotificationService;

/**
 * Legacy-адаптер для NotificationService
 * @deprecated Используйте App\Infrastructure\Notification\NotificationService
 */
class NotificationService extends InfrastructureNotificationService
{
    // Все функциональность наследуется из Infrastructure сервиса
}