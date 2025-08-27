<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\AdHomePageDTO;
use Illuminate\Support\Collection;

/**
 * Сервис трансформации объявлений для различных представлений
 */
class AdTransformService
{
    private AdGeoService $geoService;
    private AdPricingService $pricingService;
    
    public function __construct(
        AdGeoService $geoService,
        AdPricingService $pricingService
    ) {
        $this->geoService = $geoService;
        $this->pricingService = $pricingService;
    }
    
    /**
     * Трансформация объявлений для главной страницы
     * 
     * @param Collection $ads
     * @return Collection
     */
    public function transformForHomePage(Collection $ads): Collection
    {
        // Загружаем связи с профилем мастера
        $ads->load('user.masterProfile');
        
        return $ads->map(function ($ad) {
            return $this->transformSingleAd($ad);
        });
    }
    
    /**
     * Трансформация одного объявления
     * 
     * @param Ad $ad
     * @return AdHomePageDTO
     */
    private function transformSingleAd(Ad $ad): AdHomePageDTO
    {
        $coordinates = $this->geoService->extractCoordinates($ad->geo);
        $pricing = $this->pricingService->extractPricing($ad->prices, $ad->price);
        $photos = $this->extractPhotos($ad->photos);
        $services = $this->extractServices($ad->services);
        
        // Извлекаем рейтинг и отзывы если есть связь с user
        $rating = $this->calculateRating($ad);
        $reviewsCount = $this->getReviewsCount($ad);
        
        // Определяем статусы
        $isVerified = $this->checkVerificationStatus($ad);
        $isPremium = $this->checkPremiumStatus($ad);
        
        // Получаем данные о профиле мастера
        $masterProfile = $ad->user?->masterProfile;
        $masterSlug = $masterProfile?->slug ?: null;
        
        // Используем slug из самого объявления, если он есть
        // Иначе генерируем из title
        $adSlug = $ad->slug ?: \Illuminate\Support\Str::slug($ad->title ?: 'ad');
        
        return new AdHomePageDTO([
            'id' => $ad->id,
            'name' => $ad->title ?? $this->getUserName($ad),
            'photo' => $photos['main'],
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'price_from' => $pricing['min'],
            'services' => $services,
            'district' => $coordinates['district'] ?? $this->extractDistrictFromGeo($ad->geo),
            'metro' => $this->extractMetro($ad),
            'address' => $ad->address,
            'lat' => $coordinates['lat'],
            'lng' => $coordinates['lng'],
            'geo' => $coordinates,
            'experience_years' => $this->extractExperience($ad),
            'is_verified' => $isVerified,
            'is_premium' => $isPremium,
            // Добавляем данные о профиле мастера
            'master_profile_id' => $masterProfile?->id,
            'master_slug' => $masterSlug,
            'has_master_profile' => $masterProfile !== null,
            'slug' => $adSlug, // Используем slug объявления
        ]);
    }
    
    /**
     * Рассчитать рейтинг объявления
     * 
     * @param Ad $ad
     * @return float
     */
    private function calculateRating(Ad $ad): float
    {
        // Если есть связь с reviews через user
        if ($ad->user && $ad->user->receivedReviews) {
            $avgRating = $ad->user->receivedReviews()->avg('rating');
            return $avgRating ? round($avgRating, 1) : 4.5;
        }
        
        // Дефолтный рейтинг для новых объявлений
        return 4.5;
    }
    
    /**
     * Получить количество отзывов
     * 
     * @param Ad $ad
     * @return int
     */
    private function getReviewsCount(Ad $ad): int
    {
        if ($ad->user && $ad->user->receivedReviews) {
            return $ad->user->receivedReviews()->count();
        }
        
        return 0;
    }
    
    /**
     * Проверить статус верификации
     * 
     * @param Ad $ad
     * @return bool
     */
    private function checkVerificationStatus(Ad $ad): bool
    {
        // Проверяем верификацию пользователя
        if ($ad->user && $ad->user->is_verified) {
            return true;
        }
        
        // Проверяем статус модерации объявления
        return $ad->status === 'active' && $ad->moderated_at !== null;
    }
    
    /**
     * Проверить премиум статус
     * 
     * @param Ad $ad
     * @return bool
     */
    private function checkPremiumStatus(Ad $ad): bool
    {
        // Проверяем подписку пользователя
        if ($ad->user && $ad->user->subscription) {
            return $ad->user->subscription->plan === 'premium' && 
                   $ad->user->subscription->expires_at > now();
        }
        
        return false;
    }
    
