<?php

namespace App\Enums;

/**
 * Каналы отправки уведомлений
 */
enum NotificationChannel: string
{
    case DATABASE = 'database';    // В базе данных (для UI)
    case EMAIL = 'email';         // Email уведомления
    case SMS = 'sms';            // SMS уведомления
    case PUSH = 'push';          // Push уведомления
    case WEBSOCKET = 'websocket'; // WebSocket (реальное время)
    case TELEGRAM = 'telegram';   // Telegram бот
    case SLACK = 'slack';        // Slack (для админов)

    /**
     * Получить название канала
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DATABASE => 'В приложении',
            self::EMAIL => 'Email',
            self::SMS => 'SMS',
            self::PUSH => 'Push уведомления',
            self::WEBSOCKET => 'Мгновенно',
            self::TELEGRAM => 'Telegram',
            self::SLACK => 'Slack',
        };
    }

    /**
     * Получить иконку канала
     */
    public function getIcon(): string
    {
        return match($this) {
            self::DATABASE => '🔔',
            self::EMAIL => '📧',
            self::SMS => '📱',
            self::PUSH => '🔔',
            self::WEBSOCKET => '⚡',
            self::TELEGRAM => '📨',
            self::SLACK => '💬',
        };
    }

    /**
     * Получить описание канала
     */
    public function getDescription(): string
    {
        return match($this) {
            self::DATABASE => 'Уведомления в интерфейсе приложения',
            self::EMAIL => 'Отправка на электронную почту',
            self::SMS => 'Отправка SMS на телефон',
            self::PUSH => 'Push уведомления на устройство',
            self::WEBSOCKET => 'Мгновенные уведомления в реальном времени',
            self::TELEGRAM => 'Уведомления через Telegram бота',
            self::SLACK => 'Уведомления в Slack для команды',
        };
    }

    /**
     * Получить максимальное время доставки (в секундах)
     */
    public function getMaxDeliveryTime(): int
    {
        return match($this) {
            self::WEBSOCKET => 1,
            self::PUSH => 30,
            self::DATABASE => 60,
            self::SMS => 300,      // 5 минут
            self::EMAIL => 600,    // 10 минут
            self::TELEGRAM => 120, // 2 минуты
            self::SLACK => 60,     // 1 минута
        };
    }

    /**
     * Проверить, поддерживает ли канал групповые уведомления
     */
    public function supportsGrouping(): bool
    {
        return match($this) {
            self::EMAIL,
            self::SLACK,
            self::DATABASE => true,
            
            default => false,
        };
    }

    /**
     * Проверить, поддерживает ли канал форматированный контент
     */
    public function supportsRichContent(): bool
    {
        return match($this) {
            self::EMAIL,
            self::SLACK,
            self::TELEGRAM,
            self::DATABASE => true,
            
            default => false,
        };
    }

    /**
     * Получить максимальную длину сообщения
     */
    public function getMaxMessageLength(): ?int
    {
        return match($this) {
            self::SMS => 160,
            self::PUSH => 100,
            self::TELEGRAM => 4096,
            
            default => null, // Без ограничений
        };
    }

    /**
     * Проверить, поддерживает ли канал вложения
     */
    public function supportsAttachments(): bool
    {
        return match($this) {
            self::EMAIL,
            self::TELEGRAM,
            self::SLACK => true,
            
            default => false,
        };
    }

    /**
     * Получить приоритет канала (чем меньше, тем выше приоритет)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::WEBSOCKET => 1,
            self::PUSH => 2,
            self::DATABASE => 3,
            self::SMS => 4,
            self::EMAIL => 5,
            self::TELEGRAM => 6,
            self::SLACK => 7,
        };
    }

    /**
     * Проверить, требует ли канал подтверждение доставки
     */
    public function requiresDeliveryConfirmation(): bool
    {
        return match($this) {
            self::SMS,
            self::EMAIL,
            self::PUSH => true,
            
            default => false,
        };
    }

    /**
     * Получить каналы по умолчанию для типа уведомления
     */
    public static function getDefaultChannels(NotificationType $type): array
    {
        $channels = [self::DATABASE]; // Всегда сохраняем в базе

        if ($type->shouldSendPush()) {
            $channels[] = self::PUSH;
            $channels[] = self::WEBSOCKET;
        }

        if ($type->shouldSendEmail()) {
            $channels[] = self::EMAIL;
        }

        if ($type->shouldSendSms()) {
            $channels[] = self::SMS;
        }

        return $channels;
    }

    /**
     * Получить каналы для админских уведомлений
     */
    public static function getAdminChannels(): array
    {
        return [
            self::DATABASE,
            self::EMAIL,
            self::SLACK,
            self::TELEGRAM,
        ];
    }

    /**
     * Получить каналы для критических уведомлений
     */
    public static function getCriticalChannels(): array
    {
        return [
            self::WEBSOCKET,
            self::PUSH,
            self::SMS,
            self::EMAIL,
            self::DATABASE,
        ];
    }

    /**
     * Получить статистику по каналам
     */
    public static function getChannelStats(): array
    {
        return [
            'instant' => [self::WEBSOCKET, self::PUSH],
            'fast' => [self::DATABASE, self::TELEGRAM, self::SLACK],
            'standard' => [self::EMAIL, self::SMS],
        ];
    }

    /**
     * Проверить, доступен ли канал в данный момент
     */
    public function isAvailable(): bool
    {
        return match($this) {
            self::DATABASE, 
            self::WEBSOCKET => true,
            
            self::EMAIL => config('mail.enabled', true),
            self::SMS => config('services.sms.enabled', true),
            self::PUSH => config('services.push.enabled', true),
            self::TELEGRAM => config('services.telegram.enabled', false),
            self::SLACK => config('services.slack.enabled', false),
        };
    }
}