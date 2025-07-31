<?php

namespace App\Enums;

/**
 * Единицы измерения цены
 */
enum PriceUnit: string
{
    case SERVICE = 'service';
    case HOUR = 'hour';
    case DAY = 'day';
    case MINUTE = 'minute';
    case UNIT = 'unit';
    case MONTH = 'month';

    /**
     * Получить читаемое название единицы
     */
    public function getLabel(): string
    {
        return match($this) {
            self::SERVICE => 'за услугу',
            self::HOUR => 'за час',
            self::DAY => 'за день',
            self::MINUTE => 'за минуту',
            self::UNIT => 'за единицу',
            self::MONTH => 'за месяц',
        };
    }

    /**
     * Получить короткое обозначение
     */
    public function getShortLabel(): string
    {
        return match($this) {
            self::SERVICE => '/услуга',
            self::HOUR => '/час',
            self::DAY => '/день',
            self::MINUTE => '/мин',
            self::UNIT => '/ед',
            self::MONTH => '/мес',
        };
    }

    /**
     * Получить все единицы для выборки
     */
    public static function options(): array
    {
        $units = [];
        foreach (self::cases() as $unit) {
            $units[$unit->value] = $unit->getLabel();
        }
        return $units;
    }
}