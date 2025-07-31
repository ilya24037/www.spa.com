<?php

namespace App\Enums;

/**
 * Статусы платежей
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';           // Ожидает обработки
    case PROCESSING = 'processing';     // В обработке
    case COMPLETED = 'completed';       // Завершен успешно
    case FAILED = 'failed';            // Неудачный
    case CANCELLED = 'cancelled';       // Отменен
    case REFUNDED = 'refunded';        // Возвращен
    case PARTIALLY_REFUNDED = 'partially_refunded'; // Частично возвращен
    case EXPIRED = 'expired';          // Истек срок
    case DISPUTED = 'disputed';        // Спорный
    case HELD = 'held';               // Заморожен

    /**
     * Получить читаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает оплаты',
            self::PROCESSING => 'В обработке',
            self::COMPLETED => 'Оплачено',
            self::FAILED => 'Ошибка оплаты',
            self::CANCELLED => 'Отменено',
            self::REFUNDED => 'Возвращено',
            self::PARTIALLY_REFUNDED => 'Частично возвращено',
            self::EXPIRED => 'Срок истек',
            self::DISPUTED => 'Спор',
            self::HELD => 'Заморожено',
        };
    }

    /**
     * Получить описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::PENDING => 'Платеж создан и ожидает поступления средств',
            self::PROCESSING => 'Платеж обрабатывается платежной системой',
            self::COMPLETED => 'Платеж успешно завершен, средства зачислены',
            self::FAILED => 'Произошла ошибка при обработке платежа',
            self::CANCELLED => 'Платеж отменен до завершения',
            self::REFUNDED => 'Платеж полностью возвращен покупателю',
            self::PARTIALLY_REFUNDED => 'Часть суммы возвращена покупателю',
            self::EXPIRED => 'Истек срок действия платежа',
            self::DISPUTED => 'По платежу открыт спор или чарджбэк',
            self::HELD => 'Платеж заморожен для проверки',
        };
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => '#F59E0B',      // amber
            self::PROCESSING => '#3B82F6',   // blue
            self::COMPLETED => '#10B981',    // green
            self::FAILED => '#EF4444',       // red
            self::CANCELLED => '#6B7280',    // gray
            self::REFUNDED => '#8B5CF6',     // purple
            self::PARTIALLY_REFUNDED => '#A855F7', // violet
            self::EXPIRED => '#9CA3AF',      // gray-400
            self::DISPUTED => '#DC2626',     // red-600
            self::HELD => '#D97706',         // amber-600
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::PROCESSING => '⚡',
            self::COMPLETED => '✅',
            self::FAILED => '❌',
            self::CANCELLED => '🚫',
            self::REFUNDED => '↩️',
            self::PARTIALLY_REFUNDED => '↪️',
            self::EXPIRED => '⏰',
            self::DISPUTED => '⚠️',
            self::HELD => '🔒',
        };
    }

    /**
     * Проверить, является ли статус финальным
     */
    public function isFinal(): bool
    {
        return match($this) {
            self::COMPLETED, 
            self::FAILED, 
            self::CANCELLED, 
            self::REFUNDED, 
            self::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Проверить, можно ли отменить платеж
     */
    public function isCancellable(): bool
    {
        return match($this) {
            self::PENDING, self::PROCESSING => true,
            default => false,
        };
    }

    /**
     * Проверить, можно ли вернуть платеж
     */
    public function isRefundable(): bool
    {
        return match($this) {
            self::COMPLETED, self::PARTIALLY_REFUNDED => true,
            default => false,
        };
    }

    /**
     * Проверить, успешен ли платеж
     */
    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Проверить, требует ли внимания
     */
    public function requiresAttention(): bool
    {
        return match($this) {
            self::FAILED, self::DISPUTED, self::HELD => true,
            default => false,
        };
    }

    /**
     * Получить возможные переходы статусов
     */
    public function getAvailableTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::PROCESSING, self::COMPLETED, self::FAILED, self::CANCELLED, self::EXPIRED],
            self::PROCESSING => [self::COMPLETED, self::FAILED, self::HELD],
            self::COMPLETED => [self::REFUNDED, self::PARTIALLY_REFUNDED, self::DISPUTED],
            self::FAILED => [self::PENDING], // Можно попробовать снова
            self::HELD => [self::COMPLETED, self::FAILED, self::CANCELLED],
            self::PARTIALLY_REFUNDED => [self::REFUNDED, self::DISPUTED],
            default => [], // Финальные статусы
        };
    }

    /**
     * Проверить, возможен ли переход к другому статусу
     */
    public function canTransitionTo(PaymentStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getAvailableTransitions());
    }

    /**
     * Получить временные рамки для статуса (в минутах)
     */
    public function getTimeoutMinutes(): ?int
    {
        return match($this) {
            self::PENDING => 30,     // 30 минут на оплату
            self::PROCESSING => 10,  // 10 минут на обработку
            self::HELD => 1440,      // 24 часа на проверку
            default => null,
        };
    }

    /**
     * Получить приоритет для сортировки (чем меньше, тем выше приоритет)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::DISPUTED => 1,
            self::FAILED => 2,
            self::HELD => 3,
            self::PROCESSING => 4,
            self::PENDING => 5,
            self::PARTIALLY_REFUNDED => 6,
            self::COMPLETED => 7,
            self::REFUNDED => 8,
            self::CANCELLED => 9,
            self::EXPIRED => 10,
        };
    }

    /**
     * Получить статусы, требующие автоматической обработки
     */
    public static function getAutomationRequired(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::HELD,
        ];
    }

    /**
     * Получить успешные статусы
     */
    public static function getSuccessfulStatuses(): array
    {
        return [
            self::COMPLETED,
            self::PARTIALLY_REFUNDED,
            self::REFUNDED,
        ];
    }

    /**
     * Получить неудачные статусы
     */
    public static function getFailedStatuses(): array
    {
        return [
            self::FAILED,
            self::CANCELLED,
            self::EXPIRED,
        ];
    }

    /**
     * Получить активные статусы
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::HELD,
        ];
    }

    /**
     * Все статусы для выборки
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $status) {
            $options[$status->value] = $status->getLabel();
        }
        return $options;
    }

    /**
     * Создать из строки с fallback
     */
    public static function tryFrom(string $value): ?self
    {
        try {
            return self::from($value);
        } catch (\ValueError) {
            return null;
        }
    }
}