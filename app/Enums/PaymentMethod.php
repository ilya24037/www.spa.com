<?php

namespace App\Enums;

/**
 * Способы оплаты
 */
enum PaymentMethod: string
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case CARD = 'card';
    case ELECTRONIC = 'electronic';

    /**
     * Получить читаемое название способа оплаты
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CASH => 'Наличные',
            self::TRANSFER => 'Перевод',
            self::CARD => 'Банковская карта',
            self::ELECTRONIC => 'Электронные деньги',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::CASH => '💵',
            self::TRANSFER => '🏦',
            self::CARD => '💳',
            self::ELECTRONIC => '💰',
        };
    }

    /**
     * Проверить, требует ли способ предоплату
     */
    public function requiresPrepayment(): bool
    {
        return match($this) {
            self::TRANSFER, self::CARD, self::ELECTRONIC => true,
            self::CASH => false,
        };
    }

    /**
     * Получить все способы оплаты для выборки
     */
    public static function options(): array
    {
        $methods = [];
        foreach (self::cases() as $method) {
            $methods[$method->value] = $method->getLabel();
        }
        return $methods;
    }
}