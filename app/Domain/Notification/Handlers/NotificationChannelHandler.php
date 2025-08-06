<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Channels\NotificationChannelInterface;
use App\Domain\Notification\Models\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик каналов уведомлений
 */
class NotificationChannelHandler
{
    /**
     * Зарегистрированные каналы уведомлений
     */
    protected array $channels = [];

    public function __construct()
    {
        $this->registerDefaultChannels();
    }

    /**
     * Зарегистрировать канал уведомлений
     */
    public function registerChannel(NotificationChannelInterface $channel): void
    {
        $this->channels[$channel->getChannelId()] = $channel;
    }

    /**
     * Получить канал по идентификатору
     */
    public function getChannel(string $channelId): NotificationChannelInterface
    {
        if (!isset($this->channels[$channelId])) {
            throw new \Exception("Notification channel not found: {$channelId}");
        }

        return $this->channels[$channelId];
    }

    /**
     * Отправить уведомление через все каналы
     */
    public function sendThroughChannels(Notification $notification): array
    {
        $results = [];
        $channels = $notification->channels ?? [];

        foreach ($channels as $channelId) {
            try {
                $channel = $this->getChannel($channelId);
                
                if (!$channel->canSend($notification)) {
                    Log::warning("Cannot send notification via channel", [
                        'notification_id' => $notification->id,
                        'channel' => $channelId,
                    ]);
                    continue;
                }

                $delivery = $channel->send($notification);
                $results[$channelId] = [
                    'success' => true,
                    'delivery_id' => $delivery->id,
                ];

            } catch (\Exception $e) {
                Log::error("Failed to send notification via channel", [
                    'notification_id' => $notification->id,
                    'channel' => $channelId,
                    'error' => $e->getMessage(),
                ]);

                $results[$channelId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Проверить доступность канала
     */
    public function isChannelAvailable(string $channelId): bool
    {
        return isset($this->channels[$channelId]);
    }

    /**
     * Получить все зарегистрированные каналы
     */
    public function getRegisteredChannels(): array
    {
        return array_keys($this->channels);
    }

    /**
     * Зарегистрировать стандартные каналы
     */
    protected function registerDefaultChannels(): void
    {
        $this->registerChannel(new \App\Domain\Notification\Channels\EmailChannel());
        $this->registerChannel(new \App\Domain\Notification\Channels\SmsChannel());
        $this->registerChannel(new \App\Domain\Notification\Channels\PushChannel());
    }
}