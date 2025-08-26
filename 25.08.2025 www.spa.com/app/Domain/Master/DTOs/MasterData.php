<?php

namespace App\Domain\Master\DTOs;

use App\Enums\MasterStatus;
use App\Enums\MasterLevel;

class MasterData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $displayName,
        public readonly string $slug,
        public readonly MasterStatus $status,
        public readonly MasterLevel $level,
        public readonly string $city,
        public readonly ?string $district,
        public readonly ?string $metroStation,
        public readonly ?string $address,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?string $bio,
        public readonly ?int $experienceYears,
        public readonly ?int $age,
        public readonly ?float $rating,
        public readonly ?int $reviewsCount,
        public readonly ?int $viewsCount,
        public readonly ?int $completedBookings,
        public readonly bool $homeService,
        public readonly bool $salonService,
        public readonly bool $isVerified,
        public readonly bool $isPremium,
        public readonly ?string $premiumUntil,
        public readonly ?array $services,
        public readonly ?array $specializations,
        public readonly ?array $certificates,
        public readonly ?array $workingHours,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            displayName: $data['display_name'],
            slug: $data['slug'] ?? '',
            status: isset($data['status']) ? MasterStatus::from($data['status']) : MasterStatus::DRAFT,
            level: isset($data['level']) ? MasterLevel::from($data['level']) : MasterLevel::BEGINNER,
            city: $data['city'],
            district: $data['district'] ?? null,
            metroStation: $data['metro_station'] ?? null,
            address: $data['address'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            bio: $data['bio'] ?? null,
            experienceYears: $data['experience_years'] ?? 0,
            age: $data['age'] ?? null,
            rating: $data['rating'] ?? 0.0,
            reviewsCount: $data['reviews_count'] ?? 0,
            viewsCount: $data['views_count'] ?? 0,
            completedBookings: $data['completed_bookings'] ?? 0,
            homeService: $data['home_service'] ?? false,
            salonService: $data['salon_service'] ?? true,
            isVerified: $data['is_verified'] ?? false,
            isPremium: $data['is_premium'] ?? false,
            premiumUntil: $data['premium_until'] ?? null,
            services: $data['services'] ?? null,
            specializations: $data['specializations'] ?? null,
            certificates: $data['certificates'] ?? null,
            workingHours: $data['working_hours'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'user_id' => $this->userId,
            'display_name' => $this->displayName,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'level' => $this->level->value,
            'city' => $this->city,
            'district' => $this->district,
            'metro_station' => $this->metroStation,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'bio' => $this->bio,
            'experience_years' => $this->experienceYears,
            'age' => $this->age,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'views_count' => $this->viewsCount,
            'completed_bookings' => $this->completedBookings,
            'home_service' => $this->homeService,
            'salon_service' => $this->salonService,
            'is_verified' => $this->isVerified,
            'is_premium' => $this->isPremium,
            'premium_until' => $this->premiumUntil,
            'services' => $this->services,
            'specializations' => $this->specializations,
            'certificates' => $this->certificates,
            'working_hours' => $this->workingHours,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }
}