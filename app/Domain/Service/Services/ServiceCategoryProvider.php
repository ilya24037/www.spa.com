<?php

namespace App\Domain\Service\Services;

/**
 * Провайдер категорий услуг
 * Централизованное управление данными категорий
 */
class ServiceCategoryProvider
{
    /**
     * Получить все доступные категории
     */
    public function getAllCategories(): array
    {
        return [
            [
                'id' => 'massage',
                'name' => 'Массаж',
                'icon' => '💆‍♀️',
                'description' => 'Классический, лечебный, расслабляющий массаж',
                'adult' => false,
                'popular' => true
            ],
            [
                'id' => 'erotic',
                'name' => 'Эротический массаж',
                'icon' => '🔥',
                'description' => 'Тантрический, Body-to-body, интимный массаж',
                'adult' => true,
                'popular' => true
            ],
            [
                'id' => 'strip',
                'name' => 'Стриптиз',
                'icon' => '💃',
                'description' => 'Приватные танцы, шоу-программы',
                'adult' => true,
                'popular' => false
            ],
            [
                'id' => 'escort',
                'name' => 'Эскорт',
                'icon' => '👗',
                'description' => 'Сопровождение на мероприятия, встречи',
                'adult' => true,
                'popular' => false
            ]
        ];
    }

    /**
     * Получить названия категорий
     */
    public function getCategoryNames(): array
    {
        return [
            'massage' => 'Массаж',
            'erotic' => 'Эротический массаж',
            'strip' => 'Стриптиз',
            'escort' => 'Эскорт'
        ];
    }

    /**
     * Получить название категории по ID
     */
    public function getCategoryName(string $categoryId): string
    {
        $names = $this->getCategoryNames();
        return $names[$categoryId] ?? 'Неизвестная категория';
    }

    /**
     * Получить города России
     */
    public function getAvailableCities(): array
    {
        return [
            'Москва', 'Санкт-Петербург', 'Новосибирск', 'Екатеринбург',
            'Казань', 'Нижний Новгород', 'Челябинск', 'Самара',
            'Омск', 'Ростов-на-Дону', 'Уфа', 'Красноярск',
            'Воронеж', 'Пермь', 'Волгоград', 'Краснодар'
        ];
    }

    /**
     * Получить subcategories для массажа
     */
    public function getMassageSubcategories(): array
    {
        return [
            ['id' => 'classic', 'name' => 'Классический массаж'],
            ['id' => 'therapeutic', 'name' => 'Лечебный массаж'],
            ['id' => 'relaxation', 'name' => 'Расслабляющий массаж'],
            ['id' => 'sports', 'name' => 'Спортивный массаж'],
            ['id' => 'anti_cellulite', 'name' => 'Антицеллюлитный массаж'],
        ];
    }

    /**
     * Проверить валидность категории
     */
    public function isValidCategory(string $categoryId): bool
    {
        $validCategories = array_column($this->getAllCategories(), 'id');
        return in_array($categoryId, $validCategories);
    }
}