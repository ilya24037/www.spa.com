<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Models\NotificationDelivery;
use App\Domain\User\Models\User;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Infrastructure\Notification\ChannelManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendNotificationJob;
use App\Jobs\SendNotificationBatchJob;

/**
 * Менеджер доставки уведомлений
 */
class NotificationDeliveryManager
{
    protected ChannelManager $channelManager;

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
    }

    /**
     * Отправить уведомление пользователю
     */
    public function send(Notification $notification, User $user, array $channels = []): Collection
    {
        if (empty($channels)) {
            $channels = $this->getDefaultChannels($user);
        }

        $deliveries = collect();

        foreach ($channels as $channel) {
            $delivery = $this->createDelivery($notification, $user, $channel);
            $deliveries->push($delivery);
            
            $this->dispatchDelivery($delivery);
        }

        Log::info('Notification queued for delivery', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'channels' => array_map(fn($c) => $c->value, $channels),
            'deliveries_count' => $deliveries->count()
        ]);

        return $deliveries;
    }

    /**
     * Массовая отправка уведомлений
     */
    public function sendBatch(Notification $notification, Collection $users, array $channels = []): int
    {
        $deliveries = collect();
        
        foreach ($users as $user) {
            $userChannels = empty($channels) ? $this->getDefaultChannels($user) : $channels;
            
            foreach ($userChannels as $channel) {
                $delivery = $this->createDelivery($notification, $user, $channel);
                $deliveries->push($delivery);
            }
        }

        // Группируем доставки по каналам для оптимизации
        $groupedDeliveries = $deliveries->groupBy(fn($d) => $d->channel->value);

        foreach ($groupedDeliveries as $channel => $channelDeliveries) {
            SendNotificationBatchJob::dispatch($channelDeliveries->pluck('id')->toArray())
                ->onQueue('notifications');
        }

        Log::info('Notification batch queued for delivery', [
            'notification_id' => $notification->id,
            'users_count' => $users->count(),
            'deliveries_count' => $deliveries->count(),
            'channels' => $groupedDeliveries->keys()->toArray()
        ]);

        return $deliveries->count();
    }

    /**
     * Выполнить доставку уведомления
     */
    public function executeDelivery(NotificationDelivery $delivery): bool
    {
        try {
            $delivery->update(['status' => NotificationStatus::SENDING]);

            $channel = $this->channelManager->getChannel($delivery->channel);
            $result = $channel->send($delivery);

            if ($result) {
                $delivery->update([
                    'status' => NotificationStatus::DELIVERED,
                    'delivered_at' => now(),
                    'attempts' => $delivery->attempts + 1
                ]);

                Log::info('Notification delivered successfully', [
                    'delivery_id' => $delivery->id,
                    'channel' => $delivery->channel->value,
                    'user_id' => $delivery->user_id
                ]);

                return true;
            } else {
                $this->handleDeliveryFailure($delivery, 'Channel returned false');
                return false;
            }

        } catch (\Exception $e) {
            $this->handleDeliveryFailure($delivery, $e->getMessage());
            return false;
        }
    }

    /**
     * Повторить неудачную доставку
     */
    public function retryDelivery(NotificationDelivery $delivery): void
    {
        if ($delivery->attempts >= config('notifications.max_attempts', 3)) {
            $delivery->update(['status' => NotificationStatus::FAILED]);
            
            Log::warning('Notification delivery permanently failed', [
                'delivery_id' => $delivery->id,
                'attempts' => $delivery->attempts
            ]);
            
            return;
        }

        $delay = $this->calculateRetryDelay($delivery->attempts);
        
        SendNotificationJob::dispatch($delivery->id)
            ->delay(now()->addSeconds($delay))
            ->onQueue('notifications');

        Log::info('Notification delivery retry scheduled', [
            'delivery_id' => $delivery->id,
            'attempt' => $delivery->attempts + 1,
            'delay_seconds' => $delay
        ]);
    }

    /**
     * Получить статистику доставки уведомления
     */
    public function getDeliveryStats(Notification $notification): array
    {
        $deliveries = $notification->deliveries;

        return [
            'total' => $deliveries->count(),
            'pending' => $deliveries->where('status', NotificationStatus::PENDING)->count(),
            'sending' => $deliveries->where('status', NotificationStatus::SENDING)->count(),
            'delivered' => $deliveries->where('status', NotificationStatus::DELIVERED)->count(),
            'failed' => $deliveries->where('status', NotificationStatus::FAILED)->count(),
            'by_channel' => $deliveries->groupBy('channel')
                ->map(fn($group) => [
                    'total' => $group->count(),
                    'delivered' => $group->where('status', NotificationStatus::DELIVERED)->count(),
                    'failed' => $group->where('status', NotificationStatus::FAILED)->count()
                ])
        ];
    }

    /**
     * Очистить старые записи доставки
     */
    public function cleanupOldDeliveries(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $count = NotificationDelivery::where('created_at', '<', $cutoffDate)
            ->where('status', NotificationStatus::DELIVERED)
            ->delete();

        Log::info('Old notification deliveries cleaned up', [
            'deleted_count' => $count,
            'cutoff_date' => $cutoffDate->toDateString()
        ]);

        return $count;
    }

    /**
     * Создать запись доставки
     */
    protected function createDelivery(
        Notification $notification,
        User $user,
        NotificationChannel $channel
    ): NotificationDelivery {
        return NotificationDelivery::create([
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'channel' => $channel,
            'status' => NotificationStatus::PENDING,
            'attempts' => 0,
            'channel_data' => $this->prepareChannelData($user, $channel)
        ]);
    }

    /**
     * Подготовить данные для канала
     */
    protected function prepareChannelData(User $user, NotificationChannel $channel): array
    {
        return match($channel) {
            NotificationChannel::EMAIL => [
                'to' => $user->email,
                'name' => $user->name
            ],
            NotificationChannel::SMS => [
                'to' => $user->phone
            ],
            NotificationChannel::PUSH => [
                'device_tokens' => $user->device_tokens ?? []
            ],
            NotificationChannel::IN_APP => [
                'user_id' => $user->id
            ],
            default => []
        };
    }

    /**
     * Получить каналы по умолчанию для пользователя
     */
    protected function getDefaultChannels(User $user): array
    {
        $channels = [];

        // Всегда добавляем внутренние уведомления
        $channels[] = NotificationChannel::IN_APP;

        // Email если есть и разрешен
        if ($user->email && ($user->notification_settings['email'] ?? true)) {
            $channels[] = NotificationChannel::EMAIL;
        }

        // SMS если есть телефон и разрешен
        if ($user->phone && ($user->notification_settings['sms'] ?? false)) {
            $channels[] = NotificationChannel::SMS;
        }

        // Push если есть устройства и разрешены
        if (!empty($user->device_tokens) && ($user->notification_settings['push'] ?? true)) {
            $channels[] = NotificationChannel::PUSH;
        }

        return $channels;
    }

    /**
     * Отправить доставку в очередь
     */
    protected function dispatchDelivery(NotificationDelivery $delivery): void
    {
        $delay = $this->getDeliveryDelay($delivery->channel);
        
        SendNotificationJob::dispatch($delivery->id)
            ->delay($delay)
            ->onQueue('notifications');
    }

    /**
     * Получить задержку доставки для канала
     */
    protected function getDeliveryDelay(NotificationChannel $channel): int
    {
        return match($channel) {
            NotificationChannel::IN_APP => 0,      // Мгновенно
            NotificationChannel::PUSH => 1,       // 1 секунда
            NotificationChannel::EMAIL => 10,     // 10 секунд
            NotificationChannel::SMS => 30,       // 30 секунд
            default => 0
        };
    }

    /**
     * Рассчитать задержку для повтора
     */
    protected function calculateRetryDelay(int $attempt): int
    {
        // Экспоненциальная задержка: 60, 300, 900 секунд
        return min(60 * pow(5, $attempt), 900);
    }

    /**
     * Обработать неудачную доставку
     */
    protected function handleDeliveryFailure(NotificationDelivery $delivery, string $error): void
    {
        $delivery->update([
            'status' => NotificationStatus::FAILED,
            'error_message' => $error,
            'attempts' => $delivery->attempts + 1,
            'failed_at' => now()
        ]);

        Log::error('Notification delivery failed', [
            'delivery_id' => $delivery->id,
            'channel' => $delivery->channel->value,
            'user_id' => $delivery->user_id,
            'error' => $error,
            'attempts' => $delivery->attempts
        ]);

        // Запланировать повтор если не превышен лимит попыток
        if ($delivery->attempts < config('notifications.max_attempts', 3)) {
            $this->retryDelivery($delivery);
        }
    }
}