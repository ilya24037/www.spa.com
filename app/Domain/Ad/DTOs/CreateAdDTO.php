<?php

namespace App\Domain\Ad\DTOs;

use App\Domain\Ad\DTOs\Data\AdContentData;
use App\Domain\Ad\DTOs\Data\AdLocationData;
use App\Domain\Ad\DTOs\Data\AdPricingData;

class CreateAdDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $category,
        public readonly string $specialty,
        public readonly array $clients,
        public readonly string $description,
        public readonly AdPricingData $pricing,
        public readonly AdLocationData $location,
        public readonly ?AdContentData $content = null,
        public readonly array $services = [],
        public readonly array $media = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        // Преобразуем плоскую структуру данных из запроса в структурированные DTO
        $pricingData = [
            'price' => $data['price'] ?? 0,
            'discount_price' => $data['discount'] ?? null,
            'currency' => 'RUB', // По умолчанию
            'price_type' => 'FIXED', // По умолчанию
            'min_order_amount' => $data['min_duration'] ?? null,
            'additional_prices' => [
                'per_hour' => $data['price_per_hour'] ?? null,
                'outcall' => $data['outcall_price'] ?? null,
                'express' => $data['express_price'] ?? null,
                'two_hours' => $data['price_two_hours'] ?? null,
                'night' => $data['price_night'] ?? null,
            ],
            'negotiable' => false // По умолчанию
        ];
        
        $locationData = [
            'city' => $data['city'] ?? 'Москва', // Нужно будет получать из запроса
            'district' => $data['district'] ?? null,
            'metro_station' => $data['metro_station'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'home_service' => in_array('home', $data['service_location'] ?? []),
            'salon_service' => in_array('salon', $data['service_location'] ?? []),
        ];
        
        return new self(
            userId: $data['user_id'] ?? auth()->id(),
            title: $data['title'],
            category: $data['category'],
            specialty: $data['specialty'],
            clients: $data['clients'] ?? [],
            description: $data['description'],
            pricing: AdPricingData::fromArray($pricingData),
            location: AdLocationData::fromArray($locationData),
            content: null, // Контент хранится в основной таблице
            services: $data['services'] ?? [],
            media: [
                'photos' => $data['photos'] ?? [],
                'video' => $data['video'] ?? null
            ],
        );
    }

    public function toArray(): array
    {
        // Преобразуем структурированные DTO обратно в плоскую структуру для AdService
        $pricingArray = $this->pricing->toArray();
        $locationArray = $this->location->toArray();
        
        $data = [
            'user_id' => $this->userId,
            'title' => $this->title,
            'category' => $this->category,
            'specialty' => $this->specialty,
            'clients' => $this->clients,
            'description' => $this->description,
            'services' => $this->services,
            
            // Поля цены из pricing DTO
            'price' => $pricingArray['price'] ?? 0,
            'discount' => $pricingArray['discount_price'] ?? null,
            'min_duration' => $pricingArray['min_order_amount'] ?? null,
            
            // Дополнительные цены
            'price_per_hour' => $pricingArray['additional_prices']['per_hour'] ?? null,
            'outcall_price' => $pricingArray['additional_prices']['outcall'] ?? null,
            'express_price' => $pricingArray['additional_prices']['express'] ?? null,
            'price_two_hours' => $pricingArray['additional_prices']['two_hours'] ?? null,
            'price_night' => $pricingArray['additional_prices']['night'] ?? null,
            
            // Поля локации из location DTO
            'address' => $locationArray['address'] ?? null,
            'service_location' => array_filter([
                $locationArray['home_service'] ? 'home' : null,
                $locationArray['salon_service'] ? 'salon' : null,
            ]),
            
            // Медиа
            'photos' => $this->media['photos'] ?? [],
            'video' => $this->media['video'] ?? null,
        ];
        
        // Удаляем null значения
        return array_filter($data, fn($value) => $value !== null);
    }
}