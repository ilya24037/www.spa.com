<?php

namespace App\Infrastructure\Cache\Strategies;

use App\Infrastructure\Cache\Contracts\CacheStrategyInterface;
use App\Domain\Master\Models\MasterProfile;

/**
 * Стратегия кеширования для мастеров
 */
class MasterCacheStrategy implements CacheStrategyInterface
{
    private int $defaultTTL = 600; // 10 минут
    
    public function getKey($identifier): string
    {
        if ($identifier instanceof MasterProfile) {
            return "master:{$identifier->id}";
        }
        
        if (is_array($identifier)) {
            // Для фильтрованных запросов
            return 'masters:' . md5(serialize($identifier));
        }
        
        return "master:{$identifier}";
    }

    public function getTags($identifier): array
    {
        $tags = ['masters'];
        
        if ($identifier instanceof MasterProfile) {
            $tags[] = "master:{$identifier->id}";
            $tags[] = "user:{$identifier->user_id}";
            $tags[] = "status:{$identifier->status}";
            
            if ($identifier->is_premium) {
                $tags[] = 'premium_masters';
            }
            
            if ($identifier->is_verified) {
                $tags[] = 'verified_masters';
            }
        }
        
        return $tags;
    }

    public function getTTL(): int
    {
        return $this->defaultTTL;
    }

    public function shouldCache($data): bool
    {
        // Не кешируем неактивных мастеров
        if ($data instanceof MasterProfile && $data->status !== 'active') {
            return false;
        }
        
        return true;
    }

    public function prepareForCache($data)
    {
        if ($data instanceof MasterProfile) {
            // Предзагружаем связи для кеширования
            $data->load([
                'user', 
                'photos', 
                'videos',
                'services',
                'schedule',
                'reviews' => function($query) {
                    $query->latest()->limit(10);
                }
            ]);
            
            // Удаляем чувствительные данные
            $cached = $data->toArray();
            unset($cached['admin_notes']);
            unset($cached['internal_notes']);
            
            // Если контакты скрыты, убираем их из кеша
            if (!$data->show_contacts) {
                unset($cached['phone']);
                unset($cached['whatsapp']);
                unset($cached['telegram']);
            }
            
            return $cached;
        }
        
        return $data;
    }

    public function processFromCache($data)
    {
        return $data;
    }

    public function invalidate($identifier): bool
    {
        $tags = $this->getTags($identifier);
        
        // Инвалидируем кеш мастера и связанные кеши
        app('cache')->tags($tags)->flush();
        
        // Инвалидируем списки мастеров
        app('cache')->tags(['masters_lists'])->flush();
        
        return true;
    }

    /**
     * Специальные методы для работы с мастерами
     */
    
    /**
     * Ключ для списка мастеров по услуге
     */
    public function getServiceMastersKey(int $serviceId, array $filters = []): string
    {
        $filterKey = empty($filters) ? 'all' : md5(serialize($filters));
        return "masters:service:{$serviceId}:filters:{$filterKey}";
    }
    
    /**
     * Ключ для списка мастеров по локации
     */
    public function getLocationMastersKey(string $location, array $filters = []): string
    {
        $filterKey = empty($filters) ? 'all' : md5(serialize($filters));
        return "masters:location:" . md5($location) . ":filters:{$filterKey}";
    }
    
    /**
     * Ключ для топ мастеров
     */
    public function getTopMastersKey(string $criteria = 'rating'): string
    {
        return "masters:top:{$criteria}";
    }
    
    /**
     * Ключ для расписания мастера
     */
    public function getScheduleKey(int $masterId, ?string $date = null): string
    {
        $date = $date ?? date('Y-m-d');
        return "master:{$masterId}:schedule:{$date}";
    }
    
    /**
     * Ключ для доступных слотов
     */
    public function getAvailableSlotsKey(int $masterId, string $date): string
    {
        return "master:{$masterId}:slots:{$date}";
    }
    
    /**
     * Теги для списков мастеров
     */
    public function getListTags(): array
    {
        return ['masters', 'masters_lists'];
    }
    
    /**
     * TTL для различных типов кеша мастеров
     */
    public function getTTLByType(string $type): int
    {
        $ttlMap = [
            'single_master' => 600,     // 10 минут
            'masters_list' => 300,      // 5 минут
            'top_masters' => 1800,      // 30 минут
            'schedule' => 180,          // 3 минуты
            'available_slots' => 60,    // 1 минута
            'search_results' => 240,    // 4 минуты
            'nearby_masters' => 900,    // 15 минут
        ];
        
        return $ttlMap[$type] ?? $this->defaultTTL;
    }
    
    /**
     * Инвалидация кеша при обновлении расписания
     */
    public function invalidateSchedule(int $masterId): bool
    {
        $tags = [
            "master:{$masterId}",
            'masters_schedules'
        ];
        
        app('cache')->tags($tags)->flush();
        
        return true;
    }
}