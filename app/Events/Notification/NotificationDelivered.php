<?php

namespace App\Events\Notification;

use App\Domain\Notification\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие доставки уведомления
 */
class NotificationDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification
    ) {}
}