<?php

namespace App\Domain\Service\Services;

use App\Domain\Service\Repositories\CategoryRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с категориями и справочниками
 */
class CategoryService
{
    private CategoryRepository $categoryRepository;
    
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }
    
    /**
     * Получить активные категории
     * 
     * @return array
     */
    public function getActiveCategories(): array
    {
        try {
            return $this->categoryRepository->getActive()->toArray();
        } catch (\Exception $e) {
            Log::warning('Failed to load categories from DB', ['error' => $e->getMessage()]);
            return $this->getDefaultCategories();
        }
    }
    
    /**
     * Получить все категории
     * 
     * @return Collection
     */
    public function getAllCategories(): Collection
    {
        try {
            return $this->categoryRepository->getAll();
        } catch (\Exception $e) {
            Log::warning('Failed to load categories', ['error' => $e->getMessage()]);
            return collect($this->getDefaultCategories());
        }
    }
    
    /**
     * Получить список районов
     * 
     * @param string|null $city
     * @return array
     */
    public function getDistricts(?string $city = null): array
    {
        // TODO: В будущем загружать из БД в зависимости от города
        return Cache::remember('districts_' . ($city ?? 'default'), 3600, function () {
            return [
                'Центральный',
                'Пресненский',
                'Тверской',
                'Арбат',
                'Хамовники',
                'Замоскворечье',
                'Басманный',
                'Красносельский',
                'Мещанский',
                'Таганский'
            ];
        });
    }
    
    /**
     * Получить диапазон цен
     * 
     * @return array
     */
    public function getPriceRange(): array
    {
        return [
            'min' => 1000,
            'max' => 10000
        ];
    }
    
    /**
     * Получить доступные города
     * 
     * @return array
     */
    public function getAvailableCities(): array
    {
        return Cache::remember('available_cities', 3600, function () {
            return [
                'Москва',
                'Санкт-Петербург',
                'Новосибирск',
                'Екатеринбург',
                'Нижний Новгород',
                'Казань',
                'Челябинск',
                'Омск',
                'Самара',
                'Ростов-на-Дону'
            ];
        });
    }
    
    /**
     * Дефолтные категории если БД недоступна
     * 
     * @return array
     */
    private function getDefaultCategories(): array
    {
        return [
            ['id' => 1, 'name' => 'Классический массаж', 'icon' => '💆'],
            ['id' => 2, 'name' => 'Тайский массаж', 'icon' => '🧘'],
            ['id' => 3, 'name' => 'Спортивный массаж', 'icon' => '🏃'],
            ['id' => 4, 'name' => 'Лечебный массаж', 'icon' => '🏥'],
            ['id' => 5, 'name' => 'Антицеллюлитный', 'icon' => '✨'],
            ['id' => 6, 'name' => 'СПА программы', 'icon' => '🌺'],
        ];
    }
    
    /**
     * Очистить кеш категорий
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->categoryRepository->clearCache();
        Cache::forget('available_cities');
        
        // Очищаем кеш районов для всех городов
        foreach ($this->getAvailableCities() as $city) {
            Cache::forget('districts_' . $city);
        }
        Cache::forget('districts_default');
    }
}