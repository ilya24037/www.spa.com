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
     * Получить детальное описание способа оплаты
     */
    public function getDescription(): string
    {
        return match($this) {
            self::CASH => 'Оплата наличными при встрече с мастером',
            self::TRANSFER => 'Банковский перевод по реквизитам',
            self::CARD => 'Оплата банковской картой через платежный шлюз',
            self::ELECTRONIC => 'Электронные кошельки: ЮMoney, QIWI, WebMoney',
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
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::CASH => '#059669',     // green-600
            self::TRANSFER => '#374151', // gray-700
            self::CARD => '#1F2937',     // gray-800
            self::ELECTRONIC => '#7C3AED', // violet-600
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
     * Проверить, поддерживает ли мгновенную оплату
     */
    public function isInstant(): bool
    {
        return match($this) {
            self::CARD, self::ELECTRONIC => true,
            self::CASH, self::TRANSFER => false,
        };
    }

    /**
     * Проверить, требует ли подтверждения оплаты
     */
    public function requiresConfirmation(): bool
    {
        return match($this) {
            self::CASH, self::TRANSFER => true,
            self::CARD, self::ELECTRONIC => false,
        };
    }

    /**
     * Проверить, поддерживает ли возврат средств
     */
    public function supportsRefund(): bool
    {
        return match($this) {
            self::CARD, self::ELECTRONIC, self::TRANSFER => true,
            self::CASH => false,
        };
    }

    /**
     * Получить минимальную сумму платежа
     */
    public function getMinAmount(): float
    {
        return match($this) {
            self::CARD, self::ELECTRONIC => 50.0,
            self::TRANSFER => 100.0,
            self::CASH => 0.0,
        };
    }

    /**
     * Получить максимальную сумму платежа
     */
    public function getMaxAmount(): float
    {
        return match($this) {
            self::CARD => 300000.0,       // 300k рублей
            self::ELECTRONIC => 100000.0, // 100k рублей
            self::TRANSFER => 1000000.0,  // 1M рублей
            self::CASH => 50000.0,        // 50k рублей
        };
    }

    /**
     * Получить комиссию (в процентах)
     */
    public function getFeePercentage(): float
    {
        return match($this) {
            self::CARD => 2.9,
            self::ELECTRONIC => 3.0,
            self::TRANSFER => 0.0,
            self::CASH => 0.0,
        };
    }

    /**
     * Получить время обработки платежа (в минутах)
     */
    public function getProcessingTime(): int
    {
        return match($this) {
            self::CARD => 1,
            self::ELECTRONIC => 5,
            self::TRANSFER => 1440, // 24 часа
            self::CASH => 0,        // Мгновенно при встрече
        };
    }

    /**
     * Получить приоритет отображения (чем меньше, тем выше)
     */
    public function getDisplayPriority(): int
    {
        return match($this) {
            self::CARD => 1,
            self::ELECTRONIC => 2,
            self::CASH => 3,
            self::TRANSFER => 4,
        };
    }

    /**
     * Проверить, доступен ли способ для суммы
     */
    public function isAvailableForAmount(float $amount): bool
    {
        return $amount >= $this->getMinAmount() && $amount <= $this->getMaxAmount();
    }

    /**
     * Вычислить комиссию для суммы
     */
    public function calculateFee(float $amount): float
    {
        return ($amount * $this->getFeePercentage()) / 100;
    }

    /**
     * Получить итоговую сумму с комиссией
     */
    public function getTotalWithFee(float $amount): float
    {
        return $amount + $this->calculateFee($amount);
    }

    /**
     * Получить популярные способы оплаты
     */
    public static function getPopular(): array
    {
        return [self::CARD, self::CASH, self::ELECTRONIC];
    }

    /**
     * Получить мгновенные способы оплаты
     */
    public static function getInstantMethods(): array
    {
        return array_filter(self::cases(), fn($method) => $method->isInstant());
    }

    /**
     * Получить способы без комиссии
     */
    public static function getZeroFeeMethods(): array
    {
        return array_filter(self::cases(), fn($method) => $method->getFeePercentage() === 0.0);
    }

    /**
     * Получить способы, доступные для суммы
     */
    public static function getAvailableForAmount(float $amount): array
    {
        return array_filter(self::cases(), fn($method) => $method->isAvailableForAmount($amount));
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