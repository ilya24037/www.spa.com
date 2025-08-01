<?php

namespace App\Domain\Search\Repositories;

use App\Enums\SearchType;
use App\Enums\SortBy;
use App\Models\Ad;
use App\Models\User;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Репозиторий для поиска
 */
class SearchRepository
{
    /**
     * Выполнить поиск
     */
    public function search(
        string $query,
        SearchType $type,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20,
        ?array $location = null
    ): LengthAwarePaginator {
        
        $cacheKey = $type->getCacheKey($query, array_merge($filters, [
            'sort' => $sortBy->value,
            'page' => $page,
            'per_page' => $perPage,
            'location' => $location,
        ]));

        return Cache::remember($cacheKey, $type->getCacheTTL() * 60, function () use (
            $query, $type, $filters, $sortBy, $page, $perPage, $location
        ) {
            return $this->performSearch($query, $type, $filters, $sortBy, $page, $perPage, $location);
        });
    }

    /**
     * Выполнить поиск без кэширования
     */
    protected function performSearch(
        string $query,
        SearchType $type,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {

        return match($type) {
            SearchType::ADS => $this->searchAds($query, $filters, $sortBy, $page, $perPage, $location),
            SearchType::MASTERS => $this->searchMasters($query, $filters, $sortBy, $page, $perPage, $location),
            SearchType::SERVICES => $this->searchServices($query, $filters, $sortBy, $page, $perPage, $location),
            SearchType::GLOBAL => $this->searchGlobal($query, $filters, $sortBy, $page, $perPage, $location),
            SearchType::RECOMMENDATIONS => $this->searchRecommendations($query, $filters, $sortBy, $page, $perPage, $location),
        };
    }

    /**
     * Поиск объявлений
     */
    protected function searchAds(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $builder = Ad::query()
            ->select([
                'ads.*',
                DB::raw($this->getRelevanceScore($query, [
                    'ads.title' => 3.0,
                    'ads.description' => 2.0,
                    'ads.specialty' => 2.5,
                    'ads.additional_features' => 1.5,
                    'ads.city' => 1.0,
                ]) . ' as relevance_score')
            ])
            ->with([
                'user:id,name,avatar,rating,reviews_count,experience_years',
                'media' => function($query) {
                    $query->where('type', 'image')->limit(3);
                },
                'reviews' => function($query) {
                    $query->latest()->limit(3);
                }
            ])
            ->where('status', 'active')
            ->where('is_published', true);

        // Текстовый поиск
        if (!empty($query)) {
            $builder->where(function($q) use ($query) {
                $searchTerms = $this->parseSearchQuery($query);
                
                foreach ($searchTerms as $term) {
                    $q->orWhere('title', 'LIKE', "%{$term}%")
                      ->orWhere('description', 'LIKE', "%{$term}%")
                      ->orWhere('specialty', 'LIKE', "%{$term}%")
                      ->orWhere('additional_features', 'LIKE', "%{$term}%")
                      ->orWhere('city', 'LIKE', "%{$term}%");
                }
            });
        }

        // Применяем фильтры
        $this->applyAdFilters($builder, $filters);

        // Добавляем геопоиск
        if ($location && $sortBy === SortBy::DISTANCE) {
            $builder = $this->addDistanceCalculation($builder, $location['lat'], $location['lng']);
        }

        // Применяем сортировку
        $this->applySorting($builder, $sortBy, 'ads');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Поиск мастеров
     */
    protected function searchMasters(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $builder = User::query()
            ->select([
                'users.*',
                DB::raw($this->getRelevanceScore($query, [
                    'users.name' => 3.0,
                    'users.specialty' => 2.5,
                    'users.description' => 2.0,
                    'users.city' => 1.5,
                ]) . ' as relevance_score')
            ])
            ->with([
                'ads' => function($query) {
                    $query->where('status', 'active')->limit(5);
                },
                'media' => function($query) {
                    $query->where('type', 'avatar')->orWhere('type', 'image');
                },
                'reviews' => function($query) {
                    $query->latest()->limit(5);
                }
            ])
            ->where('is_master', true)
            ->where('is_active', true);

        // Текстовый поиск
        if (!empty($query)) {
            $builder->where(function($q) use ($query) {
                $searchTerms = $this->parseSearchQuery($query);
                
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'LIKE', "%{$term}%")
                      ->orWhere('specialty', 'LIKE', "%{$term}%")
                      ->orWhere('description', 'LIKE', "%{$term}%")
                      ->orWhere('city', 'LIKE', "%{$term}%");
                }
            });
        }

        // Применяем фильтры
        $this->applyMasterFilters($builder, $filters);

        // Добавляем геопоиск
        if ($location && $sortBy === SortBy::DISTANCE) {
            $builder = $this->addDistanceCalculation($builder, $location['lat'], $location['lng']);
        }

        // Применяем сортировку
        $this->applySorting($builder, $sortBy, 'users');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Поиск услуг
     */
    protected function searchServices(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $builder = Service::query()
            ->select([
                'services.*',
                DB::raw($this->getRelevanceScore($query, [
                    'services.name' => 3.0,
                    'services.description' => 2.0,
                ]) . ' as relevance_score')
            ])
            ->with(['category', 'media'])
            ->where('status', 'active');

        // Текстовый поиск
        if (!empty($query)) {
            $builder->where(function($q) use ($query) {
                $searchTerms = $this->parseSearchQuery($query);
                
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'LIKE', "%{$term}%")
                      ->orWhere('description', 'LIKE', "%{$term}%");
                }
            });
        }

