<?php

namespace App\Enums;

/**
 * Статусы мастеров
 */
enum MasterStatus: string
{
    case DRAFT = 'draft';                   // Черновик профиля
    case PENDING = 'pending';               // Ожидает проверки
    case ACTIVE = 'active';                 // Активный, принимает заказы
    case INACTIVE = 'inactive';             // Неактивный, временно не работает
    case BLOCKED = 'blocked';               // Заблокирован администрацией
    case SUSPENDED = 'suspended';           // Приостановлен за нарушения
    case VACATION = 'vacation';             // В отпуске
    case ARCHIVED = 'archived';             // Архивирован

    /**
     * Получить читаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::PENDING => 'На проверке',
            self::ACTIVE => 'Активен',
            self::INACTIVE => 'Неактивен',
            self::BLOCKED => 'Заблокирован',
            self::SUSPENDED => 'Приостановлен',
            self::VACATION => 'В отпуске',
            self::ARCHIVED => 'В архиве',
        };
    }

    /**
     * Получить описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::DRAFT => 'Профиль еще не завершен и не опубликован',
            self::PENDING => 'Профиль ожидает проверки модератором',
            self::ACTIVE => 'Мастер активен и может принимать заказы',
            self::INACTIVE => 'Мастер временно не принимает заказы',
            self::BLOCKED => 'Профиль заблокирован администрацией',
            self::SUSPENDED => 'Профиль приостановлен за нарушение правил',
            self::VACATION => 'Мастер находится в отпуске',
            self::ARCHIVED => 'Профиль перемещен в архив',
        };
    }

    /**
     * Получить цвет статуса
     */
    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => '#6B7280',       // gray
            self::PENDING => '#F59E0B',     // amber
            self::ACTIVE => '#10B981',      // green
            self::INACTIVE => '#9CA3AF',    // gray-400
            self::BLOCKED => '#EF4444',     // red
            self::SUSPENDED => '#DC2626',   // red-600
            self::VACATION => '#3B82F6',    // blue
            self::ARCHIVED => '#4B5563',    // gray-600
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getIcon(): string
    {
        return match($this) {
            self::DRAFT => '📝',
            self::PENDING => '⏳',
            self::ACTIVE => '✅',
            self::INACTIVE => '⏸️',
            self::BLOCKED => '🚫',
            self::SUSPENDED => '⚠️',
            self::VACATION => '🏖️',
            self::ARCHIVED => '📦',
        };
    }

    /**
     * Проверить, активен ли статус
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Проверить, может ли мастер принимать заказы
     */
    public function canAcceptBookings(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Проверить, видим ли профиль публично
     */
    public function isPubliclyVisible(): bool
    {
        return match($this) {
            self::ACTIVE, self::VACATION => true,
            default => false,
        };
    }

    /**
     * Проверить, может ли мастер редактировать профиль
     */
    public function canEditProfile(): bool
    {
        return match($this) {
            self::BLOCKED, self::ARCHIVED => false,
            default => true,
        };
    }

    /**
     * Проверить, требует ли проверки модератором
     */
    public function requiresModeration(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Получить возможные переходы статусов
     */
    public function getNextPossibleStatuses(): array
    {
        return match($this) {
            self::DRAFT => [self::PENDING],
            self::PENDING => [self::ACTIVE, self::BLOCKED],
            self::ACTIVE => [self::INACTIVE, self::VACATION, self::BLOCKED, self::SUSPENDED],
            self::INACTIVE => [self::ACTIVE, self::ARCHIVED],
            self::BLOCKED => [self::ACTIVE, self::ARCHIVED],
            self::SUSPENDED => [self::ACTIVE, self::BLOCKED],
            self::VACATION => [self::ACTIVE],
            self::ARCHIVED => [self::DRAFT],
        };
    }

    /**
     * Проверить возможность перехода к статусу
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->getNextPossibleStatuses());
    }

    /**
     * Получить статусы для фильтрации
     */
    public static function getFilterStatuses(): array
    {
        return [
            'active' => [self::ACTIVE],
            'inactive' => [self::INACTIVE, self::VACATION],
            'blocked' => [self::BLOCKED, self::SUSPENDED],
            'pending' => [self::DRAFT, self::PENDING],
        ];
    }

    /**
     * Получить статус по умолчанию
     */
    public static function default(): self
    {
        return self::DRAFT;
    }

    /**
     * Получить публичные статусы
     */
    public static function getPublicStatuses(): array
    {
        return [self::ACTIVE, self::VACATION];
    }

    /**
     * Получить активные статусы
     */
    public static function getActiveStatuses(): array
    {
        return [self::ACTIVE];
    }

    /**
     * Получить заблокированные статусы
     */
    public static function getBlockedStatuses(): array
    {
        return [self::BLOCKED, self::SUSPENDED];
    }
}