<?php

namespace App\Infrastructure\Cache\Decorators;

use App\Domain\Ad\Repositories\AdRepository;
use App\Infrastructure\Cache\CacheService;
use App\Infrastructure\Cache\Strategies\AdCacheStrategy;
use App\Infrastructure\Cache\Contracts\CacheableRepositoryInterface;

/**
 * Декоратор репозитория объявлений с кешированием
 */
class CachedAdRepository implements CacheableRepositoryInterface
{
    private AdRepository $repository;
    private CacheService $cache;
    private AdCacheStrategy $strategy;

    public function __construct(
        AdRepository $repository,
        CacheService $cache,
        AdCacheStrategy $strategy
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->strategy = $strategy;
    }

    /**
     * Проксируем все методы оригинального репозитория
     */
    public function __call($method, $arguments)
    {
        return $this->repository->$method(...$arguments);
    }

    public function findCached($id, ?int $ttl = null)
    {
        $key = $this->strategy->getKey($id);
        $ttl = $ttl ?? $this->strategy->getTTLByType('single_ad');

        return $this->cache->rememberWithTags(
            $this->strategy->getTags($id),
            $key,
            $ttl,
            function () use ($id) {
                $ad = $this->repository->find($id);
                
                if ($ad && $this->strategy->shouldCache($ad)) {
                    return $this->strategy->prepareForCache($ad);
                }
                
                return $ad;
            }
        );
    }

    public function allCached(?int $ttl = null)
    {
        $key = 'ads:all';
        $ttl = $ttl ?? $this->strategy->getTTLByType('category_ads');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () {
                return $this->repository->all();
            }
        );
    }

    public function paginatedCached(int $perPage = 15, ?int $ttl = null)
    {
        $page = request()->get('page', 1);
        $key = "ads:paginated:page:{$page}:perPage:{$perPage}";
        $ttl = $ttl ?? $this->strategy->getTTLByType('category_ads');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () use ($perPage) {
                return $this->repository->paginate($perPage);
            }
        );
    }

    public function whereCached(array $conditions, ?int $ttl = null)
    {
        $key = $this->strategy->getKey($conditions);
        $ttl = $ttl ?? $this->strategy->getTTLByType('search_results');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () use ($conditions) {
                return $this->repository->where($conditions);
            }
        );
    }

    /**
     * Специальные методы кеширования для объявлений
     */

    /**
     * Получить объявления пользователя с кешированием
     */
    public function findByUserCached(int $userId, array $filters = [], ?int $ttl = null)
    {
        $key = $this->strategy->getUserAdsKey($userId, $filters);
        $ttl = $ttl ?? $this->strategy->getTTLByType('user_ads');

        return $this->cache->rememberWithTags(
            ['ads', "user:{$userId}"],
            $key,
            $ttl,
            function () use ($userId, $filters) {
                return $this->repository->findByUser($userId, $filters);
            }
        );
    }

    /**
     * Получить объявления по категории с кешированием
     */
    public function findByCategoryCached(string $category, array $filters = [], ?int $ttl = null)
    {
        $key = $this->strategy->getCategoryAdsKey($category, $filters);
        $ttl = $ttl ?? $this->strategy->getTTLByType('category_ads');

        return $this->cache->rememberWithTags(
            ['ads', "category:{$category}"],
            $key,
            $ttl,
            function () use ($category, $filters) {
                return $this->repository->findByCategory($category, $filters);
            }
        );
    }

    /**
     * Поиск объявлений с кешированием
     */
    public function searchCached(array $searchParams, ?int $ttl = null)
    {
        $key = $this->strategy->getSearchKey($searchParams);
        $ttl = $ttl ?? $this->strategy->getTTLByType('search_results');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () use ($searchParams) {
                return $this->repository->search($searchParams);
            }
        );
    }

    /**
     * Получить популярные объявления с кешированием
     */
    public function getPopularCached(?int $ttl = null)
    {
        $key = 'ads:popular';
        $ttl = $ttl ?? $this->strategy->getTTLByType('popular_ads');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () {
                return $this->repository->getPopular();
            }
        );
    }

    /**
     * Получить рекомендуемые объявления с кешированием
     */
    public function getFeaturedCached(?int $ttl = null)
    {
        $key = 'ads:featured';
        $ttl = $ttl ?? $this->strategy->getTTLByType('featured_ads');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () {
                return $this->repository->getFeatured();
            }
        );
    }

    public function invalidateCache($id): bool
    {
        return $this->strategy->invalidate($id);
    }

    public function invalidateAllCache(): bool
    {
        return $this->cache->forgetByTags($this->strategy->getListTags());
    }

    public function getCacheStats(): array
    {
        return [
            'repository' => 'AdRepository',
            'total_cache_stats' => $this->cache->getStats(),
            'strategy' => get_class($this->strategy),
            'cached_keys_pattern' => 'ad:*, ads:*',
        ];
    }

    /**
     * Методы для теплого кеша (предварительной загрузки)
     */
    
    /**
     * Прогреть кеш для популярных объявлений
     */
    public function warmPopularAdsCache(): void
    {
        $this->getPopularCached();
        $this->getFeaturedCached();
    }

    /**
     * Прогреть кеш для основных категорий
     */
    public function warmCategoriesCache(array $categories): void
    {
        foreach ($categories as $category) {
            $this->findByCategoryCached($category);
        }
    }
}