        // Применяем фильтры
        $this->applyServiceFilters($builder, $filters);

        // Применяем сортировку
        $this->applySorting($builder, $sortBy, 'services');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Глобальный поиск
     */
    protected function searchGlobal(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $results = collect();
        $totalResults = 0;

        // Поиск по всем типам
        $adResults = $this->searchAds($query, $filters, $sortBy, 1, 10, $location);
        $masterResults = $this->searchMasters($query, $filters, $sortBy, 1, 10, $location);
        $serviceResults = $this->searchServices($query, $filters, $sortBy, 1, 5, $location);

        // Объединяем результаты с метаданными
        foreach ($adResults->items() as $ad) {
            $results->push([
                'type' => 'ad',
                'data' => $ad,
                'relevance_score' => $ad->relevance_score ?? 0,
            ]);
        }

        foreach ($masterResults->items() as $master) {
            $results->push([
                'type' => 'master',
                'data' => $master,
                'relevance_score' => $master->relevance_score ?? 0,
            ]);
        }

        foreach ($serviceResults->items() as $service) {
            $results->push([
                'type' => 'service',
                'data' => $service,
                'relevance_score' => $service->relevance_score ?? 0,
            ]);
        }

        // Сортируем объединенные результаты
        $sorted = $results->sortByDesc('relevance_score');
        
        $totalResults = $adResults->total() + $masterResults->total() + $serviceResults->total();

        // Пагинация
        $offset = ($page - 1) * $perPage;
        $items = $sorted->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $totalResults,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Поиск рекомендаций
     */
    protected function searchRecommendations(
        string $query,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $userId = auth()->id();
        if (!$userId) {
            return $this->searchAds($query, $filters, $sortBy, $page, $perPage, $location);
        }

        // Получаем историю пользователя для персонализации
        $userHistory = $this->getUserSearchHistory($userId);
        $userPreferences = $this->getUserPreferences($userId);

        $builder = Ad::query()
            ->select([
                'ads.*',
                DB::raw($this->getPersonalizedRelevanceScore($query, $userHistory, $userPreferences) . ' as relevance_score')
            ])
            ->with([
                'user:id,name,avatar,rating,reviews_count',
                'media' => function($query) {
                    $query->where('type', 'image')->limit(3);
                }
            ])
            ->where('status', 'active')
            ->where('is_published', true);

        // Исключаем уже просмотренные недавно
        if (!empty($userHistory['viewed_ads'])) {
            $builder->whereNotIn('id', array_slice($userHistory['viewed_ads'], 0, 50));
        }

        // Применяем персонализированные фильтры
        $this->applyPersonalizedFilters($builder, $userPreferences, $filters);

        // Применяем сортировку
        $this->applySorting($builder, $sortBy, 'ads');

        return $builder->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Применить фильтры для объявлений
     */
    protected function applyAdFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['city'])) {
            $builder->where('city', $filters['city']);
        }

