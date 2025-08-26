<?php

namespace App\Enums;

/**
 * Статусы уведомлений
 */
enum NotificationStatus: string
{
    case PENDING = 'pending';      // Ожидает отправки
    case SENT = 'sent';           // Отправлено
    case DELIVERED = 'delivered';  // Доставлено
    case READ = 'read';           // Прочитано
    case FAILED = 'failed';       // Ошибка отправки
    case CANCELLED = 'cancelled'; // Отменено

    /**
     * Получить лейбл статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает отправки',
            self::SENT => 'Отправлено',
            self::DELIVERED => 'Доставлено',
            self::READ => 'Прочитано',
            self::FAILED => 'Ошибка отправки',
            self::CANCELLED => 'Отменено',
        };
    }

    /**
     * Получить цвет статуса
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::SENT => 'info',
            self::DELIVERED => 'success',
            self::READ => 'success',
            self::FAILED => 'error',
            self::CANCELLED => 'secondary',
        };
    }

    /**
     * Проверить, является ли статус финальным
     */
    public function isFinal(): bool
    {
        return match($this) {
            self::READ, 
            self::FAILED, 
            self::CANCELLED => true,
            
            default => false,
        };
    }

    /**
     * Проверить, можно ли повторить отправку
     */
    public function canRetry(): bool
    {
        return match($this) {
            self::FAILED, 
            self::PENDING => true,
            
            default => false,
        };
    }

    /**
     * Получить следующий возможный статус
     */
    public function getNextStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::SENT, self::FAILED, self::CANCELLED],
            self::SENT => [self::DELIVERED, self::FAILED],
            self::DELIVERED => [self::READ],
            self::FAILED => [self::SENT, self::CANCELLED],
            default => [],
        };
    }

    /**
     * Статусы для фильтрации
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::PENDING,
            self::SENT,
            self::DELIVERED,
        ];
    }

    /**
     * Статусы ошибок
     */
    public static function getErrorStatuses(): array
    {
        return [
            self::FAILED,
            self::CANCELLED,
        ];
    }

    /**
     * Статусы успешной доставки
     */
    public static function getSuccessStatuses(): array
    {
        return [
            self::DELIVERED,
            self::READ,
        ];
    }
}