<?php

namespace App\Models;

use App\Domain\Notification\Models\Notification as DomainNotification;

/**
 * Legacy-адаптер для Notification
 * @deprecated Используйте App\Domain\Notification\Models\Notification
 */
class Notification extends DomainNotification
{
    // Все функциональность наследуется из Domain модели
}