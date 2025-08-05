<?php

namespace App\Domain\Payment\Enums;

/**
 * Направления транзакций
 */
enum TransactionDirection: string
{
    case IN = 'in';
    case OUT = 'out';
    
    /**
     * Получить человекочитаемое название
     */
    public function label(): string
    {
        return match($this) {
            self::IN => 'Входящая',
            self::OUT => 'Исходящая',
        };
    }
    
    /**
     * Получить знак для отображения суммы
     */
    public function sign(): string
    {
        return match($this) {
            self::IN => '+',
            self::OUT => '-',
        };
    }
    
    /**
     * Получить цвет для направления
     */
    public function color(): string
    {
        return match($this) {
            self::IN => '#10B981',
            self::OUT => '#EF4444',
        };
    }
    
    /**
     * Получить иконку
     */
    public function icon(): string
    {
        return match($this) {
            self::IN => 'arrow-down',
            self::OUT => 'arrow-up',
        };
    }
}