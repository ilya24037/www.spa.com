<?php

namespace App\Enums;

/**
 * Уровни мастеров
 */
enum MasterLevel: string
{
    case BEGINNER = 'beginner';         // Начинающий
    case STANDARD = 'standard';         // Стандарт
    case EXPERT = 'expert';             // Эксперт
    case PREMIUM = 'premium';           // Премиум
    case VIP = 'vip';                   // VIP

    /**
     * Получить читаемое название уровня
     */
    public function getLabel(): string
    {
        return match($this) {
            self::BEGINNER => 'Начинающий',
            self::STANDARD => 'Стандарт',
            self::EXPERT => 'Эксперт',
            self::PREMIUM => 'Премиум',
            self::VIP => 'VIP',
        };
    }

    /**
     * Получить описание уровня
     */
    public function getDescription(): string
    {
        return match($this) {
            self::BEGINNER => 'Опыт работы до 1 года',
            self::STANDARD => 'Опыт от 1 до 3 лет, базовые сертификаты',
            self::EXPERT => 'Опыт от 3 лет, профессиональные сертификаты',
            self::PREMIUM => 'Опыт от 5 лет, высокий рейтинг, много отзывов',
            self::VIP => 'Топовый мастер с эксклюзивными услугами',
        };
    }

    /**
     * Получить цвет уровня
     */
    public function getColor(): string
    {
        return match($this) {
            self::BEGINNER => '#9CA3AF',    // gray-400
            self::STANDARD => '#10B981',    // green
            self::EXPERT => '#3B82F6',      // blue
            self::PREMIUM => '#8B5CF6',     // violet
            self::VIP => '#F59E0B',         // amber
        };
    }

    /**
     * Получить иконку уровня
     */
    public function getIcon(): string
    {
        return match($this) {
            self::BEGINNER => '🌱',
            self::STANDARD => '⭐',
            self::EXPERT => '💎',
            self::PREMIUM => '👑',
            self::VIP => '🏆',
        };
    }

    /**
     * Получить минимальный опыт в годах
     */
    public function getMinExperienceYears(): int
    {
        return match($this) {
            self::BEGINNER => 0,
            self::STANDARD => 1,
            self::EXPERT => 3,
            self::PREMIUM => 5,
            self::VIP => 7,
        };
    }

    /**
     * Получить минимальный рейтинг
     */
    public function getMinRating(): float
    {
        return match($this) {
            self::BEGINNER => 0.0,
            self::STANDARD => 3.5,
            self::EXPERT => 4.0,
            self::PREMIUM => 4.5,
            self::VIP => 4.8,
        };
    }

    /**
     * Получить минимальное количество отзывов
     */
    public function getMinReviews(): int
    {
        return match($this) {
            self::BEGINNER => 0,
            self::STANDARD => 5,
            self::EXPERT => 20,
            self::PREMIUM => 50,
            self::VIP => 100,
        };
    }

    /**
     * Получить комиссию платформы (%)
     */
    public function getPlatformCommission(): int
    {
        return match($this) {
            self::BEGINNER => 20,
            self::STANDARD => 15,
            self::EXPERT => 12,
            self::PREMIUM => 10,
            self::VIP => 7,
        };
    }

    /**
     * Получить приоритет в поиске
     */
    public function getSearchPriority(): int
    {
        return match($this) {
            self::VIP => 100,
            self::PREMIUM => 80,
            self::EXPERT => 60,
            self::STANDARD => 40,
            self::BEGINNER => 20,
        };
    }

    /**
     * Получить лимит фотографий
     */
    public function getPhotoLimit(): int
    {
        return match($this) {
            self::BEGINNER => 5,
            self::STANDARD => 10,
            self::EXPERT => 20,
            self::PREMIUM => 30,
            self::VIP => 50,
        };
    }

    /**
     * Получить лимит видео
     */
    public function getVideoLimit(): int
    {
        return match($this) {
            self::BEGINNER => 0,
            self::STANDARD => 1,
            self::EXPERT => 3,
            self::PREMIUM => 5,
            self::VIP => 10,
        };
    }

    /**
     * Проверить доступность функции
     */
    public function hasFeature(string $feature): bool
    {
        $features = match($this) {
            self::BEGINNER => ['basic_profile'],
            self::STANDARD => ['basic_profile', 'photo_gallery', 'reviews'],
            self::EXPERT => ['basic_profile', 'photo_gallery', 'reviews', 'video', 'certificates'],
            self::PREMIUM => ['basic_profile', 'photo_gallery', 'reviews', 'video', 'certificates', 'priority_support', 'analytics'],
            self::VIP => ['basic_profile', 'photo_gallery', 'reviews', 'video', 'certificates', 'priority_support', 'analytics', 'personal_manager', 'premium_badge'],
        };

        return in_array($feature, $features);
    }

    /**
     * Получить следующий уровень
     */
    public function getNextLevel(): ?self
    {
        return match($this) {
            self::BEGINNER => self::STANDARD,
            self::STANDARD => self::EXPERT,
            self::EXPERT => self::PREMIUM,
            self::PREMIUM => self::VIP,
            self::VIP => null,
        };
    }

    /**
     * Определить уровень по параметрам
     */
    public static function determineLevel(int $experience, float $rating, int $reviews): self
    {
        if ($experience >= 7 && $rating >= 4.8 && $reviews >= 100) {
            return self::VIP;
        }
        if ($experience >= 5 && $rating >= 4.5 && $reviews >= 50) {
            return self::PREMIUM;
        }
        if ($experience >= 3 && $rating >= 4.0 && $reviews >= 20) {
            return self::EXPERT;
        }
        if ($experience >= 1 && $rating >= 3.5 && $reviews >= 5) {
            return self::STANDARD;
        }
        
        return self::BEGINNER;
    }

    /**
     * Получить все уровни для выбора
     */
    public static function options(): array
    {
        $levels = [];
        foreach (self::cases() as $level) {
            $levels[$level->value] = $level->getLabel();
        }
        return $levels;
    }
}