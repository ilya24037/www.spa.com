<?php

namespace App\Events\Notification;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания уведомления
 */
class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для пользователя
            new PrivateChannel('user.' . $this->notification->user_id),
            
            // Канал присутствия для админов
            new PresenceChannel('admin.notifications'),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Данные для трансляции
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'type' => $this->notification->type->value,
                'title' => $this->notification->getDisplayTitle(),
                'message' => $this->notification->getDisplayMessage(),
                'icon' => $this->notification->getIcon(),
                'color' => $this->notification->getColor(),
                'priority' => $this->notification->priority,
                'created_at' => $this->notification->created_at->toISOString(),
            ],
            'user' => [
                'id' => $this->notification->user_id,
                'name' => $this->notification->user->name ?? 'System',
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->notification->shouldSendViaChannel(\App\Enums\NotificationChannel::WEBSOCKET);
    }

    /**
     * Очередь для трансляции
     */
    public function broadcastQueue(): string
    {
        return match($this->notification->priority) {
            'high' => 'high-priority-broadcasts',
            'medium' => 'broadcasts',
            'low' => 'low-priority-broadcasts',
        };
    }
}