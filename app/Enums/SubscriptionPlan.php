<?php

namespace App\Enums;

/**
 * Тарифные планы подписок
 */
enum SubscriptionPlan: string
{
    case FREE = 'free';
    case BASIC = 'basic';
    case PREMIUM = 'premium';
    case VIP = 'vip';

    /**
     * Получить название плана
     */
    public function getLabel(): string
    {
        return match($this) {
            self::FREE => 'Бесплатный',
            self::BASIC => 'Базовый',
            self::PREMIUM => 'Премиум',
            self::VIP => 'VIP',
        };
    }

    /**
     * Получить описание плана
     */
    public function getDescription(): string
    {
        return match($this) {
            self::FREE => 'Базовые функции платформы',
            self::BASIC => 'Расширенный профиль и больше фото',
            self::PREMIUM => 'Приоритет в поиске и статистика',
            self::VIP => 'Максимум возможностей и поддержка',
        };
    }

    /**
     * Получить цену в рублях за месяц
     */
    public function getPrice(): int
    {
        return match($this) {
            self::FREE => 0,
            self::BASIC => 990,
            self::PREMIUM => 2490,
            self::VIP => 4990,
        };
    }

    /**
     * Получить лимиты плана
     */
    public function getLimits(): array
    {
        return match($this) {
            self::FREE => [
                'photos' => 3,
                'videos' => 0,
                'priority' => 0,
                'badge' => false,
                'statistics' => false,
                'no_ads' => false,
                'boost_per_month' => 0,
                'description_length' => 500,
                'services' => 5,
                'work_zones' => 1,
                'response_time_hours' => 48,
            ],
            self::BASIC => [
                'photos' => 10,
                'videos' => 2,
                'priority' => 1,
                'badge' => true,
                'badge_type' => 'basic',
                'statistics' => false,
                'no_ads' => false,
                'boost_per_month' => 1,
                'description_length' => 1000,
                'services' => 10,
                'work_zones' => 3,
                'response_time_hours' => 24,
            ],
            self::PREMIUM => [
                'photos' => 20,
                'videos' => 5,
                'priority' => 2,
                'badge' => true,
                'badge_type' => 'premium',
                'statistics' => true,
                'no_ads' => true,
                'boost_per_month' => 3,
                'description_length' => 2000,
                'services' => 20,
                'work_zones' => 5,
                'response_time_hours' => 12,
                'analytics' => true,
                'competitor_analysis' => false,
            ],
            self::VIP => [
                'photos' => 50,
                'videos' => 10,
                'priority' => 3,
                'badge' => true,
                'badge_type' => 'vip',
                'statistics' => true,
                'no_ads' => true,
                'boost_per_month' => 10,
                'description_length' => 5000,
                'services' => -1, // неограниченно
                'work_zones' => -1, // неограниченно
                'response_time_hours' => 1,
                'analytics' => true,
                'competitor_analysis' => true,
                'personal_manager' => true,
                'priority_support' => true,
            ],
        };
    }

    /**
     * Получить конкретный лимит
     */
    public function getLimit(string $key): mixed
    {
        $limits = $this->getLimits();
        return $limits[$key] ?? null;
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::FREE => '#6B7280',    // gray
            self::BASIC => '#3B82F6',   // blue
            self::PREMIUM => '#8B5CF6', // purple
            self::VIP => '#F59E0B',     // amber
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::FREE => '🆓',
            self::BASIC => '⭐',
            self::PREMIUM => '💎',
            self::VIP => '👑',
        };
    }

    /**
     * Получить преимущества плана
     */
    public function getFeatures(): array
    {
        return match($this) {
            self::FREE => [
                'Базовый профиль',
                'До 3 фотографий',
                'До 5 услуг',
                '1 район работы',
            ],
            self::BASIC => [
                'Расширенный профиль',
                'До 10 фото и 2 видео',
                'Значок "Проверенный"',
                'До 10 услуг',
                '3 района работы',
                '1 бесплатное поднятие в месяц',
                'Ответы на отзывы',
            ],
            self::PREMIUM => [
                'Все из тарифа "Базовый"',
                'До 20 фото и 5 видео',
                'Значок "Премиум" ⭐',
                'Приоритет в поиске',
                'Статистика и аналитика',
                'Без рекламы',
                '3 поднятия в месяц',
                'До 20 услуг',
                '5 районов работы',
            ],
            self::VIP => [
                'Все из тарифа "Премиум"',
                'До 50 фото и 10 видео',
                'Значок "VIP" 👑',
                'Топ позиции в поиске',
                'Анализ конкурентов',
                'Персональный менеджер',
                'Приоритетная поддержка 24/7',
                '10 поднятий в месяц',
                'Неограниченные услуги',
                'Работа по всему городу',
            ],
        };
    }

    /**
     * Получить скидку для периода (в процентах)
     */
    public function getDiscount(int $months): int
    {
        if ($this === self::FREE) {
            return 0;
        }

        return match($months) {
            1 => 0,    // месяц - без скидки
            3 => 10,   // квартал - 10%
            6 => 15,   // полгода - 15%
            12 => 20,  // год - 20%
            default => 0,
        };
    }

    /**
     * Рассчитать итоговую стоимость с учетом скидки
     */
    public function calculateTotal(int $months): int
    {
        $basePrice = $this->getPrice() * $months;
        $discount = $this->getDiscount($months);
        
        return (int) ($basePrice * (100 - $discount) / 100);
    }

    /**
     * Проверить, может ли план быть улучшен
     */
    public function canUpgrade(): bool
    {
        return $this !== self::VIP;
    }

    /**
     * Получить следующий план для апгрейда
     */
    public function getNextPlan(): ?self
    {
        return match($this) {
            self::FREE => self::BASIC,
            self::BASIC => self::PREMIUM,
            self::PREMIUM => self::VIP,
            self::VIP => null,
        };
    }

    /**
     * Все доступные планы
     */
    public static function getAllPlans(): array
    {
        return [
            self::FREE,
            self::BASIC,
            self::PREMIUM,
            self::VIP,
        ];
    }

    /**
     * Платные планы
     */
    public static function getPaidPlans(): array
    {
        return [
            self::BASIC,
            self::PREMIUM,
            self::VIP,
        ];
    }
}