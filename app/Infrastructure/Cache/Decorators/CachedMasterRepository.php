<?php

namespace App\Infrastructure\Cache\Decorators;

use App\Domain\Master\Repositories\MasterRepository;
use App\Infrastructure\Cache\CacheService;
use App\Infrastructure\Cache\Strategies\MasterCacheStrategy;
use App\Infrastructure\Cache\Contracts\CacheableRepositoryInterface;

/**
 * Декоратор репозитория мастеров с кешированием
 */
class CachedMasterRepository implements CacheableRepositoryInterface
{
    private MasterRepository $repository;
    private CacheService $cache;
    private MasterCacheStrategy $strategy;

    public function __construct(
        MasterRepository $repository,
        CacheService $cache,
        MasterCacheStrategy $strategy
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
        $ttl = $ttl ?? $this->strategy->getTTLByType('single_master');

        return $this->cache->rememberWithTags(
            $this->strategy->getTags($id),
            $key,
            $ttl,
            function () use ($id) {
                $master = $this->repository->find($id);
                
                if ($master && $this->strategy->shouldCache($master)) {
                    return $this->strategy->prepareForCache($master);
                }
                
                return $master;
            }
        );
    }

    public function allCached(?int $ttl = null)
    {
        $key = 'masters:all';
        $ttl = $ttl ?? $this->strategy->getTTLByType('masters_list');

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
        $key = "masters:paginated:page:{$page}:perPage:{$perPage}";
        $ttl = $ttl ?? $this->strategy->getTTLByType('masters_list');

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
     * Специальные методы кеширования для мастеров
     */

    /**
     * Получить мастеров по услуге с кешированием
     */
    public function findByServiceCached(int $serviceId, array $filters = [], ?int $ttl = null)
    {
        $key = $this->strategy->getServiceMastersKey($serviceId, $filters);
        $ttl = $ttl ?? $this->strategy->getTTLByType('masters_list');

        return $this->cache->rememberWithTags(
            ['masters', "service:{$serviceId}"],
            $key,
            $ttl,
            function () use ($serviceId, $filters) {
                return $this->repository->findByService($serviceId, $filters);
            }
        );
    }

    /**
     * Получить мастеров по локации с кешированием
     */
    public function findByLocationCached(string $location, array $filters = [], ?int $ttl = null)
    {
        $key = $this->strategy->getLocationMastersKey($location, $filters);
        $ttl = $ttl ?? $this->strategy->getTTLByType('masters_list');

        return $this->cache->rememberWithTags(
            ['masters', 'location_masters'],
            $key,
            $ttl,
            function () use ($location, $filters) {
                return $this->repository->findByLocation($location, $filters);
            }
        );
    }

    /**
     * Получить топ мастеров с кешированием
     */
    public function getTopMastersCached(string $criteria = 'rating', ?int $ttl = null)
    {
        $key = $this->strategy->getTopMastersKey($criteria);
        $ttl = $ttl ?? $this->strategy->getTTLByType('top_masters');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () use ($criteria) {
                return $this->repository->getTopMasters($criteria);
            }
        );
    }

    /**
     * Получить расписание мастера с кешированием
     */
    public function getScheduleCached(int $masterId, ?string $date = null, ?int $ttl = null)
    {
        $key = $this->strategy->getScheduleKey($masterId, $date);
        $ttl = $ttl ?? $this->strategy->getTTLByType('schedule');

        return $this->cache->rememberWithTags(
            ["master:{$masterId}", 'masters_schedules'],
            $key,
            $ttl,
            function () use ($masterId, $date) {
                return $this->repository->getSchedule($masterId, $date);
            }
        );
    }

    /**
     * Получить доступные слоты с кешированием
     */
    public function getAvailableSlotsCached(int $masterId, string $date, ?int $ttl = null)
    {
        $key = $this->strategy->getAvailableSlotsKey($masterId, $date);
        $ttl = $ttl ?? $this->strategy->getTTLByType('available_slots');

        return $this->cache->rememberWithTags(
            ["master:{$masterId}", 'masters_schedules'],
            $key,
            $ttl,
            function () use ($masterId, $date) {
                return $this->repository->getAvailableSlots($masterId, $date);
            }
        );
    }

    /**
     * Поиск мастеров с кешированием
     */
    public function searchCached(array $searchParams, ?int $ttl = null)
    {
        $key = "masters:search:" . md5(serialize($searchParams));
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
     * Получить ближайших мастеров с кешированием
     */
    public function findNearbyCached(float $lat, float $lng, int $radius = 10, ?int $ttl = null)
    {
        $key = "masters:nearby:lat:{$lat}:lng:{$lng}:radius:{$radius}";
        $ttl = $ttl ?? $this->strategy->getTTLByType('nearby_masters');

        return $this->cache->rememberWithTags(
            $this->strategy->getListTags(),
            $key,
            $ttl,
            function () use ($lat, $lng, $radius) {
                return $this->repository->findNearby($lat, $lng, $radius);
            }
        );
    }

    /**
     * Получить премиум мастеров с кешированием
     */
    public function getPremiumMastersCached(?int $ttl = null)
    {
        $key = 'masters:premium';
        $ttl = $ttl ?? $this->strategy->getTTLByType('top_masters');

        return $this->cache->rememberWithTags(
            ['masters', 'premium_masters'],
            $key,
            $ttl,
            function () {
                return $this->repository->getPremiumMasters();
            }
        );
    }

    /**
     * Получить верифицированных мастеров с кешированием
     */
    public function getVerifiedMastersCached(?int $ttl = null)
    {
        $key = 'masters:verified';
        $ttl = $ttl ?? $this->strategy->getTTLByType('top_masters');

        return $this->cache->rememberWithTags(
            ['masters', 'verified_masters'],
            $key,
            $ttl,
            function () {
                return $this->repository->getVerifiedMasters();
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

    /**
     * Инвалидация расписания мастера
     */
    public function invalidateScheduleCache(int $masterId): bool
    {
        return $this->strategy->invalidateSchedule($masterId);
    }

    public function getCacheStats(): array
    {
        return [
            'repository' => 'MasterRepository',
            'total_cache_stats' => $this->cache->getStats(),
            'strategy' => get_class($this->strategy),
            'cached_keys_pattern' => 'master:*, masters:*',
        ];
    }

    /**
     * Методы для теплого кеша (предварительной загрузки)
     */
    
    /**
     * Прогреть кеш для популярных мастеров
     */
    public function warmPopularMastersCache(): void
    {
        $this->getTopMastersCached('rating');
        $this->getTopMastersCached('bookings_count');
        $this->getPremiumMastersCached();
        $this->getVerifiedMastersCached();
    }

    /**
     * Прогреть кеш для основных услуг
     */
    public function warmServicesCache(array $serviceIds): void
    {
        foreach ($serviceIds as $serviceId) {
            $this->findByServiceCached($serviceId);
        }
    }
}