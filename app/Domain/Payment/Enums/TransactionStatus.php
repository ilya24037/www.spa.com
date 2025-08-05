<?php

namespace App\Domain\Payment\Enums;

/**
 * Статусы транзакций
 */
enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case REFUNDED = 'refunded';
    
    /**
     * Получить человекочитаемое название
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает',
            self::PROCESSING => 'В обработке',
            self::SUCCESS => 'Успешно',
            self::FAILED => 'Ошибка',
            self::CANCELLED => 'Отменено',
            self::REVERSED => 'Возвращено',
            self::PARTIALLY_REFUNDED => 'Частично возвращено',
            self::REFUNDED => 'Возвращено',
        };
    }
    
    /**
     * Получить цвет статуса
     */
    public function color(): string
    {
        return match($this) {
            self::SUCCESS => '#10B981',
            self::PENDING => '#F59E0B',
            self::PROCESSING => '#3B82F6',
            self::FAILED => '#EF4444',
            self::CANCELLED => '#6B7280',
            self::REVERSED => '#8B5CF6',
            self::PARTIALLY_REFUNDED => '#F59E0B',
            self::REFUNDED => '#10B981',
        };
    }
    
    /**
     * Проверка финальности статуса
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::SUCCESS,
            self::FAILED,
            self::CANCELLED,
            self::REVERSED,
            self::REFUNDED,
        ]);
    }
    
    /**
     * Проверка успешности статуса
     */
    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }
    
    /**
     * Возможные переходы статусов
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [
                self::PROCESSING,
                self::SUCCESS,
                self::FAILED,
                self::CANCELLED,
            ]),
            self::PROCESSING => in_array($newStatus, [
                self::SUCCESS,
                self::FAILED,
                self::CANCELLED,
            ]),
            self::SUCCESS => in_array($newStatus, [
                self::REVERSED,
                self::PARTIALLY_REFUNDED,
                self::REFUNDED,
            ]),
            self::PARTIALLY_REFUNDED => in_array($newStatus, [
                self::REFUNDED,
            ]),
            default => false,
        };
    }
}