        if (!empty($filters['price_range'])) {
            [$min, $max] = explode('-', $filters['price_range']);
            $builder->whereBetween('price', [(int)$min, (int)$max]);
        }

        if (!empty($filters['rating'])) {
            $builder->whereHas('user', function($q) use ($filters) {
                $q->where('rating', '>=', (float)$filters['rating']);
            });
        }

        if (!empty($filters['experience'])) {
            $builder->whereHas('user', function($q) use ($filters) {
                $q->where('experience_years', '>=', (int)$filters['experience']);
            });
        }

        if (!empty($filters['service_type'])) {
            $builder->where('specialty', $filters['service_type']);
        }

        if (!empty($filters['availability'])) {
            $builder->where('is_available', true);
        }

        if (!empty($filters['ad_type'])) {
            $builder->where('ad_type', $filters['ad_type']);
        }

        if (!empty($filters['work_format'])) {
            $builder->where('work_format', $filters['work_format']);
        }
    }

    /**
     * Применить фильтры для мастеров
     */
    protected function applyMasterFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['city'])) {
            $builder->where('city', $filters['city']);
        }

        if (!empty($filters['specialty'])) {
            $builder->where('specialty', $filters['specialty']);
        }

        if (!empty($filters['rating'])) {
            $builder->where('rating', '>=', (float)$filters['rating']);
        }

        if (!empty($filters['experience'])) {
            $builder->where('experience_years', '>=', (int)$filters['experience']);
        }

        if (!empty($filters['price_range'])) {
            [$min, $max] = explode('-', $filters['price_range']);
            $builder->whereBetween('min_price', [(int)$min, (int)$max]);
        }

        if (!empty($filters['availability'])) {
            $builder->where('is_available', true);
        }

        if (!empty($filters['verified'])) {
            $builder->where('is_verified', true);
        }
    }

    /**
     * Применить фильтры для услуг
     */
    protected function applyServiceFilters(Builder $builder, array $filters): void
    {
        if (!empty($filters['category'])) {
            $builder->where('category', $filters['category']);
        }

        if (!empty($filters['price_range'])) {
            [$min, $max] = explode('-', $filters['price_range']);
            $builder->whereBetween('price', [(int)$min, (int)$max]);
        }

        if (!empty($filters['duration'])) {
            $builder->where('duration', '<=', (int)$filters['duration']);
        }

        if (!empty($filters['popularity'])) {
            $builder->where('popularity_score', '>=', (float)$filters['popularity']);
        }
    }

    /**
     * Применить персонализированные фильтры
     */
    protected function applyPersonalizedFilters(Builder $builder, array $preferences, array $filters): void
    {
        // Предпочитаемые города
        if (!empty($preferences['preferred_cities']) && empty($filters['city'])) {
            $builder->whereIn('city', $preferences['preferred_cities']);
        }

        // Предпочитаемый ценовой диапазон
        if (!empty($preferences['price_range']) && empty($filters['price_range'])) {
            [$min, $max] = $preferences['price_range'];
            $builder->whereBetween('price', [$min, $max]);
        }

        // Предпочитаемые специализации
        if (!empty($preferences['preferred_specialties'])) {
            $builder->whereIn('specialty', $preferences['preferred_specialties']);
        }

        // Минимальный рейтинг
        if (!empty($preferences['min_rating'])) {
            $builder->whereHas('user', function($q) use ($preferences) {
                $q->where('rating', '>=', $preferences['min_rating']);
            });
        }
    }

    /**
     * Применить сортировку
     */
    protected function applySorting(Builder $builder, SortBy $sortBy, string $tableAlias = null): void
    {
        $expression = $sortBy->getSqlExpression($tableAlias);
        $builder->orderByRaw($expression);
    }

    /**
     * Добавить вычисление расстояния
     */
    protected function addDistanceCalculation(Builder $builder, float $lat, float $lng): Builder
    {
        return $builder->addSelect([
            DB::raw("
                (6371 * acos(cos(radians({$lat})) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians({$lng})) 
                + sin(radians({$lat})) 
                * sin(radians(latitude)))) AS distance
            ")
        ]);
    }

    /**
     * Получить SQL для расчета релевантности
     */
    protected function getRelevanceScore(string $query, array $fieldWeights): string
    {
        if (empty($query)) {
            return 'COALESCE(popularity_score, 0)';
        }

        $terms = $this->parseSearchQuery($query);
        $scoreExpressions = [];

        foreach ($fieldWeights as $field => $weight) {
            foreach ($terms as $term) {
                $scoreExpressions[] = "
                    CASE 
                        WHEN {$field} LIKE '%{$term}%' THEN {$weight}
                        ELSE 0 
                    END
                ";
            }
        }

        return 'COALESCE((' . implode(' + ', $scoreExpressions) . '), 0)';
    }

    /**
     * Получить персонализированный счет релевантности
     */
    protected function getPersonalizedRelevanceScore(string $query, array $history, array $preferences): string
    {
        $baseScore = $this->getRelevanceScore($query, [
            'ads.title' => 3.0,
            'ads.description' => 2.0,
            'ads.specialty' => 2.5,
        ]);

        $personalizedBonuses = [];

        // Бонус за предпочитаемые специализации
        if (!empty($preferences['preferred_specialties'])) {
            $specialties = "'" . implode("','", $preferences['preferred_specialties']) . "'";
            $personalizedBonuses[] = "CASE WHEN specialty IN ({$specialties}) THEN 2.0 ELSE 0 END";
        }

        // Бонус за предпочитаемые города
        if (!empty($preferences['preferred_cities'])) {
            $cities = "'" . implode("','", $preferences['preferred_cities']) . "'";
            $personalizedBonuses[] = "CASE WHEN city IN ({$cities}) THEN 1.5 ELSE 0 END";
        }

        // Штраф за недавно просмотренные похожие объявления
        if (!empty($history['viewed_specialties'])) {
            $viewedSpecialties = "'" . implode("','", $history['viewed_specialties']) . "'";
            $personalizedBonuses[] = "CASE WHEN specialty IN ({$viewedSpecialties}) THEN -0.5 ELSE 0 END";
        }

        if (empty($personalizedBonuses)) {
            return $baseScore;
        }

        return $baseScore . ' + ' . implode(' + ', $personalizedBonuses);
    }

    /**
     * Парсинг поискового запроса
     */
    protected function parseSearchQuery(string $query): array
    {
        // Убираем лишние пробелы и разбиваем на термы
        $terms = array_filter(explode(' ', trim($query)));
        
        // Удаляем слишком короткие термы
        return array_filter($terms, fn($term) => mb_strlen($term) >= 2);
    }

    /**
     * Получить историю поиска пользователя
     */
    protected function getUserSearchHistory(int $userId): array
    {
        return Cache::remember("user_search_history_{$userId}", 3600, function () use ($userId) {
            // Здесь должна быть логика получения истории из БД
            return [
                'viewed_ads' => [],
                'viewed_specialties' => [],
                'search_queries' => [],
            ];
        });
    }

    /**
     * Получить предпочтения пользователя
     */
    protected function getUserPreferences(int $userId): array
    {
        return Cache::remember("user_preferences_{$userId}", 7200, function () use ($userId) {
            // Здесь должна быть логика получения предпочтений из БД
            return [
                'preferred_cities' => [],
                'preferred_specialties' => [],
                'price_range' => null,
                'min_rating' => null,
            ];
        });
    }

    /**
     * Получить автодополнение
     */
    public function getAutocomplete(string $query, SearchType $type, int $limit = 10): array
    {
        if (mb_strlen($query) < $type->getMinQueryLength()) {
            return [];
        }

        $cacheKey = "autocomplete:{$type->value}:" . md5($query);
        
        return Cache::remember($cacheKey, 300, function () use ($query, $type, $limit) {
            return $this->performAutocomplete($query, $type, $limit);
        });
    }

    /**
     * Выполнить автодополнение
     */
    protected function performAutocomplete(string $query, SearchType $type, int $limit): array
    {
        return match($type) {
            SearchType::ADS => $this->getAdAutocomplete($query, $limit),
            SearchType::MASTERS => $this->getMasterAutocomplete($query, $limit),
            SearchType::SERVICES => $this->getServiceAutocomplete($query, $limit),
            SearchType::RECOMMENDATIONS => $this->getPersonalizedAutocomplete($query, $limit),
            default => [],
        };
    }

    /**
     * Автодополнение для объявлений
     */
    protected function getAdAutocomplete(string $query, int $limit): array
    {
        return Ad::where('status', 'active')
            ->where(function($q) use ($query) {
                $q->where('title', 'LIKE', "{$query}%")
                  ->orWhere('specialty', 'LIKE', "{$query}%")
                  ->orWhere('city', 'LIKE', "{$query}%");
            })
            ->select('title as text', 'specialty as category', 'city')
            ->distinct()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Автодополнение для мастеров
     */
    protected function getMasterAutocomplete(string $query, int $limit): array
    {
        return User::where('is_master', true)
            ->where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "{$query}%")
                  ->orWhere('specialty', 'LIKE', "{$query}%")
                  ->orWhere('city', 'LIKE', "{$query}%");
            })
            ->select('name as text', 'specialty as category', 'city')
            ->distinct()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Автодополнение для услуг
     */
    protected function getServiceAutocomplete(string $query, int $limit): array
    {
        return Service::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "{$query}%")
                  ->orWhere('category', 'LIKE', "{$query}%");
            })
            ->select('name as text', 'category')
            ->distinct()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Персонализированное автодополнение
     */
    protected function getPersonalizedAutocomplete(string $query, int $limit): array
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->getAdAutocomplete($query, $limit);
        }

        $userPreferences = $this->getUserPreferences($userId);
        
        // Комбинируем результаты с приоритетом по предпочтениям
        $results = collect();
        
        // Предпочитаемые категории
        if (!empty($userPreferences['preferred_specialties'])) {
            $preferred = Ad::where('status', 'active')
                ->whereIn('specialty', $userPreferences['preferred_specialties'])
                ->where('title', 'LIKE', "{$query}%")
                ->select('title as text', 'specialty as category', 'city')
                ->limit($limit / 2)
                ->get();
            
            $results = $results->concat($preferred);
        }
        
        // Общие результаты
        $general = $this->getAdAutocomplete($query, $limit - $results->count());
        $results = $results->concat($general);
        
        return $results->unique('text')->take($limit)->values()->toArray();
    }

    /**
     * Получить популярные поисковые запросы
     */
    public function getPopularQueries(SearchType $type, int $limit = 10): array
    {
        $cacheKey = "popular_queries:{$type->value}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $limit) {
            // Здесь должна быть логика получения популярных запросов из аналитики
            return match($type) {
                SearchType::ADS => [
                    'классический массаж',
                    'антицеллюлитный массаж',
                    'спортивный массаж',
                    'расслабляющий массаж',
                    'лечебный массаж',
                ],
                SearchType::MASTERS => [
                    'массажист',
                    'мастер массажа',
                    'лечебный массаж',
                    'спа процедуры',
                ],
                SearchType::SERVICES => [
                    'классический массаж',
                    'лимфодренажный массаж',
                    'массаж лица',
                    'массаж спины',
                ],
                default => [],
            };
        });
    }

    /**
     * Сохранить поисковый запрос для аналитики
     */
    public function logSearchQuery(string $query, SearchType $type, int $resultsCount, ?int $userId = null): void
    {
        // Здесь должна быть логика сохранения в БД для аналитики
        // Можно использовать очередь для асинхронной записи
        
        if ($userId) {
            // Обновляем историю поиска пользователя
            Cache::forget("user_search_history_{$userId}");
        }
    }
}