<?php

namespace App\Domain\Payment\Enums;

/**
 * Типы транзакций
 */
enum TransactionType: string
{
    case PAYMENT = 'payment';
    case PAYOUT = 'payout';
    case REFUND = 'refund';
    case COMMISSION = 'commission';
    case TRANSFER = 'transfer';
    case ADJUSTMENT = 'adjustment';
    case SUBSCRIPTION = 'subscription';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    
    /**
     * Получить человекочитаемое название
     */
    public function label(): string
    {
        return match($this) {
            self::PAYMENT => 'Платеж',
            self::PAYOUT => 'Выплата',
            self::REFUND => 'Возврат',
            self::COMMISSION => 'Комиссия',
            self::TRANSFER => 'Перевод',
            self::ADJUSTMENT => 'Корректировка',
            self::SUBSCRIPTION => 'Подписка',
            self::DEPOSIT => 'Пополнение',
            self::WITHDRAWAL => 'Вывод средств',
        };
    }
    
    /**
     * Получить иконку для типа
     */
    public function icon(): string
    {
        return match($this) {
            self::PAYMENT => 'credit-card',
            self::PAYOUT => 'arrow-up-circle',
            self::REFUND => 'arrow-down-circle',
            self::COMMISSION => 'percent',
            self::TRANSFER => 'arrow-right-circle',
            self::ADJUSTMENT => 'settings',
            self::SUBSCRIPTION => 'refresh',
            self::DEPOSIT => 'plus-circle',
            self::WITHDRAWAL => 'minus-circle',
        };
    }
    
    /**
     * Получить цвет для типа
     */
    public function color(): string
    {
        return match($this) {
            self::PAYMENT => '#10B981',
            self::PAYOUT => '#3B82F6',
            self::REFUND => '#F59E0B',
            self::COMMISSION => '#8B5CF6',
            self::TRANSFER => '#06B6D4',
            self::ADJUSTMENT => '#6B7280',
            self::SUBSCRIPTION => '#10B981',
            self::DEPOSIT => '#10B981',
            self::WITHDRAWAL => '#EF4444',
        };
    }
    
    /**
     * Проверка, является ли тип доходом
     */
    public function isIncome(): bool
    {
        return in_array($this, [
            self::PAYMENT,
            self::DEPOSIT,
            self::REFUND,
        ]);
    }
    
    /**
     * Проверка, является ли тип расходом
     */
    public function isExpense(): bool
    {
        return in_array($this, [
            self::PAYOUT,
            self::COMMISSION,
            self::WITHDRAWAL,
        ]);
    }
}