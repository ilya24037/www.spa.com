<?php

namespace App\Domain\Master\DTOs;

use App\Domain\Ad\Models\Ad;

/**
 * DTO для API представления мастера
 */
class MasterApiDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $photo,
        public readonly string $address,
        public readonly float $rating,
        public readonly int $reviewsCount,
        public readonly int $priceFrom,
        public readonly string $priceUnit,
        public readonly int $daysAgo,
        public readonly array $services,
        public readonly ?string $district,
        public readonly ?string $city,
        public readonly bool $isOnline,
        public readonly bool $isPremium,
        public readonly bool $isVerified,
        public readonly array $coordinates
    ) {}
    
    /**
     * Создание DTO из объявления
     */
    public static function fromAd(Ad $ad): self
    {
        $geoService = app(\App\Domain\Ad\Services\AdGeoService::class);
        $pricingService = app(\App\Domain\Ad\Services\AdPricingService::class);
        
        $coordinates = $geoService->extractCoordinates($ad->geo);
        $pricing = $pricingService->extractPricing($ad->prices, $ad->price);
        
        return new self(
            id: $ad->id,
            name: self::extractUserName($ad),
            photo: self::extractPhoto($ad->photos),
            address: $ad->address,
            rating: self::generateRating(),
            reviewsCount: self::generateReviewsCount(),
            priceFrom: $pricing['min'],
            priceUnit: $pricing['unit'],
            daysAgo: self::calculateDaysAgo($ad->created_at),
            services: self::extractServices($ad->services),
            district: $coordinates['district'],
            city: self::extractCity($ad->address),
            isOnline: true,
            isPremium: false,
            isVerified: true,
            coordinates: [
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng']
            ]
        );
    }
    
    /**
     * Преобразование в массив для API
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'address' => $this->address,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'price_from' => $this->priceFrom,
            'price_unit' => $this->priceUnit,
            'days_ago' => $this->daysAgo,
            'services' => $this->services,
            'district' => $this->district,
            'city' => $this->city,
            'is_online' => $this->isOnline,
            'is_premium' => $this->isPremium,
            'is_verified' => $this->isVerified,
            'coordinates' => $this->coordinates
        ];
    }
    
    /**
     * Проверка валидности координат
     */
    public function hasValidCoordinates(): bool
    {
        return !empty($this->coordinates['lat']) && !empty($this->coordinates['lng']);
    }
    
    /**
     * Извлечение имени пользователя
     */
    private static function extractUserName(Ad $ad): string
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
     */
    private static function extractPhoto($photos): ?string
    {
        if (!$photos) {
            return null;
        }
        
        $photosData = is_string($photos) ? json_decode($photos, true) : $photos;
        
        if (!is_array($photosData) || empty($photosData)) {
            return null;
        }
        
        $firstPhoto = $photosData[0];
        
        if (is_array($firstPhoto)) {
            return $firstPhoto['url'] ?? $firstPhoto['src'] ?? $firstPhoto['path'] ?? null;
        }
        
        return is_string($firstPhoto) ? $firstPhoto : null;
    }
    
    /**
     * Расчет количества дней с момента публикации
     */
    private static function calculateDaysAgo($createdAt): int
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
     */
    private static function extractServices($services): array
    {
        if (!$services) {
            return [];
        }
        
        $servicesData = is_string($services) ? json_decode($services, true) : $services;
        
        if (!is_array($servicesData)) {
            return [];
        }
        
        $result = [];
        
        foreach ($servicesData as $key => $value) {
            if (is_array($value)) {
                if (isset($value['services']) && is_array($value['services'])) {
                    foreach ($value['services'] as $service) {
                        if (is_array($service) && isset($service['name'])) {
                            $result[] = ['name' => $service['name']];
                        }
                    }
                } elseif (isset($value['name'])) {
                    $result[] = ['name' => $value['name']];
                }
            } elseif (is_string($key) && $value === true) {
                $result[] = ['name' => self::humanizeServiceKey($key)];
            }
        }
        
        return $result;
    }
    
    /**
     * Извлечение города из адреса
     */
    private static function extractCity(?string $address): ?string
    {
        if (!$address) {
            return null;
        }
        
        $parts = explode(',', $address);
        return trim($parts[0] ?? '');
    }
    
    /**
     * Генерация временного рейтинга
     */
    private static function generateRating(): float
    {
        return 4.5 + (rand(0, 50) / 100);
    }
    
    /**
     * Генерация временного количества отзывов
     */
    private static function generateReviewsCount(): int
    {
        return rand(5, 150);
    }
    
    /**
     * Преобразование ключа услуги в читаемый вид
     */
    private static function humanizeServiceKey(string $key): string
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
}