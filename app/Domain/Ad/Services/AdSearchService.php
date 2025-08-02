<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Repositories\AdRepository;
use App\Enums\AdStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис поиска объявлений
 */
class AdSearchService
{
    private AdRepository $adRepository;

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Основной поиск объявлений
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Кэширование популярных поисковых запросов
        $cacheKey = $this->generateCacheKey($filters, $perPage);
        
        if (empty($filters['no_cache'])) {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }
        }

        $results = $this->adRepository->search($filters, $perPage);

        // Кэшируем результат на 5 минут для частых запросов
        if ($this->shouldCache($filters)) {
            Cache::put($cacheKey, $results, 300);
        }

        // Логируем поисковые запросы для аналитики
        $this->logSearchQuery($filters, $results->total());

        return $results;
    }

    /**
     * Быстрый поиск (автокомплит)
     */
    public function quickSearch(string $query, int $limit = 10): Collection
    {
        if (mb_strlen($query) < 2) {
            return collect();
        }

        $cacheKey = 'quick_search:' . md5($query);
        
        return Cache::remember($cacheKey, 300, function() use ($query, $limit) {
            return Ad::with(['content', 'pricing'])
                ->where('status', AdStatus::ACTIVE)
                ->whereHas('content', function($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                      ->orWhere('specialty', 'like', '%' . $query . '%');
                })
                ->orderBy('views_count', 'desc')
                ->limit($limit)
                ->get()
                ->map(function($ad) {
                    return [
                        'id' => $ad->id,
                        'title' => $ad->content?->title,
                        'specialty' => $ad->content?->specialty,
                        'price' => $ad->pricing?->formatted_price,
                        'address' => $ad->address,
                        'main_photo' => $ad->media?->main_photo,
                    ];
                });
        });
    }

    /**
     * Поиск похожих объявлений
     */
    public function findSimilar(Ad $ad, int $limit = 5): Collection
    {
        $cacheKey = "similar_ads:{$ad->id}:{$limit}";
        
        return Cache::remember($cacheKey, 600, function() use ($ad, $limit) {
            return $this->adRepository->findSimilar($ad, $limit);
        });
    }

    /**
     * Получить популярные объявления
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Cache::remember('popular_ads:' . $limit, 1800, function() use ($limit) {
            return $this->adRepository->getPopular($limit);
        });
    }

    /**
     * Получить недавние объявления
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Cache::remember('recent_ads:' . $limit, 300, function() use ($limit) {
            return $this->adRepository->getRecent($limit);
        });
    }

    /**
     * Получить рекомендации для пользователя
     */
    public function getRecommendations(?int $userId = null, int $limit = 10): Collection
    {
        if (!$userId) {
            // Для неавторизованных пользователей показываем популярные
            return $this->getPopular($limit);
        }

        $cacheKey = "recommendations:{$userId}:{$limit}";
        
        return Cache::remember($cacheKey, 3600, function() use ($userId, $limit) {
            // Здесь можно реализовать более сложную логику рекомендаций
            // на основе истории просмотров, предпочтений пользователя и т.д.
            
            // Пока возвращаем просто популярные объявления
            return $this->getPopular($limit);
        });
    }

    /**
     * Поиск по геолокации
     */
    public function searchByLocation(float $lat, float $lng, float $radius = 10, array $filters = [], int $limit = 20): Collection
    {
        // Простой поиск по адресам (для полноценного геопоиска нужны координаты в БД)
        $locationQuery = $filters['location'] ?? '';
        
        if (empty($locationQuery)) {
            return collect();
        }

        return Ad::with(['content', 'pricing', 'media'])
            ->where('status', AdStatus::ACTIVE)
            ->where('address', 'like', '%' . $locationQuery . '%')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить фильтры для поиска
     */
    public function getAvailableFilters(): array
    {
        return Cache::remember('search_filters', 3600, function() {
            return [
                'categories' => $this->getPopularCategories(),
                'price_ranges' => $this->getPriceRanges(),
                'age_ranges' => $this->getAgeRanges(),
                'work_formats' => $this->getWorkFormats(),
                'locations' => $this->getPopularLocations(),
                'experience_levels' => $this->getExperienceLevels(),
            ];
        });
    }

    /**
     * Получить популярные категории
     */
    private function getPopularCategories(): array
    {
        return Ad::where('status', AdStatus::ACTIVE)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'category')
            ->toArray();
    }

    /**
     * Получить диапазоны цен
     */
    private function getPriceRanges(): array
    {
        return [
            '0-1000' => 'До 1000 ₽',
            '1000-3000' => '1000-3000 ₽',
            '3000-5000' => '3000-5000 ₽',
            '5000-10000' => '5000-10000 ₽',
            '10000-20000' => '10000-20000 ₽',
            '20000+' => 'От 20000 ₽',
        ];
    }

    /**
     * Получить диапазоны возрастов
     */
    private function getAgeRanges(): array
    {
        return [
            '18-25' => '18-25 лет',
            '25-35' => '25-35 лет',
            '35-45' => '35-45 лет',
            '45+' => 'От 45 лет',
        ];
    }

    /**
     * Получить форматы работы
     */
    private function getWorkFormats(): array
    {
        return [
            'individual' => 'Индивидуально',
            'duo' => 'В паре',
            'group' => 'Групповые услуги',
        ];
    }

    /**
     * Получить популярные локации
     */
    private function getPopularLocations(): array
    {
        return Ad::where('status', AdStatus::ACTIVE)
            ->whereNotNull('address')
            ->selectRaw('SUBSTRING_INDEX(address, ",", 1) as city, COUNT(*) as count')
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->pluck('count', 'city')
            ->toArray();
    }

    /**
     * Получить уровни опыта
     */
    private function getExperienceLevels(): array
    {
        return [
            '3260137' => 'Без опыта',
            '3260142' => 'До 1 года',
            '3260146' => '1-3 года',
            '3260149' => '3-6 лет',
            '3260152' => 'Более 6 лет',
        ];
    }

    /**
     * Генерация ключа кэша
     */
    private function generateCacheKey(array $filters, int $perPage): string
    {
        ksort($filters); // Сортируем для консистентности
        return 'search:' . md5(serialize($filters) . $perPage);
    }

    /**
     * Определить стоит ли кэшировать запрос
     */
    private function shouldCache(array $filters): bool
    {
        // Кэшируем только простые запросы без сложных фильтров
        $complexFilters = ['search', 'custom_location', 'user_specific'];
        
        foreach ($complexFilters as $filter) {
            if (isset($filters[$filter])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Логирование поисковых запросов
     */
    private function logSearchQuery(array $filters, int $resultsCount): void
    {
        // Логируем для аналитики популярных запросов
        if (!empty($filters['search']) || !empty($filters['category'])) {
            Log::info('Search query', [
                'filters' => array_intersect_key($filters, array_flip(['search', 'category', 'location'])),
                'results_count' => $resultsCount,
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Очистить кэш поиска
     */
    public function clearSearchCache(): void
    {
        $tags = ['search', 'popular_ads', 'recent_ads', 'search_filters'];
        
        foreach ($tags as $tag) {
            Cache::forget($tag);
        }

        Log::info('Search cache cleared');
    }

    /**
     * Получить статистику поиска
     */
    public function getSearchStats(): array
    {
        return [
            'total_active_ads' => Ad::where('status', AdStatus::ACTIVE)->count(),
            'categories_count' => Ad::where('status', AdStatus::ACTIVE)
                ->distinct('category')->count(),
            'locations_count' => Ad::where('status', AdStatus::ACTIVE)
                ->whereNotNull('address')->distinct('address')->count(),
            'avg_price' => Ad::join('ad_pricings', 'ads.id', '=', 'ad_pricings.ad_id')
                ->where('ads.status', AdStatus::ACTIVE)
                ->avg('ad_pricings.price'),
            'cache_size' => $this->estimateCacheSize(),
        ];
    }

    /**
     * Оценить размер кэша
     */
    private function estimateCacheSize(): int
    {
        // Примерная оценка размера кэша поиска
        // В реальном проекте можно использовать Redis для точного подсчета
        return 0;
    }
}