<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Closure;

/**
 * Сервис для управления кешированием
 */
class CacheService
{
    /**
     * Префикс для ключей кеша
     */
    protected string $prefix = 'spa_platform';

    /**
     * Время жизни кеша по умолчанию (секунды)
     */
    protected int $defaultTTL = 3600;

    /**
     * Получить или установить значение в кеше
     */
    public function remember(string $key, $ttl, Closure $callback)
    {
        $cacheKey = $this->getCacheKey($key);
        
        return Cache::remember($cacheKey, $ttl ?? $this->defaultTTL, $callback);
    }

    /**
     * Получить или установить значение с тегами
     */
    public function rememberWithTags(array $tags, string $key, $ttl, Closure $callback)
    {
        $cacheKey = $this->getCacheKey($key);
        
        return Cache::tags($tags)->remember($cacheKey, $ttl ?? $this->defaultTTL, $callback);
    }

    /**
     * Кешировать модель
     */
    public function rememberModel(Model $model, ?int $ttl = null): Model
    {
        $key = $this->getModelCacheKey($model);
        $tags = $this->getModelTags($model);
        
        return $this->rememberWithTags($tags, $key, $ttl, function () use ($model) {
            return $model->fresh();
        });
    }

    /**
     * Кешировать коллекцию
     */
    public function rememberCollection(string $key, $query, ?int $ttl = null)
    {
        return $this->remember($key, $ttl, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Кешировать пагинацию
     */
    public function rememberPaginated(string $key, $query, int $perPage = 20, ?int $ttl = null)
    {
        $page = request()->get('page', 1);
        $paginatedKey = "{$key}:page:{$page}:perPage:{$perPage}";
        
        return $this->remember($paginatedKey, $ttl, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });
    }

    /**
     * Инвалидировать кеш модели
     */
    public function forgetModel(Model $model): bool
    {
        $key = $this->getModelCacheKey($model);
        $tags = $this->getModelTags($model);
        
        // Очищаем по ключу
        Cache::forget($this->getCacheKey($key));
        
        // Очищаем по тегам
        Cache::tags($tags)->flush();
        
        return true;
    }

    /**
     * Инвалидировать кеш по тегам
     */
    public function forgetByTags(array $tags): bool
    {
        return Cache::tags($tags)->flush();
    }

    /**
     * Очистить весь кеш
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Прогреть кеш
     */
    public function warm(array $keys): void
    {
        foreach ($keys as $key => $callback) {
            if (is_callable($callback)) {
                $this->remember($key, $this->defaultTTL, $callback);
            }
        }
    }

    /**
     * Получить статистику кеша
     */
    public function getStats(): array
    {
        $stats = [
            'hits' => 0,
            'misses' => 0,
            'memory_usage' => 0,
            'keys_count' => 0,
        ];

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Redis::connection();
            $info = $redis->info();
            
            $stats['hits'] = $info['keyspace_hits'] ?? 0;
            $stats['misses'] = $info['keyspace_misses'] ?? 0;
            $stats['memory_usage'] = $info['used_memory_human'] ?? '0B';
            $stats['keys_count'] = $redis->dbsize();
        }

        return $stats;
    }

    /**
     * Блокировка для предотвращения stampede эффекта
     */
    public function lock(string $key, $ttl, Closure $callback)
    {
        $lockKey = "lock:{$key}";
        $lock = Cache::lock($lockKey, 10);

        if ($lock->get()) {
            try {
                $result = $this->remember($key, $ttl, $callback);
                $lock->release();
                return $result;
            } catch (\Exception $e) {
                $lock->release();
                throw $e;
            }
        }

        // Если не удалось получить блокировку, ждем и пытаемся получить из кеша
        sleep(1);
        return Cache::get($this->getCacheKey($key));
    }

    /**
     * Кеширование с предварительной загрузкой
     */
    public function preload(string $key, $ttl, Closure $callback)
    {
        $cacheKey = $this->getCacheKey($key);
        $preloadKey = "{$cacheKey}:preload";
        
        // Проверяем основной кеш
        if (Cache::has($cacheKey)) {
            // Проверяем, не пора ли обновить
            $remaining = Cache::getStore()->getConnection()->ttl($cacheKey);
            
            // Если осталось меньше 20% времени жизни, обновляем в фоне
            if ($remaining < ($ttl * 0.2)) {
                dispatch(function () use ($preloadKey, $callback, $ttl) {
                    $result = $callback();
                    Cache::put($preloadKey, $result, $ttl);
                })->afterResponse();
            }
            
            return Cache::get($cacheKey);
        }
        
        // Если кеша нет, генерируем
        $result = $callback();
        Cache::put($cacheKey, $result, $ttl);
        
        return $result;
    }

    /**
     * Получить ключ кеша
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }

    /**
     * Получить ключ кеша для модели
     */
    protected function getModelCacheKey(Model $model): string
    {
        $class = get_class($model);
        $id = $model->getKey();
        
        return "model:{$class}:{$id}";
    }

    /**
     * Получить теги для модели
     */
    protected function getModelTags(Model $model): array
    {
        $class = class_basename($model);
        $table = $model->getTable();
        
        return [
            'models',
            "model:{$class}",
            "table:{$table}",
        ];
    }

    /**
     * Настройки TTL для разных типов данных
     */
    public function getTTL(string $type): int
    {
        $ttlConfig = [
            'masters_list' => 300,      // 5 минут
            'master_profile' => 600,    // 10 минут
            'services' => 3600,         // 1 час
            'bookings' => 60,           // 1 минута
            'reviews' => 1800,          // 30 минут
            'statistics' => 900,        // 15 минут
            'configuration' => 86400,   // 24 часа
        ];

        return $ttlConfig[$type] ?? $this->defaultTTL;
    }
}