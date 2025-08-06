<?php

namespace App\Domain\Search\Repositories;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use App\Domain\Search\Repositories\AdSearchRepository;
use App\Domain\Search\Repositories\MasterSearchRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный основной репозиторий поиска
 * Делегирует работу специализированным репозиториям
 */
class SearchRepository
{
    protected AdSearchRepository $adSearchRepository;
    protected MasterSearchRepository $masterSearchRepository;

    public function __construct(
        AdSearchRepository $adSearchRepository,
        MasterSearchRepository $masterSearchRepository
    ) {
        $this->adSearchRepository = $adSearchRepository;
        $this->masterSearchRepository = $masterSearchRepository;
    }

    /**
     * Выполнить поиск с кешированием
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
        
        $cacheKey = $this->generateCacheKey($query, $type, $filters, $sortBy, $page, $perPage, $location);
        $cacheTTL = $this->getCacheTTL($type);

        return Cache::remember($cacheKey, $cacheTTL, function () use (
            $query, $type, $filters, $sortBy, $page, $perPage, $location
        ) {
            return $this->performSearch($query, $type, $filters, $sortBy, $page, $perPage, $location);
        });
    }

    /**
     * Выполнить поиск без кеширования
     */
    public function performSearch(
        string $query,
        SearchType $type,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): LengthAwarePaginator {
        
        $startTime = microtime(true);
        
        $result = match($type) {
            SearchType::ADS => $this->adSearchRepository->search(
                $query, $filters, $sortBy, $page, $perPage, $location
            ),
            SearchType::MASTERS => $this->masterSearchRepository->search(
                $query, $filters, $sortBy, $page, $perPage, $location
            ),
            default => throw new \InvalidArgumentException("Unsupported search type: {$type->value}")
        };

        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info('Search performed', [
            'type' => $type->value,
            'query' => $query,
            'total_results' => $result->total(),
            'duration_ms' => $duration,
            'page' => $page,
            'per_page' => $perPage
        ]);

        return $result;
    }

    /**
     * Получить популярные результаты
     */
    public function getPopular(SearchType $type, int $limit = 10): \Illuminate\Support\Collection
    {
        $cacheKey = "popular_{$type->value}_{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($type, $limit) {
            return match($type) {
                SearchType::ADS => $this->adSearchRepository->getPopular($limit),
                SearchType::MASTERS => $this->masterSearchRepository->getPopular($limit),
                default => collect()
            };
        });
    }

    /**
     * Получить недавние результаты
     */
    public function getRecent(SearchType $type, int $limit = 10): \Illuminate\Support\Collection
    {
        $cacheKey = "recent_{$type->value}_{$limit}";
        
        return Cache::remember($cacheKey, 600, function () use ($type, $limit) {
            return match($type) {
                SearchType::ADS => $this->adSearchRepository->getRecent($limit),
                SearchType::MASTERS => $this->masterSearchRepository->getRecent($limit),
                default => collect()
            };
        });
    }

    /**
     * Найти похожие элементы
     */
    public function findSimilar(SearchType $type, $item, int $limit = 5): \Illuminate\Support\Collection
    {
        return match($type) {
            SearchType::ADS => $this->adSearchRepository->findSimilar($item, $limit),
            SearchType::MASTERS => $this->masterSearchRepository->findSimilar($item, $limit),
            default => collect()
        };
    }

    /**
     * Получить предложения для автодополнения
     */
    public function getSuggestions(SearchType $type, string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "suggestions_{$type->value}_" . md5($query) . "_{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $query, $limit) {
            return match($type) {
                SearchType::ADS => $this->adSearchRepository->getSuggestions($query, $limit),
                SearchType::MASTERS => $this->masterSearchRepository->getSuggestions($query, $limit),
                default => []
            };
        });
    }

    /**
     * Получить статистику поиска
     */
    public function getSearchStats(): array
    {
        return Cache::remember('search_stats', 3600, function () {
            return [
                'total_ads' => $this->adSearchRepository->getRecent(1)->count() > 0 ? 
                    \App\Domain\Ad\Models\Ad::where('is_active', true)->count() : 0,
                'total_masters' => $this->masterSearchRepository->getRecent(1)->count() > 0 ? 
                    \App\Domain\Master\Models\MasterProfile::where('is_active', true)->count() : 0,
                'popular_ads' => $this->adSearchRepository->getPopular(5)->pluck('title'),
                'top_masters' => $this->masterSearchRepository->getTop(5)->pluck('user.name'),
            ];
        });
    }

    /**
     * Очистить кеш поиска
     */
    public function clearCache(SearchType $type = null): void
    {
        if ($type) {
            $patterns = [
                "search_{$type->value}_*",
                "popular_{$type->value}_*",
                "recent_{$type->value}_*",
                "suggestions_{$type->value}_*"
            ];
        } else {
            $patterns = [
                'search_*',
                'popular_*', 
                'recent_*',
                'suggestions_*',
                'search_stats'
            ];
        }

        foreach ($patterns as $pattern) {
            // Здесь нужна реализация очистки по паттерну
            // зависит от драйвера кеша
        }
        
        Log::info('Search cache cleared', ['type' => $type?->value ?? 'all']);
    }

    /**
     * Сгенерировать ключ кеша
     */
    protected function generateCacheKey(
        string $query,
        SearchType $type,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage,
        ?array $location
    ): string {
        $data = [
            'query' => $query,
            'type' => $type->value,
            'filters' => $filters,
            'sort' => $sortBy->value,
            'page' => $page,
            'per_page' => $perPage,
            'location' => $location
        ];
        
        return 'search_' . md5(serialize($data));
    }

    /**
     * Получить время жизни кеша
     */
    protected function getCacheTTL(SearchType $type): int
    {
        return match($type) {
            SearchType::ADS => 300,    // 5 минут
            SearchType::MASTERS => 600, // 10 минут
            default => 300
        };
    }

    /**
     * Записать поисковый запрос для аналитики
     */
    public function logSearchQuery(string $query, SearchType $type, int $resultsCount): void
    {
        // Асинхронно записываем в аналитику
        \Illuminate\Support\Facades\Queue::push(function () use ($query, $type, $resultsCount) {
            try {
                \DB::table('search_queries')->insert([
                    'query' => $query,
                    'type' => $type->value,
                    'results_count' => $resultsCount,
                    'created_at' => now(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log search query', ['error' => $e->getMessage()]);
            }
        });
    }

    /**
     * Получить популярные поисковые запросы
     */
    public function getPopularQueries(SearchType $type, int $limit = 10): array
    {
        return Cache::remember("popular_queries_{$type->value}_{$limit}", 3600, function () use ($type, $limit) {
            try {
                return \DB::table('search_queries')
                    ->where('type', $type->value)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->select('query', \DB::raw('COUNT(*) as count'))
                    ->groupBy('query')
                    ->orderBy('count', 'desc')
                    ->limit($limit)
                    ->pluck('query')
                    ->toArray();
            } catch (\Exception $e) {
                Log::error('Failed to get popular queries', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }
}