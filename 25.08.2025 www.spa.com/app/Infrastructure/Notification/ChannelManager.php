<?php

namespace App\Infrastructure\Notification;

use App\Enums\NotificationChannel;
use App\Infrastructure\Notification\Channels\ChannelInterface;
use App\Infrastructure\Notification\Channels\DatabaseChannel;
use App\Infrastructure\Notification\Channels\EmailChannel;
use App\Infrastructure\Notification\Channels\SmsChannel;
use App\Infrastructure\Notification\Channels\PushChannel;
use App\Infrastructure\Notification\Channels\WebSocketChannel;
use App\Infrastructure\Notification\Channels\TelegramChannel;
use App\Infrastructure\Notification\Channels\SlackChannel;

/**
 * Менеджер каналов уведомлений
 */
class ChannelManager
{
    protected array $channels = [];

    public function __construct()
    {
        $this->registerDefaultChannels();
    }

    /**
     * Зарегистрировать каналы по умолчанию
     */
    protected function registerDefaultChannels(): void
    {
        $this->channels = [
            NotificationChannel::DATABASE->value => app(DatabaseChannel::class),
            NotificationChannel::EMAIL->value => app(EmailChannel::class),
            NotificationChannel::SMS->value => app(SmsChannel::class),
            NotificationChannel::PUSH->value => app(PushChannel::class),
            NotificationChannel::WEBSOCKET->value => app(WebSocketChannel::class),
            NotificationChannel::TELEGRAM->value => app(TelegramChannel::class),
            NotificationChannel::SLACK->value => app(SlackChannel::class),
        ];
    }

    /**
     * Получить канал
     */
    public function getChannel(NotificationChannel $channel): ChannelInterface
    {
        if (!isset($this->channels[$channel->value])) {
            throw new \InvalidArgumentException("Channel {$channel->value} not registered");
        }

        return $this->channels[$channel->value];
    }

    /**
     * Зарегистрировать канал
     */
    public function registerChannel(NotificationChannel $channel, ChannelInterface $instance): void
    {
        $this->channels[$channel->value] = $instance;
    }

    /**
     * Получить все доступные каналы
     */
    public function getAvailableChannels(): array
    {
        return array_filter($this->channels, function($channel) {
            return $channel->isAvailable();
        });
    }

    /**
     * Проверить доступность канала
     */
    public function isChannelAvailable(NotificationChannel $channel): bool
    {
        if (!isset($this->channels[$channel->value])) {
            return false;
        }

        return $this->channels[$channel->value]->isAvailable();
    }
}