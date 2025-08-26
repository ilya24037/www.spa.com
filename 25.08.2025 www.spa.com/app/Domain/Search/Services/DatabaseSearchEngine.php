<?php

namespace App\Domain\Search\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Унифицированный SQL движок поиска (fallback для Elasticsearch)
 * Консолидирует: AdSearchEngine + MasterSearchEngine + ServiceSearchEngine
 * 
 * Принцип KISS: один класс для всего поиска через БД
 */
class DatabaseSearchEngine implements SearchEngineInterface
{
    /**
     * Основной метод поиска
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        $searchType = $this->detectSearchType($filters);
        
        try {
            switch ($searchType) {
                case SearchType::ADS:
                    return $this->searchAds($query, $filters, $sortBy, $page, $perPage, $location);
                    
                case SearchType::MASTERS:
                    return $this->searchMasters($query, $filters, $sortBy, $page, $perPage, $location);
                    
                case SearchType::SERVICES:
                    return $this->searchServices($query, $filters, $sortBy, $page, $perPage);
                    
                default:
                    return $this->searchAds($query, $filters, $sortBy, $page, $perPage, $location);
            }
            
        } catch (\Exception $e) {
            Log::error('Database search failed', [
                'query' => $query,
                'type' => $searchType->value,
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyResults($page, $perPage);
        }
    }

    /**
     * Быстрый поиск для автодополнения
     */
    public function quickSearch(string $query, int $limit = 5): array
    {
        if (mb_strlen($query) < 2) {
            return [];
        }

        try {
            // Поиск по объявлениям
            $ads = Ad::query()
                ->select(['id', 'title', 'description', 'price_per_hour'])
                ->where('status', 'active')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('services', 'LIKE', "%{$query}%");
                })
                ->limit($limit)
                ->get()
                ->map(function ($ad) {
                    return [
                        'id' => $ad->id,
                        'title' => $ad->title,
                        'type' => 'ad',
                        'price' => $ad->price_per_hour
                    ];
                });

