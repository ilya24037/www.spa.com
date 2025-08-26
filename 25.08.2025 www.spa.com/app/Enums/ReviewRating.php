<?php

namespace App\Enums;

/**
 * Рейтинги отзывов (1-5 звезд)
 */
enum ReviewRating: int
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
    case FOUR = 4;
    case FIVE = 5;

    /**
     * Получить текстовое описание рейтинга
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ONE => 'Очень плохо',
            self::TWO => 'Плохо',
            self::THREE => 'Нормально',
            self::FOUR => 'Хорошо',
            self::FIVE => 'Отлично',
        };
    }

    /**
     * Получить эмодзи для рейтинга
     */
    public function getEmoji(): string
    {
        return match($this) {
            self::ONE => '😞',
            self::TWO => '😐',
            self::THREE => '😊',
            self::FOUR => '😃',
            self::FIVE => '🤩',
        };
    }

    /**
     * Получить цвет рейтинга
     */
    public function getColor(): string
    {
        return match($this) {
            self::ONE, self::TWO => 'error',
            self::THREE => 'warning',
            self::FOUR => 'info',
            self::FIVE => 'success',
        };
    }

    /**
     * Получить HTML звезд
     */
    public function getStarsHtml(): string
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $this->value ? '★' : '☆';
        }
        return $stars;
    }

    /**
     * Проверить, является ли рейтинг положительным
     */
    public function isPositive(): bool
    {
        return $this->value >= 4;
    }

    /**
     * Проверить, является ли рейтинг отрицательным
     */
    public function isNegative(): bool
    {
        return $this->value <= 2;
    }

    /**
     * Проверить, является ли рейтинг нейтральным
     */
    public function isNeutral(): bool
    {
        return $this->value === 3;
    }

    /**
     * Получить вес рейтинга для расчета среднего
     */
    public function getWeight(): float
    {
        return match($this) {
            self::ONE => 0.2,
            self::TWO => 0.4,
            self::THREE => 0.6,
            self::FOUR => 0.8,
            self::FIVE => 1.0,
        };
    }

    /**
     * Создать из числового значения
     */
    public static function fromValue(int $value): self
    {
        return match($value) {
            1 => self::ONE,
            2 => self::TWO,
            3 => self::THREE,
            4 => self::FOUR,
            5 => self::FIVE,
            default => throw new \InvalidArgumentException("Invalid rating value: {$value}"),
        };
    }

    /**
     * Получить все возможные рейтинги
     */
    public static function all(): array
    {
        return [
            self::ONE,
            self::TWO,
            self::THREE,
            self::FOUR,
            self::FIVE,
        ];
    }

    /**
     * Получить положительные рейтинги
     */
    public static function positive(): array
    {
        return [self::FOUR, self::FIVE];
    }

    /**
     * Получить отрицательные рейтинги
     */
    public static function negative(): array
    {
        return [self::ONE, self::TWO];
    }

    /**
     * Рассчитать средний рейтинг из массива
     */
    public static function calculateAverage(array $ratings): float
    {
        if (empty($ratings)) {
            return 0.0;
        }

        $total = array_sum($ratings);
        return round($total / count($ratings), 1);
    }

    /**
     * Получить распределение рейтингов
     */
    public static function getDistribution(array $ratings): array
    {
        $distribution = [
            1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0
        ];

        foreach ($ratings as $rating) {
            if (isset($distribution[$rating])) {
                $distribution[$rating]++;
            }
        }

        return $distribution;
    }
}