<?php

namespace App\Domain\Service\Repositories;

use App\Domain\Service\Models\MassageCategory;
use App\Domain\Common\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Репозиторий для работы с категориями услуг
 * 
 * @extends BaseRepository<MassageCategory>
 */
class CategoryRepository extends BaseRepository
{
    /**
     * Указываем базовой реализации, с какой моделью работает репозиторий
     *
     * @return class-string<MassageCategory>
     */
    protected function getModelClass(): string
    {
        return MassageCategory::class;
    }
    
    /**
     * Получить активные категории
     * 
     * @return Collection
     */
    public function getActive(): Collection
    {
        return Cache::remember('active_categories', 3600, function () {
            return MassageCategory::select('id', 'name', 'icon')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        });
    }
    
    /**
     * Получить все категории
     * 
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Cache::remember('all_categories', 3600, function () {
            return MassageCategory::orderBy('sort_order')->get();
        });
    }
    
    /**
     * Получить категорию по ID
     * 
     * @param int $id
     * @return MassageCategory|null
     */
    public function findById(int $id): ?MassageCategory
    {
        return Cache::remember("category_{$id}", 3600, function () use ($id) {
            return MassageCategory::find($id);
        });
    }
    
    /**
     * Получить категории по IDs
     * 
     * @param array $ids
     * @return Collection
     */
    public function findByIds(array $ids): Collection
    {
        return MassageCategory::whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Получить популярные категории
     * 
     * @param int $limit
     * @return Collection
     */
    public function getPopular(int $limit = 6): Collection
    {
        return Cache::remember("popular_categories_{$limit}", 3600, function () use ($limit) {
            return MassageCategory::where('is_active', true)
                ->orderBy('ads_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Очистить кеш категорий
     * 
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('active_categories');
        Cache::forget('all_categories');
        Cache::tags(['categories'])->flush();
    }
    
    /**
     * Обновить счетчик объявлений в категории
     * 
     * @param int $categoryId
     * @param int $delta изменение счетчика (+1 или -1)
     * @return void
     */
    public function updateAdsCount(int $categoryId, int $delta = 1): void
    {
        MassageCategory::where('id', $categoryId)
            ->increment('ads_count', $delta);
            
        $this->clearCache();
    }
}