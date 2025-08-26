<?php

namespace App\Domain\Payment\Enums;

/**
 * Интервалы подписки
 */
enum SubscriptionInterval: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case YEAR = 'year';
    case LIFETIME = 'lifetime';
    
    /**
     * Получить человекочитаемое название
     */
    public function label(): string
    {
        return match($this) {
            self::DAY => 'День',
            self::WEEK => 'Неделя',
            self::MONTH => 'Месяц',
            self::QUARTER => 'Квартал',
            self::YEAR => 'Год',
            self::LIFETIME => 'Пожизненно',
        };
    }
    
    /**
     * Получить название для периода с количеством
     */
    public function labelWithCount(int $count): string
    {
        if ($count === 1) {
            return match($this) {
                self::DAY => 'Ежедневно',
                self::WEEK => 'Еженедельно',
                self::MONTH => 'Ежемесячно',
                self::QUARTER => 'Ежеквартально',
                self::YEAR => 'Ежегодно',
                self::LIFETIME => 'Пожизненно',
            };
        }
        
        return match($this) {
            self::DAY => "Каждые $count дня",
            self::WEEK => "Каждые $count недели",
            self::MONTH => "Каждые $count месяца",
            self::QUARTER => "Каждые $count квартала",
            self::YEAR => "Каждые $count года",
            self::LIFETIME => 'Пожизненно',
        };
    }
    
    /**
     * Получить количество дней в интервале
     */
    public function days(): ?int
    {
        return match($this) {
            self::DAY => 1,
            self::WEEK => 7,
            self::MONTH => 30,
            self::QUARTER => 90,
            self::YEAR => 365,
            self::LIFETIME => null,
        };
    }
    
    /**
     * Получить количество месяцев в интервале
     */
    public function months(): ?int
    {
        return match($this) {
            self::DAY => 0,
            self::WEEK => 0,
            self::MONTH => 1,
            self::QUARTER => 3,
            self::YEAR => 12,
            self::LIFETIME => null,
        };
    }
    
    /**
     * Проверка, является ли интервал повторяющимся
     */
    public function isRecurring(): bool
    {
        return $this !== self::LIFETIME;
    }
    
    /**
     * Получить сортировочный вес
     */
    public function sortOrder(): int
    {
        return match($this) {
            self::DAY => 1,
            self::WEEK => 2,
            self::MONTH => 3,
            self::QUARTER => 4,
            self::YEAR => 5,
            self::LIFETIME => 6,
        };
    }
}