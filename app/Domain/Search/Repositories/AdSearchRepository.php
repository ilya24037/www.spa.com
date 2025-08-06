<?php

namespace App\Domain\Search\Repositories;

use App\Domain\Ad\Models\Ad;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для поиска объявлений
 */
class AdSearchRepository
{
    /**
     * Поиск объявлений
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        $builder = Ad::query()
            ->with(['user', 'category', 'media'])
            ->where('is_active', true)
            ->where('status', 'published');

        // Применяем поисковый запрос
        if (!empty($query)) {
            $builder = $this->applyTextSearch($builder, $query);
        }

        // Применяем фильтры
        $builder = $this->applyFilters($builder, $filters);

        // Применяем геофильтр
        if ($location) {
            $builder = $this->applyLocationFilter($builder, $location);
        }

        // Применяем сортировку
        $builder = $this->applySorting($builder, $sortBy, $location);

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Применить текстовый поиск
     */
    protected function applyTextSearch(Builder $builder, string $query): Builder
    {
        $terms = explode(' ', trim($query));
        
        return $builder->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                if (strlen($term) < 2) continue;
                
                $q->orWhere('title', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%")
                  ->orWhereHas('category', function ($catQuery) use ($term) {
                      $catQuery->where('name', 'LIKE', "%{$term}%");
                  })
                  ->orWhereHas('user', function ($userQuery) use ($term) {
                      $userQuery->where('name', 'LIKE', "%{$term}%");
                  });
            }
        });
    }

    /**
     * Применить фильтры
     */
    protected function applyFilters(Builder $builder, array $filters): Builder
    {
        // Фильтр по категории
        if (!empty($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['categories'])) {
            $builder->whereIn('category_id', (array) $filters['categories']);
        }

        // Фильтр по цене
        if (!empty($filters['price_from'])) {
            $builder->where(function ($q) use ($filters) {
                $q->where('price_from', '>=', $filters['price_from'])
                  ->orWhere('price_fixed', '>=', $filters['price_from']);
            });
        }

        if (!empty($filters['price_to'])) {
            $builder->where(function ($q) use ($filters) {
                $q->where('price_to', '<=', $filters['price_to'])
                  ->orWhere('price_fixed', '<=', $filters['price_to']);
            });
        }

        // Фильтр по типу услуги
        if (!empty($filters['service_location'])) {
            $builder->where('service_location', $filters['service_location']);
        }

        // Фильтр по станциям метро
        if (!empty($filters['metro_stations'])) {
            $stations = (array) $filters['metro_stations'];
            $builder->where(function ($q) use ($stations) {
                foreach ($stations as $station) {
                    $q->orWhereJsonContains('metro_stations', $station);
                }
            });
        }

        // Фильтр по рейтингу мастера
        if (!empty($filters['min_rating'])) {
            $builder->whereHas('user.masterProfile', function ($q) use ($filters) {
                $q->where('rating', '>=', $filters['min_rating']);
            });
        }

        // Фильтр по верифицированным мастерам
        if (!empty($filters['verified_only'])) {
            $builder->whereHas('user.masterProfile', function ($q) {
                $q->where('is_verified', true);
            });
        }

        return $builder;
    }

    /**
     * Применить геофильтр
     */
    protected function applyLocationFilter(Builder $builder, array $location): Builder
    {
        if (!isset($location['lat'], $location['lng'])) {
            return $builder;
        }

        $lat = $location['lat'];
        $lng = $location['lng'];
        $radius = $location['radius'] ?? 10; // км

        return $builder->selectRaw("
            *, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
        ", [$lat, $lng, $lat])
        ->having('distance', '<', $radius);
    }

    /**
     * Применить сортировку
     */
    protected function applySorting(Builder $builder, SortBy $sortBy, ?array $location = null): Builder
    {
        switch ($sortBy) {
            case SortBy::RELEVANCE:
                // Сортировка по релевантности (можно улучшить)
                return $builder->orderBy('created_at', 'desc');

            case SortBy::PRICE_LOW:
                return $builder->orderByRaw('COALESCE(price_from, price_fixed) ASC');

            case SortBy::PRICE_HIGH:
                return $builder->orderByRaw('COALESCE(price_to, price_fixed) DESC');

            case SortBy::RATING:
                return $builder->leftJoin('master_profiles', 'ads.user_id', '=', 'master_profiles.user_id')
                    ->orderBy('master_profiles.rating', 'desc')
                    ->orderBy('ads.created_at', 'desc');

            case SortBy::NEWEST:
                return $builder->orderBy('created_at', 'desc');

            case SortBy::DISTANCE:
                if ($location && isset($location['lat'], $location['lng'])) {
                    return $builder->orderBy('distance', 'asc');
                }
                return $builder->orderBy('created_at', 'desc');

            case SortBy::POPULARITY:
                return $builder->leftJoin('ad_views', 'ads.id', '=', 'ad_views.ad_id')
                    ->selectRaw('ads.*, COUNT(ad_views.id) as view_count')
                    ->groupBy('ads.id')
                    ->orderBy('view_count', 'desc')
                    ->orderBy('ads.created_at', 'desc');

            default:
                return $builder->orderBy('created_at', 'desc');
        }
    }

    /**
     * Получить популярные объявления
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Ad::query()
            ->with(['user', 'category'])
            ->where('is_active', true)
            ->where('status', 'published')
            ->leftJoin('ad_views', 'ads.id', '=', 'ad_views.ad_id')
            ->selectRaw('ads.*, COUNT(ad_views.id) as view_count')
            ->groupBy('ads.id')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить недавние объявления
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Ad::query()
            ->with(['user', 'category'])
            ->where('is_active', true)
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Поиск по похожим объявлениям
     */
    public function findSimilar(Ad $ad, int $limit = 5): Collection
    {
        return Ad::query()
            ->with(['user', 'category'])
            ->where('id', '!=', $ad->id)
            ->where('is_active', true)
            ->where('status', 'published')
            ->where(function ($q) use ($ad) {
                $q->where('category_id', $ad->category_id)
                  ->orWhere('title', 'LIKE', '%' . substr($ad->title, 0, 20) . '%');
            })
            ->orderByRaw('CASE WHEN category_id = ? THEN 1 ELSE 2 END', [$ad->category_id])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Автодополнение для поиска
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $titles = Ad::query()
            ->where('is_active', true)
            ->where('status', 'published')
            ->where('title', 'LIKE', "%{$query}%")
            ->distinct()
            ->pluck('title')
            ->take($limit)
            ->toArray();

        $categories = DB::table('categories')
            ->where('name', 'LIKE', "%{$query}%")
            ->pluck('name')
            ->take($limit)
            ->toArray();

        return array_unique(array_merge($titles, $categories));
    }
}