<?php

namespace App\Enums;

/**
 * Роли пользователей в системе
 */
enum UserRole: string
{
    case CLIENT = 'client';
    case MASTER = 'master';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';

    /**
     * Получить читаемое название роли
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CLIENT => 'Клиент',
            self::MASTER => 'Мастер',
            self::ADMIN => 'Администратор',
            self::MODERATOR => 'Модератор',
        };
    }

    /**
     * Получить описание роли
     */
    public function getDescription(): string
    {
        return match($this) {
            self::CLIENT => 'Пользователь, который ищет и заказывает услуги',
            self::MASTER => 'Специалист, который предоставляет услуги',
            self::ADMIN => 'Администратор системы с полными правами',
            self::MODERATOR => 'Модератор контента и объявлений',
        };
    }

    /**
     * Получить разрешения роли
     */
    public function getPermissions(): array
    {
        return match($this) {
            self::CLIENT => [
                'view_ads',
                'create_bookings',
                'write_reviews',
                'manage_favorites',
                'update_profile',
            ],
            self::MASTER => [
                'view_ads',
                'create_ads',
                'manage_ads',
                'accept_bookings',
                'manage_services',
                'view_earnings',
                'update_profile',
            ],
            self::ADMIN => [
                'manage_users',
                'manage_ads',
                'manage_bookings',
                'view_analytics',
                'manage_system',
                'moderate_content',
                'manage_payments',
                'export_data',
            ],
            self::MODERATOR => [
                'moderate_ads',
                'moderate_reviews',
                'view_reports',
                'ban_users',
                'manage_complaints',
            ],
        };
    }

    /**
     * Проверить есть ли у роли определённое разрешение
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Проверить является ли роль административной
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::MODERATOR]);
    }

    /**
     * Проверить может ли роль создавать объявления
     */
    public function canCreateAds(): bool
    {
        return $this === self::MASTER;
    }

    /**
     * Проверить может ли роль создавать бронирования
     */
    public function canCreateBookings(): bool
    {
        return $this === self::CLIENT;
    }

    /**
     * Получить цвет роли для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::CLIENT => '#3B82F6',     // blue
            self::MASTER => '#10B981',     // green
            self::ADMIN => '#DC2626',      // red
            self::MODERATOR => '#F59E0B',  // amber
        };
    }

    /**
     * Получить иконку роли
     */
    public function getIcon(): string
    {
        return match($this) {
            self::CLIENT => '👤',
            self::MASTER => '⭐',
            self::ADMIN => '🔧',
            self::MODERATOR => '🛡️',
        };
    }

    /**
     * Получить доступные роли для регистрации
     */
    public static function getRegistrationRoles(): array
    {
        return [
            self::CLIENT->value => self::CLIENT->getLabel(),
            self::MASTER->value => self::MASTER->getLabel(),
        ];
    }

    /**
     * Получить все роли для выборки
     */
    public static function options(): array
    {
        $roles = [];
        foreach (self::cases() as $role) {
            $roles[$role->value] = $role->getLabel();
        }
        return $roles;
    }

    /**
     * Получить роль по умолчанию для новых пользователей
     */
    public static function default(): self
    {
        return self::CLIENT;
    }
}