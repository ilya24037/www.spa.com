<?php

namespace App\Enums;

/**
 * Типы отзывов
 */
enum ReviewType: string
{
    case SERVICE = 'service';         // Отзыв о услуге
    case MASTER = 'master';          // Отзыв о мастере
    case GENERAL = 'general';        // Общий отзыв
    case COMPLAINT = 'complaint';    // Жалоба

    /**
     * Получить название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::SERVICE => 'О услуге',
            self::MASTER => 'О мастере',
            self::GENERAL => 'Общий отзыв',
            self::COMPLAINT => 'Жалоба',
        };
    }

    /**
     * Получить иконку типа
     */
    public function getIcon(): string
    {
        return match($this) {
            self::SERVICE => '⭐',
            self::MASTER => '👤',
            self::GENERAL => '💬',
            self::COMPLAINT => '⚠️',
        };
    }

    /**
     * Получить цвет типа
     */
    public function getColor(): string
    {
        return match($this) {
            self::SERVICE => 'primary',
            self::MASTER => 'info',
            self::GENERAL => 'secondary',
            self::COMPLAINT => 'warning',
        };
    }

    /**
     * Получить приоритет модерации
     */
    public function getModerationPriority(): string
    {
        return match($this) {
            self::COMPLAINT => 'high',
            self::SERVICE, self::MASTER => 'medium',
            self::GENERAL => 'low',
        };
    }

    /**
     * Требует ли тип обязательную модерацию
     */
    public function requiresModeration(): bool
    {
        return match($this) {
            self::COMPLAINT => true,
            self::SERVICE, self::MASTER, self::GENERAL => false,
        };
    }

    /**
     * Получить минимальную длину отзыва
     */
    public function getMinLength(): int
    {
        return match($this) {
            self::SERVICE, self::MASTER => 10,
            self::GENERAL => 5,
            self::COMPLAINT => 20,
        };
    }

    /**
     * Получить максимальную длину отзыва
     */
    public function getMaxLength(): int
    {
        return match($this) {
            self::SERVICE, self::MASTER => 1000,
            self::GENERAL => 500,
            self::COMPLAINT => 2000,
        };
    }

    /**
     * Разрешены ли фото в отзыве
     */
    public function allowsPhotos(): bool
    {
        return match($this) {
            self::SERVICE, self::MASTER, self::GENERAL => true,
            self::COMPLAINT => false,
        };
    }

    /**
     * Обязательна ли оценка
     */
    public function requiresRating(): bool
    {
        return match($this) {
            self::SERVICE, self::MASTER => true,
            self::GENERAL, self::COMPLAINT => false,
        };
    }

    /**
     * Получить поля для проверки
     */
    public function getValidationRules(): array
    {
        $rules = [
            'comment' => "required|string|min:{$this->getMinLength()}|max:{$this->getMaxLength()}",
        ];

        if ($this->requiresRating()) {
            $rules['rating'] = 'required|integer|between:1,5';
        }

        if ($this->allowsPhotos()) {
            $rules['photos'] = 'array|max:5';
            $rules['photos.*'] = 'image|max:5120'; // 5MB
        }

        return $rules;
    }

    /**
     * Получить текст плейсхолдера
     */
    public function getPlaceholder(): string
    {
        return match($this) {
            self::SERVICE => 'Расскажите о качестве услуги, результате, впечатлениях...',
            self::MASTER => 'Опишите профессионализм мастера, общение, пунктуальность...',
            self::GENERAL => 'Поделитесь общими впечатлениями...',
            self::COMPLAINT => 'Опишите детально суть проблемы или нарушения...',
        };
    }
}