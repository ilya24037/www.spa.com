<?php

namespace App\Domain\Ad\Enums;

/**
 * Типы объявлений
 * Классификация услуг в системе
 */
enum AdType: string
{
    case MASSAGE = 'massage';
    case SPA = 'spa';
    case THERAPY = 'therapy';
    case WELLNESS = 'wellness';
    case BEAUTY = 'beauty';
    case FITNESS = 'fitness';
    case MEDICAL = 'medical';
    case RELAXATION = 'relaxation';

    /**
     * Получить читаемое название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::MASSAGE => 'Массаж',
            self::SPA => 'SPA процедуры',
            self::THERAPY => 'Терапия',
            self::WELLNESS => 'Велнесс',
            self::BEAUTY => 'Красота',
            self::FITNESS => 'Фитнес',
            self::MEDICAL => 'Медицинские услуги',
            self::RELAXATION => 'Релаксация',
        };
    }

    /**
     * Получить описание типа
     */
    public function getDescription(): string
    {
        return match($this) {
            self::MASSAGE => 'Классический, расслабляющий, лечебный массаж',
            self::SPA => 'SPA салоны, обертывания, уход за телом',
            self::THERAPY => 'Физиотерапия, реабилитация, лечение',
            self::WELLNESS => 'Комплексные оздоровительные программы',
            self::BEAUTY => 'Косметология, уход за кожей, эстетика',
            self::FITNESS => 'Персональные тренировки, растяжка',
            self::MEDICAL => 'Медицинский массаж, восстановление',
            self::RELAXATION => 'Антистресс программы, медитация',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::MASSAGE => '💆',
            self::SPA => '🛁',
            self::THERAPY => '🏥',
            self::WELLNESS => '🌱',
            self::BEAUTY => '💄',
            self::FITNESS => '💪',
            self::MEDICAL => '⚕️',
            self::RELAXATION => '🧘',
        };
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::MASSAGE => '#8B5CF6',      // violet
            self::SPA => '#06B6D4',          // cyan
            self::THERAPY => '#EF4444',      // red
            self::WELLNESS => '#10B981',     // emerald
            self::BEAUTY => '#EC4899',       // pink
            self::FITNESS => '#F97316',      // orange
            self::MEDICAL => '#3B82F6',      // blue
            self::RELAXATION => '#84CC16',   // lime
        };
    }

    /**
     * Проверить требует ли тип специальной лицензии
     */
    public function requiresLicense(): bool
    {
        return match($this) {
            self::MEDICAL, self::THERAPY => true,
            default => false,
        };
    }

    /**
     * Получить популярные подкategории
     */
    public function getSubcategories(): array
    {
        return match($this) {
            self::MASSAGE => [
                'classic' => 'Классический',
                'relax' => 'Расслабляющий',
                'therapeutic' => 'Лечебный',
                'sports' => 'Спортивный',
                'lymphatic' => 'Лимфодренажный',
            ],
            self::SPA => [
                'body_wrap' => 'Обертывания',
                'scrub' => 'Скрабинг',
                'hydro' => 'Гидротерапия',
                'aromatherapy' => 'Ароматерапия',
            ],
            self::THERAPY => [
                'physio' => 'Физиотерапия',
                'manual' => 'Мануальная терапия',
                'rehabilitation' => 'Реабилитация',
            ],
            self::BEAUTY => [
                'facial' => 'Уход за лицом',
                'body_care' => 'Уход за телом',
                'anti_age' => 'Антивозрастной уход',
            ],
            default => [],
        };
    }

    /**
     * Получить средний диапазон цен
     */
    public function getPriceRange(): array
    {
        return match($this) {
            self::MASSAGE => ['min' => 2000, 'max' => 5000],
            self::SPA => ['min' => 3000, 'max' => 8000],
            self::THERAPY => ['min' => 2500, 'max' => 6000],
            self::WELLNESS => ['min' => 4000, 'max' => 10000],
            self::BEAUTY => ['min' => 2000, 'max' => 7000],
            self::FITNESS => ['min' => 1500, 'max' => 4000],
            self::MEDICAL => ['min' => 3000, 'max' => 8000],
            self::RELAXATION => ['min' => 2000, 'max' => 5000],
        };
    }

    /**
     * Получить все типы для выборки
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
     * Получить популярные типы (для главной страницы)
     */
    public static function popularTypes(): array
    {
        return [
            self::MASSAGE,
            self::SPA,
            self::BEAUTY,
            self::RELAXATION,
        ];
    }
}