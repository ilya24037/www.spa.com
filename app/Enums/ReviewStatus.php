<?php

namespace App\Enums;

/**
 * Статусы отзывов
 */
enum ReviewStatus: string
{
    case PENDING = 'pending';         // Ожидает модерации
    case APPROVED = 'approved';       // Одобрен
    case REJECTED = 'rejected';       // Отклонен
    case HIDDEN = 'hidden';          // Скрыт администратором
    case FLAGGED = 'flagged';        // Помечен как нарушение

    /**
     * Получить лейбл статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'На модерации',
            self::APPROVED => 'Одобрен',
            self::REJECTED => 'Отклонен',
            self::HIDDEN => 'Скрыт',
            self::FLAGGED => 'Нарушение',
        };
    }

    /**
     * Получить цвет статуса
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'error',
            self::HIDDEN => 'secondary',
            self::FLAGGED => 'error',
        };
    }

    /**
     * Получить описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::PENDING => 'Отзыв ожидает проверки модератором',
            self::APPROVED => 'Отзыв прошел модерацию и опубликован',
            self::REJECTED => 'Отзыв не прошел модерацию',
            self::HIDDEN => 'Отзыв скрыт администратором',
            self::FLAGGED => 'Отзыв помечен как нарушающий правила',
        };
    }

    /**
     * Проверить, виден ли отзыв публично
     */
    public function isPublic(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Проверить, можно ли редактировать отзыв
     */
    public function canBeEdited(): bool
    {
        return in_array($this, [self::PENDING, self::REJECTED]);
    }

    /**
     * Проверить, можно ли ответить на отзыв
     */
    public function canBeReplied(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Проверить, требует ли статус действий модератора
     */
    public function requiresModeration(): bool
    {
        return in_array($this, [self::PENDING, self::FLAGGED]);
    }

    /**
     * Получить следующие возможные статусы
     */
    public function getNextStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::APPROVED, self::REJECTED],
            self::APPROVED => [self::HIDDEN, self::FLAGGED],
            self::REJECTED => [self::APPROVED, self::HIDDEN],
            self::HIDDEN => [self::APPROVED],
            self::FLAGGED => [self::APPROVED, self::REJECTED, self::HIDDEN],
        };
    }

    /**
     * Статусы для публичного отображения
     */
    public static function getPublicStatuses(): array
    {
        return [self::APPROVED];
    }

    /**
     * Статусы для модерации
     */
    public static function getModerationStatuses(): array
    {
        return [self::PENDING, self::FLAGGED];
    }
}