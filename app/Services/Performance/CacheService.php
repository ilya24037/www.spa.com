<?php

namespace App\Services\Performance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Сервис кеширования по стандартам Wildberries
 * Оптимизация запросов и снижение нагрузки на БД
 */
class CacheService
{
    /**
     * Время жизни кеша по умолчанию (секунды)
     */
    public const DEFAULT_TTL = 3600; // 1 час
    public const SHORT_TTL = 300;    // 5 минут
    public const LONG_TTL = 86400;   // 24 часа
    public const WEEK_TTL = 604800;  // 7 дней

    /**
     * Префиксы для разных типов кеша
     */
    public const PREFIXES = [
        'masters' => 'masters',
        'ads' => 'ads',
        'categories' => 'categories',
        'search' => 'search',
        'stats' => 'stats',
        'user' => 'user',
        'system' => 'system'
    ];

    /**
     * Кеширование списка мастеров с фильтрами
     */
    public function cacheMastersList(array $filters, \Closure $callback): mixed
    {
        $cacheKey = $this->generateKey('masters', 'list', $filters);
        
        return Cache::remember($cacheKey, self::SHORT_TTL, function () use ($callback) {
            $startTime = microtime(true);
            $result = $callback();
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Masters list cached', [
                'duration_ms' => round($duration, 2),
                'count' => is_array($result) ? count($result) : 'unknown'
            ]);
            
            return $result;
        });
    }

    /**
     * Кеширование данных мастера
     */
    public function cacheMasterData(int $masterId, \Closure $callback): mixed
    {
        $cacheKey = $this->generateKey('masters', 'profile', $masterId);
        
        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($callback, $masterId) {
            $startTime = microtime(true);
            $result = $callback();
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Master data cached', [
                'master_id' => $masterId,
                'duration_ms' => round($duration, 2)
            ]);
            
            return $result;
        });
    }

    /**
     * Кеширование результатов поиска
     */
    public function cacheSearchResults(string $query, array $filters, \Closure $callback): mixed
    {
        $cacheKey = $this->generateKey('search', 'results', [
            'query' => $query,
            'filters' => $filters
        ]);
        
        return Cache::remember($cacheKey, self::SHORT_TTL, function () use ($callback, $query) {
            $startTime = microtime(true);
            $result = $callback();
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Search results cached', [
                'query' => $query,
                'duration_ms' => round($duration, 2),
                'results_count' => $result['total'] ?? 0
            ]);
            
            return $result;
        });
    }

    /**
     * Кеширование категорий (долгий кеш)
     */
    public function cacheCategories(\Closure $callback): mixed
    {
        $cacheKey = $this->generateKey('categories', 'all');
        
        return Cache::remember($cacheKey, self::LONG_TTL, function () use ($callback) {
            return $callback();
        });
    }

    /**
     * Кеширование статистики пользователя
     */
    public function cacheUserStats(int $userId, \Closure $callback): mixed
    {
        $cacheKey = $this->generateKey('user', 'stats', $userId);
        
        return Cache::remember($cacheKey, self::SHORT_TTL, function () use ($callback, $userId) {
            $result = $callback();
            
            Log::info('User stats cached', [
                'user_id' => $userId,
                'stats' => $result
            ]);
            
            return $result;
        });
    }

    /**
     * Кеширование конфигурации системы
     */
    public function cacheSystemConfig(string $key, \Closure $callback): mixed
    {
        $cacheKey = $this->generateKey('system', 'config', $key);
        
        return Cache::remember($cacheKey, self::WEEK_TTL, function () use ($callback) {
            return $callback();
        });
    }

    /**
     * Инвалидация кеша мастера
     */
    public function invalidateMasterCache(int $masterId): void
    {
        $patterns = [
            $this->generateKey('masters', 'profile', $masterId),
            $this->generateKey('masters', 'list', '*'),
            $this->generateKey('search', '*'),
        ];

        $this->invalidateByPatterns($patterns);
        
        Log::info('Master cache invalidated', ['master_id' => $masterId]);
    }

    /**
     * Инвалидация кеша поиска
     */
    public function invalidateSearchCache(): void
    {
        $pattern = $this->generateKey('search', '*');
        $this->invalidateByPattern($pattern);
        
        Log::info('Search cache invalidated');
    }

    /**
     * Инвалидация кеша пользователя
     */
    public function invalidateUserCache(int $userId): void
    {
        $patterns = [
            $this->generateKey('user', 'stats', $userId),
            $this->generateKey('user', 'profile', $userId),
        ];

        $this->invalidateByPatterns($patterns);
        
        Log::info('User cache invalidated', ['user_id' => $userId]);
    }

    /**
     * Массовая предзагрузка в кеш
     */
    public function warmupCache(): void
    {
        $startTime = microtime(true);
        
        try {
            // Кешируем категории
            $this->cacheCategories(function () {
                return \App\Models\Category::all();
            });

            // Кешируем популярных мастеров
            $this->cacheMastersList(['popular' => true], function () {
                return \App\Domain\Master\Models\MasterProfile::where('is_popular', true)
                    ->with(['photos', 'services'])
                    ->limit(20)
                    ->get();
            });

            // Кешируем системные настройки
            $this->cacheSystemConfig('app_settings', function () {
                return config('app');
            });

            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Cache warmup completed', [
                'duration_ms' => round($duration, 2)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cache warmup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получение статистики кеша
     */
    public function getCacheStats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info('memory');
                
                return [
                    'driver' => 'redis',
                    'memory_used' => $info['used_memory_human'] ?? 'unknown',
                    'memory_peak' => $info['used_memory_peak_human'] ?? 'unknown',
                    'hits' => $redis->info('stats')['keyspace_hits'] ?? 0,
                    'misses' => $redis->info('stats')['keyspace_misses'] ?? 0,
                ];
            }
            
            return [
                'driver' => config('cache.default'),
                'status' => 'active'
            ];
            
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Очистка всего кеша приложения
     */
    public function flushAppCache(): void
    {
        $startTime = microtime(true);
        
        Cache::flush();
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        Log::info('Application cache flushed', [
            'duration_ms' => round($duration, 2)
        ]);
    }

    /**
     * Генерация ключа кеша
     */
    private function generateKey(string $prefix, string $type, mixed $identifier = null): string
    {
        $parts = [config('app.name', 'spa'), $prefix, $type];
        
        if ($identifier !== null) {
            if (is_array($identifier)) {
                $parts[] = md5(serialize($identifier));
            } else {
                $parts[] = $identifier;
            }
        }
        
        return implode(':', $parts);
    }

    /**
     * Инвалидация по паттернам
     */
    private function invalidateByPatterns(array $patterns): void
    {
        foreach ($patterns as $pattern) {
            $this->invalidateByPattern($pattern);
        }
    }

    /**
     * Инвалидация по паттерну
     */
    private function invalidateByPattern(string $pattern): void
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                // Для других драйверов кеша используем tags или ручную очистку
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получение или установка кеша с блокировкой
     * Предотвращает cache stampede
     */
    public function lockAndCache(string $key, \Closure $callback, int $ttl = self::DEFAULT_TTL): mixed
    {
        $lockKey = $key . ':lock';
        
        // Пытаемся получить из кеша
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        // Пытаемся получить блокировку
        $lock = Cache::lock($lockKey, 30); // 30 секунд блокировка
        
        try {
            $lock->block(5); // Ждем максимум 5 секунд
            
            // Проверяем снова (мог закешироваться пока ждали)
            $cached = Cache::get($key);
            if ($cached !== null) {
                return $cached;
            }
            
            // Выполняем callback и кешируем
            $result = $callback();
            Cache::put($key, $result, $ttl);
            
            return $result;
            
        } finally {
            $lock->release();
        }
    }
}