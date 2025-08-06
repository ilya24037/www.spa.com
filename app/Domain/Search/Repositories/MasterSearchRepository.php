<?php

namespace App\Domain\Search\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для поиска мастеров
 */
class MasterSearchRepository
{
    /**
     * Поиск мастеров
     */
    public function search(
        string $query,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        $builder = MasterProfile::query()
            ->with(['user', 'services', 'media'])
            ->where('is_active', true);

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
                
                $q->orWhere('specialty', 'LIKE', "%{$term}%")
                  ->orWhere('about', 'LIKE', "%{$term}%")
                  ->orWhereJsonContains('specializations', $term)
                  ->orWhereJsonContains('skills', $term)
                  ->orWhereHas('user', function ($userQuery) use ($term) {
                      $userQuery->where('name', 'LIKE', "%{$term}%");
                  })
                  ->orWhereHas('services', function ($serviceQuery) use ($term) {
                      $serviceQuery->where('name', 'LIKE', "%{$term}%")
                                  ->orWhere('description', 'LIKE', "%{$term}%");
                  });
            }
        });
    }

    /**
     * Применить фильтры
     */
    protected function applyFilters(Builder $builder, array $filters): Builder
    {
        // Фильтр по специализации
        if (!empty($filters['specialty'])) {
            $builder->where('specialty', $filters['specialty']);
        }

        if (!empty($filters['specializations'])) {
            $specializations = (array) $filters['specializations'];
            $builder->where(function ($q) use ($specializations) {
                foreach ($specializations as $spec) {
                    $q->orWhereJsonContains('specializations', $spec);
                }
            });
        }

        // Фильтр по цене
        if (!empty($filters['price_from'])) {
            $builder->whereHas('services', function ($q) use ($filters) {
                $q->where('price', '>=', $filters['price_from']);
            });
        }

        if (!empty($filters['price_to'])) {
            $builder->whereHas('services', function ($q) use ($filters) {
                $q->where('price', '<=', $filters['price_to']);
            });
        }

        // Фильтр по рейтингу
        if (!empty($filters['min_rating'])) {
            $builder->where('rating', '>=', $filters['min_rating']);
        }

        // Фильтр по опыту
        if (!empty($filters['min_experience'])) {
            $builder->where('experience_years', '>=', $filters['min_experience']);
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

        // Фильтр по верификации
        if (!empty($filters['verified_only'])) {
            $builder->where('is_verified', true);
        }

        // Фильтр по премиум статусу
        if (!empty($filters['premium_only'])) {
            $builder->where('is_premium', true);
        }

        // Фильтр по доступности сейчас
        if (!empty($filters['available_now'])) {
            $builder->where('is_online', true);
        }

        // Фильтр по городу
        if (!empty($filters['city'])) {
            $builder->where('city', $filters['city']);
        }

        // Фильтр по району
        if (!empty($filters['district'])) {
            $builder->where('district', $filters['district']);
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
            case SortBy::RATING:
                return $builder->orderBy('rating', 'desc')
                    ->orderBy('reviews_count', 'desc');

            case SortBy::PRICE_LOW:
                return $builder->leftJoin('master_services', 'master_profiles.id', '=', 'master_services.master_profile_id')
                    ->selectRaw('master_profiles.*, MIN(master_services.price) as min_price')
                    ->groupBy('master_profiles.id')
                    ->orderBy('min_price', 'asc');

            case SortBy::PRICE_HIGH:
                return $builder->leftJoin('master_services', 'master_profiles.id', '=', 'master_services.master_profile_id')
                    ->selectRaw('master_profiles.*, MAX(master_services.price) as max_price')
                    ->groupBy('master_profiles.id')
                    ->orderBy('max_price', 'desc');

            case SortBy::EXPERIENCE:
                return $builder->orderBy('experience_years', 'desc')
                    ->orderBy('rating', 'desc');

            case SortBy::NEWEST:
                return $builder->orderBy('created_at', 'desc');

            case SortBy::DISTANCE:
                if ($location && isset($location['lat'], $location['lng'])) {
                    return $builder->orderBy('distance', 'asc');
                }
                return $builder->orderBy('rating', 'desc');

            case SortBy::POPULARITY:
                return $builder->orderBy('views_count', 'desc')
                    ->orderBy('rating', 'desc');

            case SortBy::RELEVANCE:
            default:
                return $builder->orderBy('is_premium', 'desc')
                    ->orderBy('rating', 'desc')
                    ->orderBy('reviews_count', 'desc');
        }
    }

    /**
     * Получить топ мастеров
     */
    public function getTop(int $limit = 10): Collection
    {
        return MasterProfile::query()
            ->with(['user', 'services'])
            ->where('is_active', true)
            ->where('rating', '>=', 4.5)
            ->where('reviews_count', '>=', 10)
            ->orderBy('rating', 'desc')
            ->orderBy('reviews_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить популярных мастеров
     */
    public function getPopular(int $limit = 10): Collection
    {
        return MasterProfile::query()
            ->with(['user', 'services'])
            ->where('is_active', true)
            ->orderBy('views_count', 'desc')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить новых мастеров
     */
    public function getRecent(int $limit = 10): Collection
    {
        return MasterProfile::query()
            ->with(['user', 'services'])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Найти похожих мастеров
     */
    public function findSimilar(MasterProfile $master, int $limit = 5): Collection
    {
        return MasterProfile::query()
            ->with(['user', 'services'])
            ->where('id', '!=', $master->id)
            ->where('is_active', true)
            ->where(function ($q) use ($master) {
                $q->where('specialty', $master->specialty)
                  ->orWhere('city', $master->city);
                
                // Поиск по схожим специализациям
                if ($master->specializations) {
                    foreach ($master->specializations as $spec) {
                        $q->orWhereJsonContains('specializations', $spec);
                    }
                }
            })
            ->orderByRaw('CASE WHEN specialty = ? THEN 1 ELSE 2 END', [$master->specialty])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить мастеров онлайн
     */
    public function getOnline(int $limit = 20): Collection
    {
        return MasterProfile::query()
            ->with(['user', 'services'])
            ->where('is_active', true)
            ->where('is_online', true)
            ->orderBy('last_active_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Автодополнение для поиска мастеров
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $specialties = MasterProfile::query()
            ->where('is_active', true)
            ->where('specialty', 'LIKE', "%{$query}%")
            ->distinct()
            ->pluck('specialty')
            ->take($limit)
            ->toArray();

        $names = MasterProfile::query()
            ->join('users', 'master_profiles.user_id', '=', 'users.id')
            ->where('master_profiles.is_active', true)
            ->where('users.name', 'LIKE', "%{$query}%")
            ->distinct()
            ->pluck('users.name')
            ->take($limit)
            ->toArray();

        return array_unique(array_merge($specialties, $names));
    }
}