<?php

namespace App\Domain\Master\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdGeoService;
use App\Domain\Ad\Services\AdPricingService;
use App\Domain\Master\DTOs\MasterApiDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для API методов работы с мастерами
 * Обрабатывает данные объявлений для API endpoints
 */
class MasterApiService
{
    public function __construct(
        private AdGeoService $geoService,
        private AdPricingService $pricingService
    ) {}
    
    /**
     * Получение отфильтрованных объявлений
     * 
     * @param array $filters
     * @return Collection
     */
    public function getFilteredAds(array $filters): Collection
    {
        $query = Ad::query()
            ->where('status', 'active')
            ->whereNotNull('geo')
            ->where('geo', '!=', '[]')
            ->where('geo', '!=', '{}');
        
        // Фильтрация по городу
        if (!empty($filters['city'])) {
            $query->where('address', 'LIKE', '%' . $filters['city'] . '%');
        }
        
        // Поиск по заголовку
        if (!empty($filters['search'])) {
            $query->where('title', 'LIKE', '%' . $filters['search'] . '%');
        }
        
        // Фильтр по категории
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        // Фильтр по диапазону цен
        if (!empty($filters['price_min'])) {
            $query->whereRaw('CAST(JSON_EXTRACT(prices, "$[0]") AS UNSIGNED) >= ?', [$filters['price_min']]);
        }
        
        if (!empty($filters['price_max'])) {
            $query->whereRaw('CAST(JSON_EXTRACT(prices, "$[0]") AS UNSIGNED) <= ?', [$filters['price_max']]);
        }
        
        // Сортировка
        $this->applySorting($query, $filters['sort'] ?? 'relevance');
        
        return $query->with('user')->get();
    }
    
    /**
     * Трансформация объявлений для API
     * 
     * @param Collection $ads
     * @return Collection
     */
    public function transformForApi(Collection $ads): Collection
    {
        return $ads->map(function ($ad) {
            try {
                $dto = MasterApiDTO::fromAd($ad);
                
                // Пропускаем объявления без валидных координат
                if (!$dto->hasValidCoordinates()) {
                    return null;
                }
                
                return $dto->toArray();
            } catch (\Exception $e) {
                Log::warning("Ошибка обработки объявления {$ad->id}: " . $e->getMessage());
                return null;
            }
        })->filter()->values();
    }
    
    /**
     * Применение сортировки к запросу
     * 
     * @param mixed $query
     * @param string $sort
     * @return void
     */
    private function applySorting($query, string $sort): void
    {
        switch ($sort) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'price_asc':
            case 'price':
                $query->orderByRaw('CAST(JSON_EXTRACT(prices, "$[0]") AS UNSIGNED) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('CAST(JSON_EXTRACT(prices, "$[0]") AS UNSIGNED) DESC');
                break;
            case 'rating':
                // Временно сортируем по created_at пока нет рейтингов
                $query->orderByDesc('created_at');
                break;
            default: // relevance
                $query->orderByDesc('created_at');
        }
    }
    
    /**
     * Извлечение имени пользователя
     * 
     * @param Ad $ad
     * @return string
     */
    private function extractUserName(Ad $ad): string
    {
        if ($ad->title) {
            return $ad->title;
        }
        
        if ($ad->user) {
            return $ad->user->name ?: $ad->user->email;
        }
        
        return 'Массажист';
    }
    
    /**
     * Извлечение первого фото
     * 
     * @param mixed $photos
     * @return string|null
     */
    private function extractPhoto($photos): ?string
    {
        if (!$photos) {
            return null;
        }
        
        $photosData = is_string($photos) ? json_decode($photos, true) : $photos;
        
        if (!is_array($photosData) || empty($photosData)) {
            return null;
        }
        
        // Поддержка разных форматов
        $firstPhoto = $photosData[0];
        
        if (is_array($firstPhoto)) {
            return $firstPhoto['url'] ?? $firstPhoto['src'] ?? $firstPhoto['path'] ?? null;
        }
        
        return is_string($firstPhoto) ? $firstPhoto : null;
    }
    
    /**
     * Расчет количества дней с момента публикации
     * 
     * @param mixed $createdAt
     * @return int
     */
    private function calculateDaysAgo($createdAt): int
    {
        if (!$createdAt) {
            return 0;
        }
        
        if (is_string($createdAt)) {
            $createdAt = new \DateTime($createdAt);
        }
        
        return (int)floor($createdAt->diff(now())->days);
    }
    
    /**
     * Извлечение услуг из объявления
     * 
     * @param mixed $services
     * @return array
     */
    private function extractServices($services): array
    {
        if (!$services) {
            return [];
        }
        
        $servicesData = is_string($services) ? json_decode($services, true) : $services;
        
        if (!is_array($servicesData)) {
            return [];
        }
        
        $result = [];
        
        // Обработка разных форматов услуг
        foreach ($servicesData as $key => $value) {
            if (is_array($value)) {
                // Формат с вложенными услугами
                if (isset($value['services']) && is_array($value['services'])) {
                    foreach ($value['services'] as $service) {
                        if (is_array($service) && isset($service['name'])) {
                            $result[] = ['name' => $service['name']];
                        }
                    }
                }
                // Формат с прямым списком
                elseif (isset($value['name'])) {
                    $result[] = ['name' => $value['name']];
                }
            }
            // Простой формат ключ-значение
            elseif (is_string($key) && $value === true) {
                $result[] = ['name' => $this->humanizeServiceKey($key)];
            }
        }
        
        return $result;
    }
    
    /**
     * Извлечение города из адреса
     * 
     * @param string|null $address
     * @return string|null
     */
    private function extractCity(?string $address): ?string
    {
        if (!$address) {
            return null;
        }
        
        // Простое извлечение города (можно улучшить регулярками)
        $parts = explode(',', $address);
        return trim($parts[0] ?? '');
    }
    
    /**
     * Генерация временного рейтинга
     * 
     * @return float
     */
    private function generateRating(): float
    {
        return 4.5 + (rand(0, 50) / 100);
    }
    
    /**
     * Генерация временного количества отзывов
     * 
     * @return int
     */
    private function generateReviewsCount(): int
    {
        return rand(5, 150);
    }
    
    /**
     * Преобразование ключа услуги в читаемый вид
     * 
     * @param string $key
     * @return string
     */
    private function humanizeServiceKey(string $key): string
    {
        $replacements = [
            'classic_massage' => 'Классический массаж',
            'relax_massage' => 'Релакс массаж',
            'thai_massage' => 'Тайский массаж',
            'sport_massage' => 'Спортивный массаж',
            'anti_cellulite' => 'Антицеллюлитный массаж',
            'lymphatic' => 'Лимфодренажный массаж'
        ];
        
        return $replacements[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
    
    /**
     * Получение статистики по объявлениям
     * 
     * @param array $filters
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $ads = $this->getFilteredAds($filters);
        
        return [
            'total' => $ads->count(),
            'with_coordinates' => $ads->filter(function ($ad) {
                $coords = $this->geoService->extractCoordinates($ad->geo);
                return $coords['lat'] && $coords['lng'];
            })->count(),
            'avg_price' => $ads->avg(function ($ad) {
                $pricing = $this->pricingService->extractPricing($ad->prices, $ad->price);
                return $pricing['min'];
            }),
            'cities' => $ads->pluck('address')->map(function ($address) {
                return $this->extractCity($address);
            })->filter()->unique()->count()
        ];
    }
}