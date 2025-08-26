<?php

namespace App\Domain\Master\DTOs;

use App\Domain\Master\Models\MasterProfile;

/**
 * DTO для элемента списка мастеров
 */
class MasterListItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $avatar,
        public readonly float $rating,
        public readonly int $reviewsCount,
        public readonly int $priceFrom,
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly bool $isAvailableNow,
        public readonly bool $isVerified,
        public readonly bool $isPremium,
        public readonly array $mainServices
    ) {}
    
    /**
     * Создание DTO из модели
     */
    public static function fromModel(MasterProfile $profile): self
    {
        $priceRange = self::calculatePriceRange($profile);
        
        return new self(
            id: $profile->id,
            name: $profile->display_name,
            slug: $profile->slug,
            avatar: $profile->avatar_url,
            rating: (float)$profile->rating,
            reviewsCount: $profile->reviews_count,
            priceFrom: $priceRange['min'],
            city: $profile->city,
            district: $profile->district,
            isAvailableNow: $profile->isAvailableNow(),
            isVerified: $profile->is_verified,
            isPremium: $profile->isPremium(),
            mainServices: self::getMainServices($profile, 3)
        );
    }
    
    /**
     * Преобразование в массив
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'avatar' => $this->avatar,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'price_from' => $this->priceFrom,
            'city' => $this->city,
            'district' => $this->district,
            'is_available_now' => $this->isAvailableNow,
            'is_verified' => $this->isVerified,
            'is_premium' => $this->isPremium,
            'main_services' => $this->mainServices
        ];
    }
    
    /**
     * Расчет диапазона цен
     */
    private static function calculatePriceRange(MasterProfile $profile): array
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }
        
        return [
            'min' => $profile->services->min('price') ?? 0,
            'max' => $profile->services->max('price') ?? 0
        ];
    }
    
    /**
     * Получение основных услуг
     */
    private static function getMainServices(MasterProfile $profile, int $limit = 3): array
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return ['Классический массаж'];
        }
        
        return $profile->services
            ->take($limit)
            ->pluck('name')
            ->toArray();
    }
}