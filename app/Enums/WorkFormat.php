<?php

namespace App\Enums;

/**
 * Форматы работы
 */
enum WorkFormat: string
{
    case INDIVIDUAL = 'individual';
    case SALON = 'salon';
    case DUO = 'duo';

    /**
     * Получить читаемое название формата работы
     */
    public function getLabel(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Индивидуально',
            self::SALON => 'Салон',
            self::DUO => 'В паре',
        };
    }

    /**
     * Получить описание формата
     */
    public function getDescription(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Работаю только индивидуально',
            self::SALON => 'Работаю в команде',
            self::DUO => 'Работаю в паре с другим специалистом',
        };
    }

    /**
     * Получить все форматы для выборки
     */
    public static function options(): array
    {
        $formats = [];
        foreach (self::cases() as $format) {
            $formats[$format->value] = $format->getLabel();
        }
        return $formats;
    }
}