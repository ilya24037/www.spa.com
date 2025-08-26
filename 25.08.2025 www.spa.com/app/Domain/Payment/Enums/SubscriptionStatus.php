<?php

namespace App\Domain\Payment\Enums;

/**
 * Статусы подписки
 */
enum SubscriptionStatus: string
{
    case PENDING = 'pending';
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case PAUSED = 'paused';
    case INCOMPLETE = 'incomplete';
    
    /**
     * Получить человекочитаемое название
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает активации',
            self::TRIALING => 'Пробный период',
            self::ACTIVE => 'Активна',
            self::PAST_DUE => 'Просрочена',
            self::CANCELLED => 'Отменена',
            self::EXPIRED => 'Истекла',
            self::PAUSED => 'Приостановлена',
            self::INCOMPLETE => 'Не завершена',
        };
    }
    
    /**
     * Получить цвет статуса
     */
    public function color(): string
    {
        return match($this) {
            self::ACTIVE => '#10B981',
            self::TRIALING => '#3B82F6',
            self::PENDING => '#F59E0B',
            self::PAST_DUE => '#F97316',
            self::CANCELLED => '#6B7280',
            self::EXPIRED => '#EF4444',
            self::PAUSED => '#8B5CF6',
            self::INCOMPLETE => '#F59E0B',
        };
    }
    
    /**
     * Получить иконку статуса
     */
    public function icon(): string
    {
        return match($this) {
            self::ACTIVE => 'check-circle',
            self::TRIALING => 'clock',
            self::PENDING => 'hourglass',
            self::PAST_DUE => 'alert-triangle',
            self::CANCELLED => 'x-circle',
            self::EXPIRED => 'calendar-x',
            self::PAUSED => 'pause-circle',
            self::INCOMPLETE => 'alert-circle',
        };
    }
    
    /**
     * Проверка активности подписки
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::ACTIVE,
            self::TRIALING,
        ]);
    }
    
    /**
     * Проверка завершенности подписки
     */
    public function isTerminated(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::EXPIRED,
        ]);
    }
    
    /**
     * Требует ли подписка оплаты
     */
    public function requiresPayment(): bool
    {
        return in_array($this, [
            self::PAST_DUE,
            self::INCOMPLETE,
        ]);
    }
    
    /**
     * Возможные переходы статусов
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [
                self::TRIALING,
                self::ACTIVE,
                self::CANCELLED,
            ]),
            self::TRIALING => in_array($newStatus, [
                self::ACTIVE,
                self::CANCELLED,
                self::EXPIRED,
            ]),
            self::ACTIVE => in_array($newStatus, [
                self::PAST_DUE,
                self::CANCELLED,
                self::PAUSED,
                self::EXPIRED,
            ]),
            self::PAST_DUE => in_array($newStatus, [
                self::ACTIVE,
                self::CANCELLED,
                self::EXPIRED,
            ]),
            self::PAUSED => in_array($newStatus, [
                self::ACTIVE,
                self::CANCELLED,
                self::EXPIRED,
            ]),
            self::INCOMPLETE => in_array($newStatus, [
                self::ACTIVE,
                self::CANCELLED,
            ]),
            default => false,
        };
    }
}