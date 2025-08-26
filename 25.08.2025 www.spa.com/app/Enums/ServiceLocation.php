<?php

namespace App\Enums;

/**
 * Места оказания услуг
 */
enum ServiceLocation: string
{
    case HOME = 'home';
    case SALON = 'salon';
    case BOTH = 'both';
    case OUTCALL = 'outcall';

    /**
     * Получить читаемое название места услуг
     */
    public function getLabel(): string
    {
        return match($this) {
            self::HOME => 'У себя',
            self::SALON => 'В салоне',
            self::BOTH => 'У себя и в салоне',
            self::OUTCALL => 'С выездом',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::HOME => '🏠',
            self::SALON => '🏢',
            self::BOTH => '🏠🏢',
            self::OUTCALL => '🚗',
        };
    }

    /**
     * Проверить, требует ли место указание адреса
     */
    public function requiresAddress(): bool
    {
        return match($this) {
            self::HOME, self::SALON, self::BOTH => true,
            self::OUTCALL => false,
        };
    }

    /**
     * Получить все места для выборки
     */
    public static function options(): array
    {
        $locations = [];
        foreach (self::cases() as $location) {
            $locations[$location->value] = $location->getLabel();
        }
        return $locations;
    }
}