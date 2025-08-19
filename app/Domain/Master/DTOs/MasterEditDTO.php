<?php

namespace App\Domain\Master\DTOs;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Support\Collection;

/**
 * DTO для редактирования профиля мастера
 */
class MasterEditDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $specialization,
        public readonly ?string $description,
        public readonly ?int $experienceYears,
        public readonly ?float $hourlyRate,
        public readonly ?string $city,
        public readonly ?int $age,
        public readonly ?int $height,
        public readonly ?int $weight,
        public readonly ?string $breastSize,
        public readonly Collection $photos,
        public readonly ?array $video
    ) {}
    
    /**
     * Создание DTO из модели
     */
    public static function fromModel(MasterProfile $profile): self
    {
        $galleryService = app(\App\Domain\Master\Services\MasterGalleryService::class);
        
        return new self(
            id: $profile->id,
            name: $profile->display_name,
            specialization: $profile->specialization,
            description: $profile->bio,
            experienceYears: $profile->experience_years,
            hourlyRate: $profile->hourly_rate,
            city: $profile->city,
            age: $profile->age,
            height: $profile->height,
            weight: $profile->weight,
            breastSize: $profile->breast_size,
            photos: $galleryService->buildEditGallery($profile),
            video: self::buildVideoData($profile)
        );
    }
    
    /**
     * Преобразование в массив для формы
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialization' => $this->specialization,
            'description' => $this->description,
            'experience_years' => $this->experienceYears,
            'hourly_rate' => $this->hourlyRate,
            'city' => $this->city,
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            'breast_size' => $this->breastSize,
            'photos' => $this->photos->toArray(),
            'video' => $this->video
        ];
    }
    
    /**
     * Построение данных о видео
     */
    private static function buildVideoData(MasterProfile $profile): ?array
    {
        if (!$profile->video) {
            return null;
        }
        
        return [
            'id' => $profile->video->id,
            'filename' => $profile->video->filename,
            'video_url' => $profile->video->video_url,
            'poster_url' => $profile->video->poster_url,
            'duration' => $profile->video->formatted_duration,
            'file_size' => $profile->video->file_size
        ];
    }
}