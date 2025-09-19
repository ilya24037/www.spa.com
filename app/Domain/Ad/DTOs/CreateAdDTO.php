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
        public readonly string $status = 'draft',
        public readonly bool $isPublished = false,
        // Добавляем все недостающие поля
        public readonly ?int $age = null,
        public readonly ?int $height = null,
        public readonly ?int $weight = null,
        public readonly ?string $breast_size = null,
        public readonly ?string $hair_color = null,
        public readonly ?string $eye_color = null,
        public readonly ?string $nationality = null,
        public readonly ?string $phone = null,
        public readonly ?string $contact_method = null,
        public readonly ?string $whatsapp = null,
        public readonly ?string $telegram = null,
        public readonly ?array $prices = null,
        public readonly ?array $geo = null,
        public readonly array $service_provider = [],
        public readonly ?string $work_format = null,
        public readonly ?string $experience = null,
        public readonly array $features = [],
        public readonly ?string $additional_features = null,
        public readonly ?string $services_additional_info = null,
        public readonly ?array $schedule = null,
        public readonly ?string $schedule_notes = null,
        public readonly ?string $price_unit = null,
        public readonly ?bool $is_starting_price = null,
        public readonly ?string $new_client_discount = null,
        public readonly ?string $gift = null,
        public readonly ?string $travel_area = null,
        public readonly array $custom_travel_areas = [],
        public readonly ?string $travel_radius = null,
        public readonly ?float $travel_price = null,
        public readonly ?string $travel_price_type = null,
        public readonly array $service_location = [],
        public readonly ?array $videos = null,
        public readonly ?array $media_settings = null,
        public readonly ?array $photos = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Упрощенный метод для создания DTO из массива (для черновиков)
        $pricingData = [
            'price' => $data['price'] ?? 0,
            'discount_price' => $data['discount'] ?? null,
            'currency' => 'RUB',
            'price_type' => 'FIXED',
            'min_order_amount' => null,
            'additional_prices' => [],
            'negotiable' => false
        ];
        
        $locationData = [
            'city' => $data['city'] ?? $data['geo']['city'] ?? 'Москва',
            'district' => $data['district'] ?? null,
            'metro_station' => null,
            'address' => $data['address'] ?? null,
            'latitude' => null,
            'longitude' => null,
            'home_service' => false,
            'salon_service' => false,
        ];
        
        return new self(
            userId: $data['user_id'] ?? auth()->id(),
            title: $data['title'] ?? '',
            category: $data['category'] ?? 'erotic',
            specialty: $data['specialty'] ?? '',
            clients: $data['clients'] ?? [],
            description: $data['description'] ?? '',
            pricing: AdPricingData::fromArray($pricingData),
            location: AdLocationData::fromArray($locationData),
            content: null,
            services: $data['services'] ?? [],
            media: [
                'photos' => $data['photos'] ?? [],
                'video' => $data['video'] ?? null
            ],
            status: $data['status'] ?? 'draft',
            isPublished: $data['is_published'] ?? false,
            // Добавляем все недостающие поля
            age: $data['age'] ?? null,
            height: $data['height'] ?? null,
            weight: $data['weight'] ?? null,
            breast_size: $data['breast_size'] ?? null,
            hair_color: $data['hair_color'] ?? null,
            eye_color: $data['eye_color'] ?? null,
            nationality: $data['nationality'] ?? null,
            phone: $data['phone'] ?? null,
            contact_method: $data['contact_method'] ?? null,
            whatsapp: $data['whatsapp'] ?? null,
            telegram: $data['telegram'] ?? null,
            prices: $data['prices'] ?? null,
            geo: [
                'city' => $data['geo']['city'] ?? $data['geo.city'] ?? null,
                'address' => $data['geo']['address'] ?? $data['geo.address'] ?? null,
                'coordinates' => $data['geo']['coordinates'] ?? $data['geo.coordinates'] ?? null,
                'zones' => $data['geo']['zones'] ?? $data['geo.zones'] ?? null,
                'metro_stations' => $data['geo']['metro_stations'] ?? $data['geo.metro_stations'] ?? null,
            ],
            service_provider: $data['service_provider'] ?? [],
            work_format: $data['work_format'] ?? null,
            experience: $data['experience'] ?? null,
            features: $data['features'] ?? [],
            additional_features: $data['additional_features'] ?? null,
            services_additional_info: $data['services_additional_info'] ?? null,
            schedule: $data['schedule'] ?? null,
            schedule_notes: $data['schedule_notes'] ?? null,
            price_unit: $data['price_unit'] ?? null,
            is_starting_price: $data['is_starting_price'] ?? null,
            new_client_discount: $data['new_client_discount'] ?? null,
            gift: $data['gift'] ?? null,
            travel_area: $data['travel_area'] ?? null,
            custom_travel_areas: $data['custom_travel_areas'] ?? [],
            travel_radius: $data['travel_radius'] ?? null,
            travel_price: $data['travel_price'] ?? null,
            travel_price_type: $data['travel_price_type'] ?? null,
            service_location: $data['service_location'] ?? [],
            videos: $data['videos'] ?? null,
            media_settings: $data['media_settings'] ?? null,
            photos: $data['photos'] ?? null,
        );
    }
    
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
            status: $data['status'] ?? 'draft',
            isPublished: $data['is_published'] ?? false,
            // Добавляем все недостающие поля
            age: $data['age'] ?? null,
            height: $data['height'] ?? null,
            weight: $data['weight'] ?? null,
            breast_size: $data['breast_size'] ?? null,
            hair_color: $data['hair_color'] ?? null,
            eye_color: $data['eye_color'] ?? null,
            nationality: $data['nationality'] ?? null,
            phone: $data['phone'] ?? null,
            contact_method: $data['contact_method'] ?? null,
            whatsapp: $data['whatsapp'] ?? null,
            telegram: $data['telegram'] ?? null,
            prices: $data['prices'] ?? null,
            geo: [
                'city' => $data['geo']['city'] ?? $data['geo.city'] ?? null,
                'address' => $data['geo']['address'] ?? $data['geo.address'] ?? null,
                'coordinates' => $data['geo']['coordinates'] ?? $data['geo.coordinates'] ?? null,
                'zones' => $data['geo']['zones'] ?? $data['geo.zones'] ?? null,
                'metro_stations' => $data['geo']['metro_stations'] ?? $data['geo.metro_stations'] ?? null,
            ],
            service_provider: $data['service_provider'] ?? [],
            work_format: $data['work_format'] ?? null,
            experience: $data['experience'] ?? null,
            features: $data['features'] ?? [],
            additional_features: $data['additional_features'] ?? null,
            services_additional_info: $data['services_additional_info'] ?? null,
            schedule: $data['schedule'] ?? null,
            schedule_notes: $data['schedule_notes'] ?? null,
            price_unit: $data['price_unit'] ?? null,
            is_starting_price: $data['is_starting_price'] ?? null,
            new_client_discount: $data['new_client_discount'] ?? null,
            gift: $data['gift'] ?? null,
            travel_area: $data['travel_area'] ?? null,
            custom_travel_areas: $data['custom_travel_areas'] ?? [],
            travel_radius: $data['travel_radius'] ?? null,
            travel_price: $data['travel_price'] ?? null,
            travel_price_type: $data['travel_price_type'] ?? null,
            service_location: $data['service_location'] ?? [],
            videos: $data['videos'] ?? null,
            media_settings: $data['media_settings'] ?? null,
            photos: $data['photos'] ?? null,
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
            
            // Статус и публикация
            'status' => $this->status,
            'is_published' => $this->isPublished,
            
            // Добавляем все недостающие поля
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            'breast_size' => $this->breast_size,
            'hair_color' => $this->hair_color,
            'eye_color' => $this->eye_color,
            'nationality' => $this->nationality,
            'phone' => $this->phone,
            'contact_method' => $this->contact_method,
            'whatsapp' => $this->whatsapp,
            'telegram' => $this->telegram,
            'prices' => $this->prices,
            'geo' => $this->geo,
            'service_provider' => $this->service_provider,
            'work_format' => $this->work_format,
            'experience' => $this->experience,
            'features' => $this->features,
            'additional_features' => $this->additional_features,
            'services_additional_info' => $this->services_additional_info,
            'schedule' => $this->schedule,
            'schedule_notes' => $this->schedule_notes,
            'price_unit' => $this->price_unit,
            'is_starting_price' => $this->is_starting_price,
            'new_client_discount' => $this->new_client_discount,
            'gift' => $this->gift,
            'travel_area' => $this->travel_area,
            'custom_travel_areas' => $this->custom_travel_areas,
            'travel_radius' => $this->travel_radius,
            'travel_price' => $this->travel_price,
            'travel_price_type' => $this->travel_price_type,
            'videos' => $this->videos,
            'media_settings' => $this->media_settings,
            'photos' => $this->photos,
        ];
        
        // Удаляем null значения
        return array_filter($data, fn($value) => $value !== null);
    }
}