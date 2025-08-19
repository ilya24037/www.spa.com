<?php

namespace App\Domain\Master\DTOs;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Support\Collection;

/**
 * DTO для полного профиля мастера
 */
class MasterProfileDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $bio,
        public readonly ?int $experienceYears,
        public readonly float $rating,
        public readonly int $reviewsCount,
        public readonly int $viewsCount,
        public readonly int $priceFrom,
        public readonly int $priceTo,
        public readonly ?string $avatar,
        public readonly bool $isAvailableNow,
        public readonly bool $isFavorite,
        public readonly bool $isVerified,
        public readonly bool $isPremium,
        
        // Контакты
        public readonly ?string $phone,
        public readonly ?string $whatsapp,
        public readonly ?string $telegram,
        public readonly bool $showContacts,
        
        // Локация
        public readonly ?string $city,
        public readonly ?string $district,
        public readonly ?string $metroStation,
        public readonly bool $homeService,
        public readonly bool $salonService,
        public readonly ?string $salonAddress,
        
        // Физические параметры
        public readonly ?int $age,
        public readonly ?int $height,
        public readonly ?int $weight,
        public readonly ?string $breastSize,
        
        // Коллекции
        public readonly array $gallery,
        public readonly Collection $services,
        public readonly Collection $workZones,
        public readonly Collection $schedules,
        public readonly Collection $reviews,
        
        public readonly \DateTime $createdAt
    ) {}
    
    /**
     * Создание DTO из модели
     */
    public static function fromModel(MasterProfile $profile, ?int $userId = null): self
    {
        $galleryService = app(\App\Domain\Master\Services\MasterGalleryService::class);
        $masterService = app(\App\Domain\Master\Services\MasterService::class);
        
        $isFavorite = $userId 
            ? $masterService->isFavorite($profile->id, $userId)
            : false;
            
        $priceRange = self::calculatePriceRange($profile);
        
        return new self(
            id: $profile->id,
            name: $profile->display_name,
            slug: $profile->slug,
            bio: $profile->bio,
            experienceYears: $profile->experience_years,
            rating: (float)$profile->rating,
            reviewsCount: $profile->reviews_count,
            viewsCount: $profile->views_count,
            priceFrom: $priceRange['min'],
            priceTo: $priceRange['max'],
            avatar: $profile->avatar_url,
            isAvailableNow: $profile->isAvailableNow(),
            isFavorite: $isFavorite,
            isVerified: $profile->is_verified,
            isPremium: $profile->isPremium(),
            phone: $profile->show_contacts ? $profile->phone : null,
            whatsapp: $profile->whatsapp,
            telegram: $profile->telegram,
            showContacts: $profile->show_contacts,
            city: $profile->city,
            district: $profile->district,
            metroStation: $profile->metro_station,
            homeService: $profile->home_service,
            salonService: $profile->salon_service,
            salonAddress: $profile->salon_address,
            age: $profile->age,
            height: $profile->height,
            weight: $profile->weight,
            breastSize: $profile->breast_size,
            gallery: $galleryService->buildGallery($profile),
            services: self::buildServices($profile),
            workZones: self::buildWorkZones($profile),
            schedules: self::buildSchedules($profile),
            reviews: self::buildReviews($profile),
            createdAt: $profile->created_at
        );
    }
    
    /**
     * Преобразование в массив для Inertia
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'experience_years' => $this->experienceYears,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'views_count' => $this->viewsCount,
            'price_from' => $this->priceFrom,
            'price_to' => $this->priceTo,
            'avatar' => $this->avatar,
            'is_available_now' => $this->isAvailableNow,
            'is_favorite' => $this->isFavorite,
            'is_verified' => $this->isVerified,
            'is_premium' => $this->isPremium,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'telegram' => $this->telegram,
            'show_contacts' => $this->showContacts,
            'city' => $this->city,
            'district' => $this->district,
            'metro_station' => $this->metroStation,
            'home_service' => $this->homeService,
            'salon_service' => $this->salonService,
            'salon_address' => $this->salonAddress,
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            'breast_size' => $this->breastSize,
            'gallery' => $this->gallery,
            'services' => $this->services->toArray(),
            'work_zones' => $this->workZones->toArray(),
            'schedules' => $this->schedules->toArray(),
            'reviews' => $this->reviews->toArray(),
            'created_at' => $this->createdAt
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
     * Построение списка услуг
     */
    private static function buildServices(MasterProfile $profile): Collection
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return collect([]);
        }
        
        return $profile->services->map(fn($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'category' => $service->category->name ?? 'Массаж',
            'price' => $service->price,
            'duration' => $service->duration,
            'description' => $service->description
        ]);
    }
    
    /**
     * Построение рабочих зон
     */
    private static function buildWorkZones(MasterProfile $profile): Collection
    {
        if (!$profile->workZones || $profile->workZones->isEmpty()) {
            return collect([]);
        }
        
        return $profile->workZones->map(fn($zone) => [
            'id' => $zone->id,
            'district' => $zone->district,
            'city' => $zone->city ?? $profile->city,
            'is_active' => $zone->is_active ?? true
        ]);
    }
    
    /**
     * Построение расписания
     */
    private static function buildSchedules(MasterProfile $profile): Collection
    {
        if (!$profile->schedules || $profile->schedules->isEmpty()) {
            return collect([]);
        }
        
        return $profile->schedules->map(fn($schedule) => [
            'id' => $schedule->id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'is_working_day' => $schedule->is_working_day ?? true
        ]);
    }
    
    /**
     * Построение отзывов
     */
    private static function buildReviews(MasterProfile $profile, int $limit = 5): Collection
    {
        if (!$profile->reviews || $profile->reviews->isEmpty()) {
            return collect([]);
        }
        
        return $profile->reviews->take($limit)->map(fn($review) => [
            'id' => $review->id,
            'rating' => $review->rating_overall ?? $review->rating,
            'comment' => $review->comment,
            'client_name' => $review->user->name ?? 'Анонимный клиент',
            'created_at' => $review->created_at
        ]);
    }
}