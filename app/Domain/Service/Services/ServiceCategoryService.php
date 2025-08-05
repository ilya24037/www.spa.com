<?php

namespace App\Domain\Service\Services;

use App\Domain\Service\Models\MassageCategory;
use App\Support\Services\BaseService;

/**
 * Сервис для управления категориями услуг
 */
class ServiceCategoryService extends BaseService
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