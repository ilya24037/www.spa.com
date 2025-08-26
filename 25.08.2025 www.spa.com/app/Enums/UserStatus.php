<?php

namespace App\Enums;

/**
 * Статусы пользователей в системе
 */
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
    case DELETED = 'deleted';

    /**
     * Получить читаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Активный',
            self::INACTIVE => 'Неактивный',
            self::PENDING => 'Ожидает подтверждения',
            self::SUSPENDED => 'Заблокирован',
            self::BANNED => 'Забанен',
            self::DELETED => 'Удален',
        };
    }

    /**
     * Получить описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::ACTIVE => 'Пользователь активен и может пользоваться всеми функциями',
            self::INACTIVE => 'Пользователь неактивен, возможно не подтвердил email',
            self::PENDING => 'Аккаунт ожидает подтверждения администратором',
            self::SUSPENDED => 'Пользователь временно заблокирован',
            self::BANNED => 'Пользователь заблокирован навсегда',
            self::DELETED => 'Аккаунт пользователя удален',
        };
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => '#10B981',      // green
            self::INACTIVE => '#6B7280',    // gray
            self::PENDING => '#F59E0B',     // amber
            self::SUSPENDED => '#EF4444',   // red
            self::BANNED => '#DC2626',      // red-600
            self::DELETED => '#4B5563',     // gray-600
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ACTIVE => '✅',
            self::INACTIVE => '⏸️',
            self::PENDING => '⏳',
            self::SUSPENDED => '🚫',
            self::BANNED => '❌',
            self::DELETED => '🗑️',
        };
    }

    /**
     * Проверить может ли пользователь входить в систему
     */
    public function canLogin(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            default => false,
        };
    }

    /**
     * Проверить может ли пользователь создавать контент
     */
    public function canCreateContent(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            default => false,
        };
    }

    /**
     * Проверить может ли пользователь получать уведомления
     */
    public function canReceiveNotifications(): bool
    {
        return match($this) {
            self::ACTIVE, self::INACTIVE => true,
            default => false,
        };
    }

    /**
     * Проверить является ли статус заблокированным
     */
    public function isBlocked(): bool
    {
        return match($this) {
            self::SUSPENDED, self::BANNED => true,
            default => false,
        };
    }

    /**
     * Проверить является ли статус удаленным
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * Проверить нужна ли активация аккаунта
     */
    public function needsActivation(): bool
    {
        return match($this) {
            self::PENDING, self::INACTIVE => true,
            default => false,
        };
    }

    /**
     * Получить возможные следующие статусы
     */
    public function getNextPossibleStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::ACTIVE, self::SUSPENDED, self::BANNED],
            self::INACTIVE => [self::ACTIVE, self::SUSPENDED, self::BANNED],
            self::ACTIVE => [self::INACTIVE, self::SUSPENDED, self::BANNED, self::DELETED],
            self::SUSPENDED => [self::ACTIVE, self::BANNED, self::DELETED],
            self::BANNED => [self::ACTIVE, self::DELETED],
            self::DELETED => [], // Нельзя изменить статус удаленного пользователя
        };
    }

    /**
     * Получить все статусы для выборки
     */
    public static function options(): array
    {
        $statuses = [];
        foreach (self::cases() as $status) {
            $statuses[$status->value] = $status->getLabel();
        }
        return $statuses;
    }

    /**
     * Получить статус по умолчанию для новых пользователей
     */
    public static function default(): self
    {
        return self::PENDING; // Требует подтверждения email
    }

    /**
     * Получить активные статусы (для фильтрации)
     */
    public static function activeStatuses(): array
    {
        return [self::ACTIVE, self::INACTIVE];
    }

    /**
     * Получить заблокированные статусы (для фильтрации)
     */
    public static function blockedStatuses(): array
    {
        return [self::SUSPENDED, self::BANNED];
    }
}