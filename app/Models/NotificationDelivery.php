<?php

namespace App\Models;

use App\Domain\Notification\Models\NotificationDelivery as DomainNotificationDelivery;

/**
 * Legacy-адаптер для NotificationDelivery
 * @deprecated Используйте App\Domain\Notification\Models\NotificationDelivery
 */
class NotificationDelivery extends DomainNotificationDelivery
{
    // Все функциональность наследуется из Domain модели
}