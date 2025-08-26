<?php

namespace App\Domain\Service\Services;

use App\Domain\Service\Models\MassageCategory;

/**
 * Сервис для управления категориями услуг
 */
class ServiceCategoryService
{
    /**
     * Получить категории с подкатегориями
     */
    public function getCategoriesWithSubcategories(): array
    {
        return MassageCategory::with('subcategories')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Получить активные категории
     */
    public function getActiveCategories(): array
    {
        return MassageCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }
}