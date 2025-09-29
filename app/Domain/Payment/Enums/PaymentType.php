<?php

namespace App\Domain\Payment\Enums;

/**
 * Типы платежей
 */
enum PaymentType: string
{
    case SERVICE_PAYMENT = 'service_payment';   // Оплата услуги
    case BOOKING_DEPOSIT = 'booking_deposit';   // Депозит за бронирование
    case SUBSCRIPTION = 'subscription';         // Подписка
    case COMMISSION = 'commission';             // Комиссия
    case REFUND = 'refund';                    // Возврат
    case WITHDRAWAL = 'withdrawal';             // Вывод средств
    case TOP_UP = 'top_up';                    // Пополнение баланса
    case PENALTY = 'penalty';                  // Штраф
    case BONUS = 'bonus';                      // Бонус
    case PROMOTION = 'promotion';              // Промо-платеж
    case AD_PLACEMENT = 'ad_placement';        // Размещение объявления

    /**
     * Получить читаемое название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::SERVICE_PAYMENT => 'Оплата услуги',
            self::BOOKING_DEPOSIT => 'Депозит за бронирование',
            self::SUBSCRIPTION => 'Подписка',
            self::COMMISSION => 'Комиссия',
            self::REFUND => 'Возврат средств',
            self::WITHDRAWAL => 'Вывод средств',
            self::TOP_UP => 'Пополнение баланса',
            self::PENALTY => 'Штраф',
            self::BONUS => 'Бонус',
            self::PROMOTION => 'Промо-платеж',
            self::AD_PLACEMENT => 'Размещение объявления',
        };
    }

    /**
     * Получить описание типа
     */
    public function getDescription(): string
    {
        return match($this) {
            self::SERVICE_PAYMENT => 'Основная оплата за оказанную услугу',
            self::BOOKING_DEPOSIT => 'Предварительный депозит за бронирование времени',
            self::SUBSCRIPTION => 'Ежемесячная подписка на премиум услуги',
            self::COMMISSION => 'Комиссия платформы с выполненной услуги',
            self::REFUND => 'Возврат денежных средств клиенту',
            self::WITHDRAWAL => 'Вывод заработанных средств мастером',
            self::TOP_UP => 'Пополнение внутреннего баланса аккаунта',
            self::PENALTY => 'Штрафные санкции за нарушения',
            self::BONUS => 'Бонусные начисления за активность',
            self::PROMOTION => 'Платеж по промо-акции или скидке',
            self::AD_PLACEMENT => 'Оплата за размещение объявления на платформе',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::SERVICE_PAYMENT => '💳',
            self::BOOKING_DEPOSIT => '🏦',
            self::SUBSCRIPTION => '🔄',
            self::COMMISSION => '💼',
            self::REFUND => '↩️',
            self::WITHDRAWAL => '💸',
            self::TOP_UP => '💰',
            self::PENALTY => '⚠️',
            self::BONUS => '🎁',
            self::PROMOTION => '🎉',
            self::AD_PLACEMENT => '📢',
        };
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::SERVICE_PAYMENT => '#10B981',    // green
            self::BOOKING_DEPOSIT => '#3B82F6',    // blue
            self::SUBSCRIPTION => '#8B5CF6',       // violet
            self::COMMISSION => '#F59E0B',         // amber
            self::REFUND => '#EF4444',             // red
            self::WITHDRAWAL => '#EC4899',         // pink
            self::TOP_UP => '#059669',             // green-600
            self::PENALTY => '#DC2626',            // red-600
            self::BONUS => '#7C3AED',              // violet-600
            self::PROMOTION => '#F97316',          // orange
            self::AD_PLACEMENT => '#0F766E',       // teal-600
        };
    }

    /**
     * Проверить, является ли тип доходом
     */
    public function isIncome(): bool
    {
        return match($this) {
            self::SERVICE_PAYMENT, 
            self::BOOKING_DEPOSIT, 
            self::SUBSCRIPTION,
            self::TOP_UP,
            self::BONUS,
            self::PROMOTION,
            self::AD_PLACEMENT => true,
            default => false,
        };
    }

    /**
     * Проверить, является ли тип расходом
     */
    public function isExpense(): bool
    {
        return match($this) {
            self::COMMISSION, 
            self::REFUND, 
            self::WITHDRAWAL, 
            self::PENALTY => true,
            default => false,
        };
    }

    /**
     * Проверить, требует ли подтверждения
     */
    public function requiresApproval(): bool
    {
        return match($this) {
            self::WITHDRAWAL, self::REFUND, self::PENALTY => true,
            default => false,
        };
    }

    /**
     * Проверить, автоматический ли платеж
     */
    public function isAutomatic(): bool
    {
        return match($this) {
            self::COMMISSION, self::SUBSCRIPTION, self::BONUS => true,
            default => false,
        };
    }

    /**
     * Получить категорию для отчетности
     */
    public function getCategory(): string
    {
        return match($this) {
            self::SERVICE_PAYMENT, self::BOOKING_DEPOSIT => 'Услуги',
            self::SUBSCRIPTION => 'Подписки',
            self::COMMISSION => 'Комиссии',
            self::REFUND => 'Возвраты',
            self::WITHDRAWAL => 'Выводы',
            self::TOP_UP => 'Пополнения',
            self::PENALTY => 'Штрафы',
            self::BONUS => 'Бонусы',
            self::PROMOTION => 'Промо',
        };
    }

    /**
     * Получить налоговую категорию
     */
    public function getTaxCategory(): string
    {
        return match($this) {
            self::SERVICE_PAYMENT, self::BOOKING_DEPOSIT => 'income_service',
            self::COMMISSION => 'expense_commission',
            self::SUBSCRIPTION => 'expense_subscription',
            self::BONUS => 'income_bonus',
            default => 'other',
        };
    }

    /**
     * Получить приоритет для обработки (чем меньше, тем выше)
     */
    public function getProcessingPriority(): int
    {
        return match($this) {
            self::REFUND => 1,              // Высший приоритет
            self::WITHDRAWAL => 2,
            self::SERVICE_PAYMENT => 3,
            self::BOOKING_DEPOSIT => 4,
            self::PENALTY => 5,
            self::COMMISSION => 6,
            self::SUBSCRIPTION => 7,
            self::TOP_UP => 8,
            self::BONUS => 9,
            self::PROMOTION => 10,          // Низший приоритет
        };
    }

    /**
     * Получить типичный период обработки (в часах)
     */
    public function getProcessingTimeHours(): int
    {
        return match($this) {
            self::SERVICE_PAYMENT, self::TOP_UP => 0,        // Мгновенно
            self::BOOKING_DEPOSIT, self::BONUS => 1,         // 1 час
            self::COMMISSION, self::SUBSCRIPTION => 24,      // 1 день
            self::WITHDRAWAL => 72,                          // 3 дня
            self::REFUND => 168,                            // 7 дней
            self::PENALTY, self::PROMOTION => 48,           // 2 дня
        };
    }

    /**
     * Получить минимальную сумму для типа
     */
    public function getMinAmount(): float
    {
        return match($this) {
            self::SERVICE_PAYMENT, self::BOOKING_DEPOSIT => 100.0,
            self::SUBSCRIPTION => 299.0,
            self::WITHDRAWAL => 500.0,
            self::TOP_UP => 50.0,
            self::COMMISSION => 1.0,
            self::REFUND => 1.0,
            self::PENALTY => 100.0,
            self::BONUS => 10.0,
            self::PROMOTION => 1.0,
        };
    }

    /**
     * Получить максимальную сумму для типа
     */
    public function getMaxAmount(): float
    {
        return match($this) {
            self::SERVICE_PAYMENT => 100000.0,    // 100k
            self::BOOKING_DEPOSIT => 50000.0,     // 50k
            self::SUBSCRIPTION => 9999.0,         // 10k
            self::WITHDRAWAL => 500000.0,         // 500k
            self::TOP_UP => 100000.0,             // 100k
            self::COMMISSION => 50000.0,          // 50k
            self::REFUND => 100000.0,             // 100k
            self::PENALTY => 25000.0,             // 25k
            self::BONUS => 10000.0,               // 10k
            self::PROMOTION => 50000.0,           // 50k
        };
    }

    /**
     * Проверить доступность для суммы
     */
    public function isAvailableForAmount(float $amount): bool
    {
        return $amount >= $this->getMinAmount() && $amount <= $this->getMaxAmount();
    }

    /**
     * Получить доступные способы оплаты для типа
     */
    public function getAvailablePaymentMethods(): array
    {
        return match($this) {
            self::SERVICE_PAYMENT, self::BOOKING_DEPOSIT => [
                PaymentMethod::CARD, 
                PaymentMethod::ELECTRONIC, 
                PaymentMethod::CASH
            ],
            self::SUBSCRIPTION, self::TOP_UP => [
                PaymentMethod::CARD, 
                PaymentMethod::ELECTRONIC
            ],
            self::WITHDRAWAL, self::REFUND => [
                PaymentMethod::TRANSFER
            ],
            self::COMMISSION, self::PENALTY, self::BONUS, self::PROMOTION => [
                PaymentMethod::TRANSFER
            ],
        };
    }

    /**
     * Получить типы платежей для пользователей
     */
    public static function getUserTypes(): array
    {
        return [
            self::SERVICE_PAYMENT,
            self::BOOKING_DEPOSIT,
            self::SUBSCRIPTION,
            self::TOP_UP,
        ];
    }

    /**
     * Получить типы платежей для мастеров
     */
    public static function getMasterTypes(): array
    {
        return [
            self::SERVICE_PAYMENT,
            self::WITHDRAWAL,
            self::COMMISSION,
            self::BONUS,
            self::PENALTY,
        ];
    }

    /**
     * Получить системные типы платежей
     */
    public static function getSystemTypes(): array
    {
        return [
            self::COMMISSION,
            self::REFUND,
            self::PENALTY,
            self::BONUS,
            self::PROMOTION,
        ];
    }

    /**
     * Получить типы, требующие НДС
     */
    public static function getVatRequiredTypes(): array
    {
        return [
            self::SERVICE_PAYMENT,
            self::SUBSCRIPTION,
            self::COMMISSION,
        ];
    }

    /**
     * Все типы для выборки
     */
    public static function options(): array
    {
        $types = [];
        foreach (self::cases() as $type) {
            $types[$type->value] = $type->getLabel();
        }
        return $types;
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