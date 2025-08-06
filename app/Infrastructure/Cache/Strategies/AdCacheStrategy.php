<?php

namespace App\Infrastructure\Cache\Strategies;

use App\Infrastructure\Cache\Contracts\CacheStrategyInterface;
use App\Domain\Ad\Models\Ad;

/**
 * Стратегия кеширования для объявлений
 */
class AdCacheStrategy implements CacheStrategyInterface
{
    private int $defaultTTL = 300; // 5 минут
    
    public function getKey($identifier): string
    {
        if ($identifier instanceof Ad) {
            return "ad:{$identifier->id}";
        }
        
        if (is_array($identifier)) {
            // Для фильтрованных запросов
            return 'ads:' . md5(serialize($identifier));
        }
        
        return "ad:{$identifier}";
    }

    public function getTags($identifier): array
    {
        $tags = ['ads'];
        
        if ($identifier instanceof Ad) {
            $tags[] = "ad:{$identifier->id}";
            $tags[] = "user:{$identifier->user_id}";
            $tags[] = "category:{$identifier->category}";
            
            if ($identifier->master_profile_id) {
                $tags[] = "master:{$identifier->master_profile_id}";
            }
        }
        
        return $tags;
    }

    public function getTTL(): int
    {
        return $this->defaultTTL;
    }

    public function shouldCache($data): bool
    {
        // Не кешируем черновики
        if ($data instanceof Ad && $data->status === 'draft') {
            return false;
        }
        
        // Не кешируем заблокированные объявления
        if ($data instanceof Ad && $data->status === 'blocked') {
            return false;
        }
        
        return true;
    }

    public function prepareForCache($data)
    {
        if ($data instanceof Ad) {
            // Предзагружаем связи для кеширования
            $data->load(['user', 'masterProfile', 'photos', 'services']);
            
            // Удаляем чувствительные данные для кеширования
            $cached = $data->toArray();
            unset($cached['admin_notes']);
            unset($cached['internal_notes']);
            
            return $cached;
        }
        
        return $data;
    }

    public function processFromCache($data)
    {
        // При необходимости можно восстановить модель из кешированных данных
        return $data;
    }

    public function invalidate($identifier): bool
    {
        $tags = $this->getTags($identifier);
        
        // Инвалидируем кеш объявления и связанные кеши
        app('cache')->tags($tags)->flush();
        
        // Инвалидируем списки объявлений
        app('cache')->tags(['ads_lists'])->flush();
        
        return true;
    }

    /**
     * Специальные методы для работы с объявлениями
     */
    
    /**
     * Ключ для списка объявлений пользователя
     */
    public function getUserAdsKey(int $userId, array $filters = []): string
    {
        $filterKey = empty($filters) ? 'all' : md5(serialize($filters));
        return "ads:user:{$userId}:filters:{$filterKey}";
    }
    
    /**
     * Ключ для списка объявлений по категории
     */
    public function getCategoryAdsKey(string $category, array $filters = []): string
    {
        $filterKey = empty($filters) ? 'all' : md5(serialize($filters));
        return "ads:category:{$category}:filters:{$filterKey}";
    }
    
    /**
     * Ключ для поиска объявлений
     */
    public function getSearchKey(array $searchParams): string
    {
        return "ads:search:" . md5(serialize($searchParams));
    }
    
    /**
     * Теги для списков объявлений
     */
    public function getListTags(): array
    {
        return ['ads', 'ads_lists'];
    }
    
    /**
     * TTL для различных типов кеша объявлений
     */
    public function getTTLByType(string $type): int
    {
        $ttlMap = [
            'single_ad' => 300,      // 5 минут
            'user_ads' => 180,       // 3 минуты  
            'category_ads' => 600,   // 10 минут
            'search_results' => 120, // 2 минуты
            'featured_ads' => 900,   // 15 минут
            'popular_ads' => 1800,   // 30 минут
        ];
        
        return $ttlMap[$type] ?? $this->defaultTTL;
    }
}