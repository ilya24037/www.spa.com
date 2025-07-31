<?php

namespace App\Events\Notification;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие неудачной отправки уведомления
 */
class NotificationFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $reason
    ) {}
}