    /**
     * Извлечь имя пользователя
     * 
     * @param Ad $ad
     * @return string
     */
    private function getUserName(Ad $ad): string
    {
        if ($ad->user) {
            return $ad->user->name ?? 'Мастер';
        }
        
        return 'Мастер';
    }
    
    /**
     * Извлечь информацию о метро
     * 
     * @param Ad $ad
     * @return string|null
     */
    private function extractMetro(Ad $ad): ?string
    {
        // Пробуем извлечь из geo данных
        $geo = is_string($ad->geo) ? json_decode($ad->geo, true) : $ad->geo;
        
        if (is_array($geo) && isset($geo['metro'])) {
            return $geo['metro'];
        }
        
        // Пробуем извлечь из адреса
        if ($ad->address && preg_match('/метро\s+([^,]+)/i', $ad->address, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Извлечь опыт работы
     * 
     * @param Ad $ad
     * @return int
     */
    private function extractExperience(Ad $ad): int
    {
        // Извлекаем из поля experience если есть
        if (isset($ad->experience) && is_numeric($ad->experience)) {
            return (int)$ad->experience;
        }
        
        // Рассчитываем на основе даты создания профиля
        if ($ad->user && $ad->user->created_at) {
            $years = $ad->user->created_at->diffInYears(now());
            return max(1, $years); // Минимум 1 год
        }
        
        return 1;
    }
    
    /**
     * Извлечение фотографий из JSON
     * 
     * @param mixed $photos
     * @return array
     */
    private function extractPhotos($photos): array
    {
        if (!$photos) {
            return ['main' => '/images/no-photo.svg'];
        }
        
        $photosData = is_string($photos) ? json_decode($photos, true) : $photos;
        
        if (!is_array($photosData) || empty($photosData)) {
            return ['main' => '/images/no-photo.svg'];
        }
        
        $firstPhoto = $photosData[0];
        
        // Поддержка разных форматов хранения фото
        if (is_array($firstPhoto)) {
            $main = $firstPhoto['preview'] ?? 
                    $firstPhoto['url'] ?? 
                    $firstPhoto['src'] ?? 
                    '/images/no-photo.svg';
        } elseif (is_string($firstPhoto)) {
            $main = $firstPhoto;
        } else {
            $main = '/images/no-photo.svg';
        }
        
        return ['main' => $main];
    }
    
    /**
     * Извлечение услуг из JSON
     * 
     * @param mixed $services
     * @return array
     */
    private function extractServices($services): array
    {
        if (!$services) {
            return ['Классический массаж'];
        }
        
        $servicesData = is_string($services) ? json_decode($services, true) : $services;
        
        if (!is_array($servicesData)) {
            return ['Классический массаж'];
        }
        
        // Берем первые 3 услуги
        $serviceNames = [];
        
        // Если это простой массив ключей
        if (array_keys($servicesData) !== range(0, count($servicesData) - 1)) {
            $serviceNames = array_slice(array_keys($servicesData), 0, 3);
        }
        // Если это массив объектов с полем name
        elseif (isset($servicesData[0]['name'])) {
            foreach (array_slice($servicesData, 0, 3) as $service) {
                if (isset($service['name'])) {
                    $serviceNames[] = $service['name'];
                }
            }
        }
        // Если это вложенная структура с services
        elseif (isset($servicesData['services'])) {
            return $this->extractServices($servicesData['services']);
        }
        
        return !empty($serviceNames) ? $serviceNames : ['Классический массаж'];
    }
    
    /**
     * Извлечение района из geo данных
     * 
     * @param mixed $geo
     * @return string
     */
    private function extractDistrictFromGeo($geo): string
    {
        if (!$geo) {
            return 'Центральный район';
        }
        
        $geoData = is_string($geo) ? json_decode($geo, true) : $geo;
        
        if (is_array($geoData) && isset($geoData['district'])) {
            return $geoData['district'];
        }
        
        return 'Центральный район';
    }
    
    /**
     * Трансформация для карты
     * 
     * @param Collection $ads
     * @return Collection
     */
    public function transformForMap(Collection $ads): Collection
    {
        return $ads->filter(function ($ad) {
            // Фильтруем только объявления с валидными координатами
            return $this->geoService->hasValidCoordinates($ad->geo);
        })->map(function ($ad) {
            $dto = $this->transformSingleAd($ad);
            
            // Возвращаем только необходимые для карты поля
            return [
                'id' => $dto->id,
                'name' => $dto->name,
                'lat' => $dto->lat,
                'lng' => $dto->lng,
                'price_from' => $dto->price_from,
                'address' => $dto->address,
                'photo' => $dto->photo,
                'rating' => $dto->rating,
                'services' => array_slice($dto->services, 0, 2)
            ];
        });
    }
}