<?php

namespace App\Support\Traits;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

/**
 * Трейт для добавления кеширования в модели
 */
trait Cacheable
{
    /**
     * Boot the cacheable trait
     */
    public static function bootCacheable()
    {
        // Очищаем кеш при создании
        static::created(function ($model) {
            $model->clearCache();
            $model->clearListCache();
        });

        // Очищаем кеш при обновлении
        static::updated(function ($model) {
            $model->clearCache();
            $model->clearListCache();
        });

        // Очищаем кеш при удалении
        static::deleted(function ($model) {
            $model->clearCache();
            $model->clearListCache();
        });
    }

    /**
     * Получить модель из кеша или БД
     */
    public static function findCached(mixed $id, ?int $ttl = null)
    {
        $instance = new static;
        $cacheKey = $instance->getCacheKey($id);
        $ttl = $ttl ?? $instance->getCacheTTL();

        return Cache::tags($instance->getCacheTags())->remember(
            $cacheKey,
            $ttl,
            function () use ($id) {
                return static::find($id);
            }
        );
    }

    /**
     * Получить модель с отношениями из кеша
     */
    public static function findWithCached(mixed $id, array $relations, ?int $ttl = null)
    {
        $instance = new static;
        $cacheKey = $instance->getCacheKey($id) . ':with:' . implode(',', $relations);
        $ttl = $ttl ?? $instance->getCacheTTL();

        return Cache::tags($instance->getCacheTags())->remember(
            $cacheKey,
            $ttl,
            function () use ($id, $relations) {
                return static::with($relations)->find($id);
            }
        );
    }

    /**
     * Кешировать коллекцию
     */
    public static function getCached(string $key, mixed $query, ?int $ttl = null)
    {
        $instance = new static;
        $cacheKey = $instance->getCollectionCacheKey($key);
        $ttl = $ttl ?? $instance->getCacheTTL();

        return Cache::tags($instance->getCacheTags())->remember(
            $cacheKey,
            $ttl,
            function () use ($query) {
                return $query->get();
            }
        );
    }

    /**
     * Очистить кеш модели
     */
    public function clearCache()
    {
        $cacheKey = $this->getCacheKey($this->getKey());
        
        // Очищаем основной кеш
        Cache::forget($cacheKey);
        
        // Очищаем кеш с отношениями
        Cache::tags($this->getCacheTags())->flush();
        
        return $this;
    }

    /**
     * Очистить кеш списков
     */
    public function clearListCache()
    {
        Cache::tags($this->getListCacheTags())->flush();
        return $this;
    }

    /**
     * Получить ключ кеша для модели
     */
    protected function getCacheKey(mixed $id = null)
    {
        $id = $id ?? $this->getKey();
        return $this->getCachePrefix() . ':' . $id;
    }

    /**
     * Получить ключ кеша для коллекции
     */
    protected function getCollectionCacheKey($key)
    {
        return $this->getCachePrefix() . ':collection:' . $key;
    }

    /**
     * Получить префикс кеша
     */
    protected function getCachePrefix()
    {
        return 'model:' . class_basename($this);
    }

    /**
     * Получить теги кеша
     */
    protected function getCacheTags()
    {
        return [
            'models',
            class_basename($this),
            $this->getTable(),
        ];
    }

    /**
     * Получить теги для кеша списков
     */
    protected function getListCacheTags()
    {
        return array_merge($this->getCacheTags(), [
            class_basename($this) . '_list',
        ]);
    }

    /**
     * Получить TTL кеша
     */
    protected function getCacheTTL()
    {
        return property_exists($this, 'cacheTTL') 
            ? $this->cacheTTL 
            : config('cache.default_ttl', 3600);
    }

    /**
     * Запомнить результат запроса
     */
    public function rememberCache($key, $ttl, $callback)
    {
        $cacheKey = $this->getCacheKey() . ':' . $key;
        
        return Cache::tags($this->getCacheTags())->remember(
            $cacheKey,
            $ttl ?? $this->getCacheTTL(),
            $callback
        );
    }

    /**
     * Прогреть кеш модели
     */
    public function warmCache()
    {
        // Кешируем основную модель
        $this->findCached($this->getKey());
        
        // Кешируем с основными отношениями
        if (property_exists($this, 'cacheRelations')) {
            $this->findWithCached($this->getKey(), $this->cacheRelations);
        }
        
        return $this;
    }

    /**
     * Массовая очистка кеша
     */
    public static function clearAllCache()
    {
        $instance = new static;
        Cache::tags($instance->getCacheTags())->flush();
    }
}