            // Поиск по мастерам
            $masters = User::query()
                ->select(['id', 'name', 'city', 'rating'])
                ->where('status', 'active')
                ->where('name', 'LIKE', "%{$query}%")
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'title' => $user->name,
                        'type' => 'master',
                        'city' => $user->city,
                        'rating' => $user->rating
                    ];
                });

            return $ads->concat($masters)->take($limit)->toArray();
            
        } catch (\Exception $e) {
            Log::warning('Quick search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Поиск похожих объектов
     */
    public function findSimilar(int $objectId, int $limit = 10, array $excludeIds = []): array
    {
        try {
            // Находим исходное объявление
            $sourceAd = Ad::find($objectId);
            if (!$sourceAd) {
                return [];
            }

            $query = Ad::query()
                ->select(['id', 'title', 'description', 'price_per_hour', 'services', 'category'])
                ->where('status', 'active')
                ->where('id', '!=', $objectId)
                ->whereNotIn('id', $excludeIds);

            // Ищем по категории
            if ($sourceAd->category) {
                $query->where('category', $sourceAd->category);
            }

            // Ищем по ценовому диапазону (+/- 30%)
            if ($sourceAd->price_per_hour) {
                $minPrice = $sourceAd->price_per_hour * 0.7;
                $maxPrice = $sourceAd->price_per_hour * 1.3;
                $query->whereBetween('price_per_hour', [$minPrice, $maxPrice]);
            }

            // Ищем по услугам
            if ($sourceAd->services) {
                $services = is_string($sourceAd->services) ? json_decode($sourceAd->services, true) : $sourceAd->services;
                if (is_array($services)) {
                    foreach ($services as $service) {
                        $query->orWhere('services', 'LIKE', "%{$service}%");
                    }
                }
            }

            return $query->limit($limit)->get()->toArray();
            
        } catch (\Exception $e) {
            Log::error('Similar search failed', [
                'objectId' => $objectId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Продвинутый поиск
     */
    public function advancedSearch(array $criteria): LengthAwarePaginator
    {
        $query = $criteria['query'] ?? '';
        $type = SearchType::tryFrom($criteria['type'] ?? 'ads') ?? SearchType::ADS;
        $filters = $criteria['filters'] ?? [];
        $sortBy = SortBy::tryFrom($criteria['sortBy'] ?? 'relevance') ?? SortBy::RELEVANCE;
        $page = $criteria['page'] ?? 1;
        $perPage = $criteria['perPage'] ?? 20;
        $location = $criteria['location'] ?? null;

        return $this->search($query, $filters, $sortBy, $page, $perPage, $location);
    }

    /**
     * Фасетный поиск
     */
    public function facetedSearch(string $query, array $facets = []): array
    {
        // Простая реализация фасетного поиска для БД
        $items = collect();
        
        // Группируем результаты по категориям
        if (in_array('category', $facets)) {
            $categoryResults = Ad::query()
                ->select('category', DB::raw('COUNT(*) as count'))
                ->where('status', 'active')
                ->when(!empty($query), function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%");
                })
                ->groupBy('category')
                ->get();
                
            $items->put('category', $categoryResults->toArray());
        }
        
        // Группируем по городам
        if (in_array('city', $facets)) {
            $cityResults = User::query()
                ->select('city', DB::raw('COUNT(*) as count'))
                ->where('status', 'active')
                ->when(!empty($query), function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })
                ->whereNotNull('city')
                ->groupBy('city')
                ->get();
                
            $items->put('city', $cityResults->toArray());
        }
        
        return $items->toArray();
    }

    /**
     * Экспорт результатов
     */
    public function exportResults(LengthAwarePaginator $results, string $format = 'csv'): string
    {
        $resultService = new SearchResultService();
        return $resultService->export($results, $format);
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query, int $limit = 5): array
    {
        $analyticsService = new SearchAnalyticsService();
        return $analyticsService->getRelatedQueries($query, SearchType::ADS, $limit);
    }

    /**
     * Геопоиск
     */
    public function geoSearch(array $location, float $radius, array $filters = [], int $limit = 20): array
    {
        try {
            $lat = $location['lat'] ?? 0;
            $lng = $location['lng'] ?? 0;
            
            if (!$lat || !$lng) {
                return [];
            }

            $query = Ad::query()
                ->select([
                    'ads.*',
                    DB::raw("
                        (6371 * acos(cos(radians($lat)) 
                        * cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(geo, '$.lat')) AS DECIMAL(10,8)))) 
                        * cos(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(geo, '$.lng')) AS DECIMAL(11,8))) - radians($lng)) 
                        + sin(radians($lat)) 
                        * sin(radians(CAST(JSON_UNQUOTE(JSON_EXTRACT(geo, '$.lat')) AS DECIMAL(10,8)))))) AS distance
                    ")
                ])
                ->where('status', 'active')
                ->whereNotNull('geo')
                ->having('distance', '<=', $radius)
                ->orderBy('distance', 'asc');

            // Применяем дополнительные фильтры
            $this->applyFilters($query, $filters);

            return $query->limit($limit)->get()->toArray();
            
        } catch (\Exception $e) {
            Log::error('Geo search failed', [
                'location' => $location,
                'radius' => $radius,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Проверка работоспособности
     */
    public function healthCheck(): array
    {
        try {
            $adCount = Ad::count();
            $userCount = User::count();
            
            return [
                'status' => 'healthy',
                'ads_count' => $adCount,
                'users_count' => $userCount,
                'database_connection' => 'ok'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ===

    /**
     * Поиск объявлений
     */
    public function searchAds(string $query, array $filters = [], SortBy $sortBy = SortBy::RELEVANCE, int $page = 1, int $perPage = 20, ?array $location = null): LengthAwarePaginator
    {
        $builder = Ad::query()
            ->select([
                'ads.*',
                'users.name as master_name',
                'users.rating as master_rating',
                'users.reviews_count as master_reviews_count'
            ])
            ->join('users', 'ads.user_id', '=', 'users.id')
            ->where('ads.status', 'active')
            ->where('users.status', 'active');

        // Текстовый поиск
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('ads.title', 'LIKE', "%{$query}%")
                  ->orWhere('ads.description', 'LIKE', "%{$query}%")
                  ->orWhere('ads.services', 'LIKE', "%{$query}%")
                  ->orWhere('users.name', 'LIKE', "%{$query}%");
            });
        }

        // Фильтры
        $this->applyFilters($builder, $filters);

        // Геофильтр
        if ($location) {
            $this->applyGeoFilter($builder, $location);
        }

        // Сортировка
        $this->applySorting($builder, $sortBy);

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Поиск мастеров
     */
    public function searchMasters(string $query, array $filters = [], SortBy $sortBy = SortBy::RELEVANCE, int $page = 1, int $perPage = 20, ?array $location = null): LengthAwarePaginator
    {
        $builder = User::query()
            ->select([
                'users.*',
                DB::raw('COUNT(ads.id) as ads_count'),
                DB::raw('AVG(ads.price_per_hour) as avg_price')
            ])
            ->leftJoin('ads', 'users.id', '=', 'ads.user_id')
            ->where('users.status', 'active')
            ->groupBy('users.id');

        // Текстовый поиск
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('users.name', 'LIKE', "%{$query}%")
                  ->orWhere('users.bio', 'LIKE', "%{$query}%")
                  ->orWhere('users.specialization', 'LIKE', "%{$query}%")
                  ->orWhere('users.city', 'LIKE', "%{$query}%");
            });
        }

        // Фильтры для мастеров
        $this->applyMasterFilters($builder, $filters);

        // Геофильтр
        if ($location) {
            $this->applyGeoFilterForMasters($builder, $location);
        }

        // Сортировка
        $this->applyMasterSorting($builder, $sortBy);

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Поиск услуг
     */
    public function searchServices(string $query, array $filters = [], SortBy $sortBy = SortBy::RELEVANCE, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        // Поиск по услугам в объявлениях
        $builder = Ad::query()
            ->select([
                'ads.services',
                DB::raw('COUNT(*) as service_count'),
                DB::raw('AVG(price_per_hour) as avg_price'),
                DB::raw('MIN(price_per_hour) as min_price'),
                DB::raw('MAX(price_per_hour) as max_price')
            ])
            ->where('status', 'active')
            ->whereNotNull('services')
            ->where('services', '!=', '');

        if (!empty($query)) {
            $builder->where('services', 'LIKE', "%{$query}%");
        }

        $builder->groupBy('services')
                ->orderBy('service_count', 'desc');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Определить тип поиска
     */
    private function detectSearchType(array $filters): SearchType
    {
        if (isset($filters['search_type'])) {
            return SearchType::tryFrom($filters['search_type']) ?? SearchType::ADS;
        }

        // Логика автоопределения типа поиска
        if (isset($filters['master_rating']) || isset($filters['experience_years'])) {
            return SearchType::MASTERS;
        }

        if (isset($filters['service_category'])) {
            return SearchType::SERVICES;
        }

        return SearchType::ADS;
    }

    /**
     * Применить фильтры
     */
    private function applyFilters(Builder $builder, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            switch ($key) {
                case 'category':
                    $builder->where('ads.category', $value);
                    break;

                case 'price_min':
                    $builder->where('ads.price_per_hour', '>=', $value);
                    break;

                case 'price_max':
                    $builder->where('ads.price_per_hour', '<=', $value);
                    break;

                case 'city':
                    $builder->where('users.city', $value);
                    break;

                case 'rating_min':
                    $builder->where('users.rating', '>=', $value);
                    break;

                case 'age_min':
                    $builder->where('ads.age', '>=', $value);
                    break;

                case 'age_max':
                    $builder->where('ads.age', '<=', $value);
                    break;

                case 'services':
                    if (is_array($value)) {
                        $builder->where(function ($q) use ($value) {
                            foreach ($value as $service) {
                                $q->orWhere('ads.services', 'LIKE', "%{$service}%");
                            }
                        });
                    }
                    break;
            }
        }
    }

    /**
     * Применить фильтры для мастеров
     */
    private function applyMasterFilters(Builder $builder, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            switch ($key) {
                case 'rating_min':
                    $builder->where('users.rating', '>=', $value);
                    break;

                case 'experience_min':
                    $builder->where('users.experience_years', '>=', $value);
                    break;

                case 'city':
                    $builder->where('users.city', $value);
                    break;

                case 'specialization':
                    $builder->where('users.specialization', 'LIKE', "%{$value}%");
                    break;

                case 'verified':
                    $builder->where('users.verified_at', '!=', null);
                    break;
            }
        }
    }

    /**
     * Применить геофильтр
     */
    private function applyGeoFilter(Builder $builder, array $location): void
    {
        $lat = $location['lat'] ?? 0;
        $lng = $location['lng'] ?? 0;
        $radius = $location['radius'] ?? 50; // км

        if ($lat && $lng) {
            $builder->whereRaw("
                ST_Distance_Sphere(
                    POINT(JSON_UNQUOTE(JSON_EXTRACT(ads.geo, '$.lng')), JSON_UNQUOTE(JSON_EXTRACT(ads.geo, '$.lat'))),
                    POINT(?, ?)
                ) <= ? * 1000
            ", [$lng, $lat, $radius]);
        }
    }

    /**
     * Применить геофильтр для мастеров
     */
    private function applyGeoFilterForMasters(Builder $builder, array $location): void
    {
        if (isset($location['city'])) {
            $builder->where('users.city', $location['city']);
        }
    }

    /**
     * Применить сортировку
     */
    private function applySorting(Builder $builder, SortBy $sortBy): void
    {
        switch ($sortBy) {
            case SortBy::PRICE_ASC:
                $builder->orderBy('ads.price_per_hour', 'asc');
                break;

            case SortBy::PRICE_DESC:
                $builder->orderBy('ads.price_per_hour', 'desc');
                break;

            case SortBy::RATING:
                $builder->orderBy('users.rating', 'desc');
                break;

            case SortBy::NEWEST:
                $builder->orderBy('ads.created_at', 'desc');
                break;

            case SortBy::POPULAR:
                $builder->orderBy('ads.views_count', 'desc');
                break;

            case SortBy::RELEVANCE:
            default:
                $builder->orderBy('users.rating', 'desc')
                        ->orderBy('ads.updated_at', 'desc');
                break;
        }
    }

    /**
     * Применить сортировку для мастеров
     */
    private function applyMasterSorting(Builder $builder, SortBy $sortBy): void
    {
        switch ($sortBy) {
            case SortBy::RATING:
                $builder->orderBy('users.rating', 'desc');
                break;

            case SortBy::NEWEST:
                $builder->orderBy('users.created_at', 'desc');
                break;

            case SortBy::POPULAR:
                $builder->orderBy('users.reviews_count', 'desc');
                break;

            case SortBy::RELEVANCE:
            default:
                $builder->orderBy('users.rating', 'desc')
                        ->orderBy('ads_count', 'desc');
                break;
        }
    }

    /**
     * Получить пустые результаты
     */
    private function getEmptyResults(int $page, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect([]),
            0,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }
}