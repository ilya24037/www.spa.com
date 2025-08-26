<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\DTOs\CreateNotificationDTO;
use App\Domain\Notification\Repositories\NotificationRepository;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

/**
 * Обработчик создания и отправки уведомлений
 */
class NotificationSendHandler
{
    public function __construct(
        protected NotificationRepository $repository,
        protected NotificationChannelHandler $channelHandler
    ) {}

    /**
     * Создать уведомление
     */
    public function create(CreateNotificationDTO $dto): Notification
    {
        return DB::transaction(function () use ($dto) {
            // Создать уведомление
            $notification = $this->repository->create([
                'user_id' => $dto->userId,
                'type' => $dto->type,
                'title' => $dto->title,
                'message' => $dto->message,
                'data' => $dto->data,
                'channels' => $dto->channels,
                'notifiable_type' => $dto->notifiableType,
                'notifiable_id' => $dto->notifiableId,
                'scheduled_at' => $dto->scheduledAt,
                'expires_at' => $dto->expiresAt,
                'priority' => $dto->priority,
                'group_key' => $dto->groupKey,
                'template' => $dto->template,
                'locale' => $dto->locale,
                'metadata' => $dto->metadata,
            ]);

            // Сгенерировать group_key если не указан
            if (!$dto->groupKey) {
                $groupKey = $this->generateGroupKey($notification);
                $notification->update(['group_key' => $groupKey]);
            }

            // Запланировать отправку
            if ($dto->scheduledAt) {
                $this->schedule($notification, $dto->scheduledAt);
            } else {
                $this->queue($notification);
            }

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'type' => $notification->type->value,
                'channels' => $notification->channels,
            ]);

            return $notification;
        });
    }

    /**
     * Отправить уведомление немедленно
     */
    public function send(Notification $notification): array
    {
        if (!$notification->isPending()) {
            throw new \Exception('Notification is not in pending status');
        }

        if ($notification->isExpired()) {
            $notification->cancel();
            throw new \Exception('Notification has expired');
        }

        $results = $this->channelHandler->sendThroughChannels($notification);

        // Обновить статус уведомления
        $hasSuccessful = count(array_filter($results, fn($r) => $r['success']));
        
        if ($hasSuccessful > 0) {
            $notification->markAsSent();
            
            // Проверить, все ли каналы доставили успешно
            if ($hasSuccessful === count($notification->channels ?? [])) {
                $notification->markAsDelivered();
            }
        } else {
            $notification->markAsFailed('All channels failed');
        }

        return $results;
    }

    /**
     * Добавить уведомление в очередь
     */
    public function queue(Notification $notification, string $queue = 'notifications'): void
    {
        Queue::push(new \App\Jobs\SendNotificationJob($notification->id), $queue);
    }

    /**
     * Запланировать отправку уведомления
     */
    public function schedule(Notification $notification, Carbon $when): void
    {
        $notification->schedule($when);
        Queue::later($when, new \App\Jobs\SendNotificationJob($notification->id));
    }

    /**
     * Массовая отправка уведомлений
     */
    public function sendBatch(array $notificationIds, string $queue = 'notifications'): void
    {
        $chunks = array_chunk($notificationIds, 100);
        
        foreach ($chunks as $chunk) {
            Queue::push(new \App\Jobs\SendNotificationBatchJob($chunk), $queue);
        }
    }

    /**
     * Отправить уведомление группе пользователей
     */
    public function sendToUsers(
        array $userIds,
        NotificationType $type,
        array $data = [],
        array $options = []
    ): array {
        $notifications = [];
        
        foreach ($userIds as $userId) {
            try {
                $dto = new CreateNotificationDTO(array_merge([
                    'userId' => $userId,
                    'type' => $type,
                    'title' => $options['title'] ?? $type->getTitle(),
                    'message' => $options['message'] ?? $type->getDefaultMessage(),
                    'data' => $data,
                    'channels' => $options['channels'] ?? $type->getDefaultChannels(),
                    'priority' => $options['priority'] ?? 'medium',
                ], $options));

                $notifications[] = $this->create($dto);
            } catch (\Exception $e) {
                Log::error('Failed to create notification for user', [
                    'user_id' => $userId,
                    'type' => $type->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notifications;
    }

    /**
     * Отправить уведомление всем пользователям
     */
    public function broadcast(
        NotificationType $type,
        array $data = [],
        array $options = []
    ): void {
        // Получить всех активных пользователей порциями
        User::active()
            ->chunk(1000, function ($users) use ($type, $data, $options) {
                $userIds = $users->pluck('id')->toArray();
                $this->sendToUsers($userIds, $type, $data, $options);
            });
    }

    /**
     * Повторить отправку неудачных уведомлений
     */
    public function retryFailed(array $filters = []): int
    {
        $notifications = Notification::failed()
            ->where('retry_count', '<', DB::raw('max_retries'))
            ->when(!empty($filters['created_after']), function ($query) use ($filters) {
                $query->where('created_at', '>=', $filters['created_after']);
            })
            ->when(!empty($filters['type']), function ($query) use ($filters) {
                $query->where('type', $filters['type']);
            })
            ->limit($filters['limit'] ?? 100)
            ->get();

        foreach ($notifications as $notification) {
            $notification->update([
                'status' => NotificationStatus::PENDING,
                'retry_count' => $notification->retry_count + 1,
            ]);
            
            $this->queue($notification);
        }

        Log::info('Failed notifications queued for retry', [
            'count' => $notifications->count(),
            'filters' => $filters,
        ]);

        return $notifications->count();
    }

    /**
     * Сгенерировать ключ группировки
     */
    protected function generateGroupKey(Notification $notification): string
    {
        $parts = [
            $notification->user_id,
            $notification->type->value,
        ];

        // Добавить идентификатор связанной сущности
        if ($notification->notifiable_id) {
            $parts[] = $notification->notifiable_type;
            $parts[] = $notification->notifiable_id;
        }

        // Добавить дату для группировки по дням
        $parts[] = $notification->created_at->format('Y-m-d');

        return implode(':', $parts);
    }
}