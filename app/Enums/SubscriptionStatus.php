<?php

namespace App\Enums;

/**
 * Статусы подписок
 */
enum SubscriptionStatus: string
{
    case PENDING = 'pending';       // Ожидает оплаты
    case ACTIVE = 'active';         // Активна
    case EXPIRED = 'expired';       // Истекла
    case CANCELLED = 'cancelled';   // Отменена пользователем
    case SUSPENDED = 'suspended';   // Приостановлена (неоплата)
    case TRIAL = 'trial';          // Пробный период

    /**
     * Получить название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает оплаты',
            self::ACTIVE => 'Активна',
            self::EXPIRED => 'Истекла',
            self::CANCELLED => 'Отменена',
            self::SUSPENDED => 'Приостановлена',
            self::TRIAL => 'Пробный период',
        };
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => '#F59E0B',    // amber
            self::ACTIVE => '#10B981',     // green
            self::EXPIRED => '#6B7280',    // gray
            self::CANCELLED => '#EF4444',  // red
            self::SUSPENDED => '#F97316',  // orange
            self::TRIAL => '#3B82F6',      // blue
        };
    }

    /**
     * Получить иконку
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::ACTIVE => '✅',
            self::EXPIRED => '⌛',
            self::CANCELLED => '❌',
            self::SUSPENDED => '⚠️',
            self::TRIAL => '🎁',
        };
    }

    /**
     * Проверить, активна ли подписка
     */
    public function isActive(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIAL]);
    }

    /**
     * Можно ли продлить подписку
     */
    public function canRenew(): bool
    {
        return in_array($this, [self::ACTIVE, self::EXPIRED, self::TRIAL]);
    }

    /**
     * Можно ли отменить подписку
     */
    public function canCancel(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIAL, self::PENDING]);
    }

    /**
     * Можно ли возобновить подписку
     */
    public function canResume(): bool
    {
        return in_array($this, [self::CANCELLED, self::SUSPENDED, self::EXPIRED]);
    }
}