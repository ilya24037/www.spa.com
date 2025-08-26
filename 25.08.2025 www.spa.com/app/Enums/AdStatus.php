<?php

namespace App\Enums;

/**
 * Статусы объявлений
 */
enum AdStatus: string
{
    case DRAFT = 'draft';
    case WAITING_PAYMENT = 'waiting_payment';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
    case EXPIRED = 'expired';
    case REJECTED = 'rejected';
    case BLOCKED = 'blocked';

    /**
     * Получить читаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::WAITING_PAYMENT => 'Ждет оплаты',
            self::ACTIVE => 'Активное',
            self::ARCHIVED => 'В архиве',
            self::EXPIRED => 'Истекло',
            self::REJECTED => 'Отклонено',
            self::BLOCKED => 'Заблокировано',
        };
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => '#6B7280',         // gray
            self::WAITING_PAYMENT => '#F59E0B', // amber
            self::ACTIVE => '#10B981',        // green
            self::ARCHIVED => '#6B7280',      // gray
            self::EXPIRED => '#EF4444',       // red
            self::REJECTED => '#EF4444',      // red
            self::BLOCKED => '#DC2626',       // red-600
        };
    }

    /**
     * Проверить, является ли статус публичным (видимым на сайте)
     */
    public function isPublic(): bool
    {
        return match($this) {
            self::ACTIVE => true,
            default => false,
        };
    }

    /**
     * Проверить, можно ли редактировать объявление с этим статусом
     */
    public function isEditable(): bool
    {
        return match($this) {
            self::DRAFT, self::WAITING_PAYMENT, self::ARCHIVED => true,
            default => false,
        };
    }

    /**
     * Проверить, можно ли удалить объявление с этим статусом
     */
    public function isDeletable(): bool
    {
        return match($this) {
            self::DRAFT, self::ARCHIVED => true,
            default => false,
        };
    }

    /**
     * Получить возможные следующие статусы
     */
    public function getNextPossibleStatuses(): array
    {
        return match($this) {
            self::DRAFT => [self::WAITING_PAYMENT, self::ARCHIVED],
            self::WAITING_PAYMENT => [self::ACTIVE, self::ARCHIVED, self::REJECTED],
            self::ACTIVE => [self::ARCHIVED, self::EXPIRED, self::BLOCKED],
            self::ARCHIVED => [self::DRAFT, self::WAITING_PAYMENT],
            self::EXPIRED => [self::WAITING_PAYMENT, self::ARCHIVED],
            self::REJECTED => [self::DRAFT, self::ARCHIVED],
            self::BLOCKED => [self::ARCHIVED],
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
}