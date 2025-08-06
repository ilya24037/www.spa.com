<?php

namespace App\Domain\Notification\Push;

use App\Domain\Notification\Models\Notification;

/**
 * Строитель payload для push уведомлений
 */
class PushPayloadBuilder
{
    /**
     * Подготовить payload для push уведомления
     */
    public function build(Notification $notification, array $content): array
    {
        return [
            'title' => $content['title'],
            'body' => $content['message'],
            'icon' => $notification->getIcon(),
            'click_action' => $notification->getActionUrl() ?? config('app.url'),
            'data' => array_merge($notification->data ?? [], [
                'notification_id' => $notification->id,
                'type' => $notification->type->value,
                'created_at' => $notification->created_at->toISOString(),
            ]),
            'actions' => $this->getActions($notification),
        ];
    }

    /**
     * Получить действия для push уведомления
     */
    protected function getActions(Notification $notification): array
    {
        $actions = [];

        // Базовые действия
        if ($notification->getActionUrl()) {
            $actions[] = [
                'action' => 'open',
                'title' => $notification->getActionText() ?? 'Открыть',
                'url' => $notification->getActionUrl(),
            ];
        }

        // Специфичные действия по типу уведомления
        switch ($notification->type) {
            case \App\Enums\NotificationType::BOOKING_REMINDER:
                $actions[] = [
                    'action' => 'confirm',
                    'title' => 'Подтвердить',
                ];
                $actions[] = [
                    'action' => 'cancel',
                    'title' => 'Отменить',
                ];
                break;
                
            case \App\Enums\NotificationType::NEW_MESSAGE:
                $actions[] = [
                    'action' => 'reply',
                    'title' => 'Ответить',
                ];
                break;
        }

        return $actions;
    }
}