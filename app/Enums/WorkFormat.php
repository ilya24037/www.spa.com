<?php

namespace App\Enums;

/**
 * Форматы работы
 */
enum WorkFormat: string
{
    case INDIVIDUAL = 'individual';
    case DUO = 'duo';
    case GROUP = 'group';

    /**
     * Получить читаемое название формата работы
     */
    public function getLabel(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Индивидуально',
            self::DUO => 'В паре',
            self::GROUP => 'Групповые услуги',
        };
    }

    /**
     * Получить описание формата
     */
    public function getDescription(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'Работаю только индивидуально с одним клиентом',
            self::DUO => 'Работаю в паре с другим специалистом',
            self::GROUP => 'Провожу групповые сеансы для нескольких клиентов',
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