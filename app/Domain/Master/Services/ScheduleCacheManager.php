<?php

namespace App\Domain\Master\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Сервис управления кешем расписания
 */
class ScheduleCacheManager
{
    private const CACHE_TTL = 1800; // 30 минут
    private const CACHE_PREFIX = 'master_schedule_';

    /**
     * Получить данные из кеша
     */
    public function get(string $key, callable $callback = null)
    {
        $cacheKey = $this->buildCacheKey($key);
        
        if ($callback) {
            return Cache::remember($cacheKey, self::CACHE_TTL, $callback);
        }
        
        return Cache::get($cacheKey);
    }

    /**
     * Сохранить данные в кеш
     */
    public function put(string $key, $value, int $ttl = null): void
    {
        $cacheKey = $this->buildCacheKey($key);
        $ttl = $ttl ?? self::CACHE_TTL;
        
        Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Очистить кеш расписания мастера
     */
    public function clearMasterSchedule(int $masterId): void
    {
        $keys = [
            "master_{$masterId}",
            "master_{$masterId}_stats",
            "master_{$masterId}_working_days",
            "master_{$masterId}_slots",
        ];
        
        foreach ($keys as $key) {
            $this->forget($key);
        }
    }

    /**
     * Очистить кеш слотов для конкретной даты
     */
    public function clearSlotsCache(int $masterId, string $date): void
    {
        $this->forget("slots_{$masterId}_{$date}");
    }

    /**
     * Удалить запись из кеша
     */
    public function forget(string $key): void
    {
        $cacheKey = $this->buildCacheKey($key);
        Cache::forget($cacheKey);
    }

    /**
     * Получить расписание мастера с кешированием
     */
    public function getMasterSchedule(int $masterId, callable $dataCallback): array
    {
        return $this->get("master_{$masterId}", $dataCallback);
    }

    /**
     * Получить статистику с кешированием
     */
    public function getScheduleStats(int $masterId, callable $dataCallback): array
    {
        return $this->get("master_{$masterId}_stats", $dataCallback);
    }

    /**
     * Получить рабочие дни с кешированием
     */
    public function getWorkingDays(int $masterId, callable $dataCallback): array
    {
        return $this->get("master_{$masterId}_working_days", $dataCallback);
    }

    /**
     * Получить слоты с кешированием
     */
    public function getSlots(int $masterId, string $date, callable $dataCallback): array
    {
        return $this->get("slots_{$masterId}_{$date}", $dataCallback);
    }

    /**
     * Построить ключ кеша
     */
    private function buildCacheKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Очистить весь кеш расписаний
     */
    public function clearAll(): void
    {
        Cache::forget(self::CACHE_PREFIX . '*');
    }

    /**
     * Получить информацию о кеше
     */
    public function getCacheInfo(int $masterId): array
    {
        $keys = [
            'schedule' => "master_{$masterId}",
            'stats' => "master_{$masterId}_stats",
            'working_days' => "master_{$masterId}_working_days",
        ];
        
        $info = [];
        foreach ($keys as $type => $key) {
            $cacheKey = $this->buildCacheKey($key);
            $info[$type] = [
                'key' => $cacheKey,
                'exists' => Cache::has($cacheKey),
            ];
        }
        
        return $info;
    }
}