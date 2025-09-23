<?php

namespace App\Domain\Ad\Enums;

/**
 * Статусы объявлений
 * Доменная модель для управления жизненным циклом объявлений
 */
enum AdStatus: string
{
    case DRAFT = 'draft';
    case PENDING_MODERATION = 'pending_moderation';
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
            self::PENDING_MODERATION => 'На модерации',
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
            self::PENDING_MODERATION => '#3B82F6', // blue
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
            self::DRAFT, self::PENDING_MODERATION, self::WAITING_PAYMENT, self::ARCHIVED, self::REJECTED => true,
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
            self::DRAFT => [self::PENDING_MODERATION, self::ARCHIVED],
            self::PENDING_MODERATION => [self::ACTIVE, self::REJECTED, self::ARCHIVED],
            self::WAITING_PAYMENT => [self::ACTIVE, self::ARCHIVED, self::REJECTED],
            self::ACTIVE => [self::PENDING_MODERATION, self::ARCHIVED, self::EXPIRED, self::BLOCKED],
            self::ARCHIVED => [self::PENDING_MODERATION, self::DRAFT],
            self::EXPIRED => [self::PENDING_MODERATION, self::ARCHIVED],
            self::REJECTED => [self::DRAFT, self::ARCHIVED],
            self::BLOCKED => [self::ARCHIVED],
        };
    }

    /**
     * Проверить возможность перехода к статусу
     */
    public function canTransitionTo(AdStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getNextPossibleStatuses());
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
     * Получить статусы, доступные для пользователя
     */
    public static function userAccessibleStatuses(): array
    {
        return [
            self::DRAFT,
            self::WAITING_PAYMENT,
            self::ACTIVE,
            self::ARCHIVED,
            self::EXPIRED,
        ];
    }

    /**
     * Получить статусы, требующие действий администратора
     */
    public static function adminOnlyStatuses(): array
    {
        return [
            self::REJECTED,
            self::BLOCKED,
        ];
    }
}