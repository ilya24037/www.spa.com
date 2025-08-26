<?php

namespace App\Domain\Ad\DTOs;

use App\Domain\Ad\Enums\AdStatus;
use App\Enums\WorkFormat;
use Illuminate\Support\Collection;

class AdData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $category,
        public readonly AdStatus $status,
        public readonly ?string $address,
        public readonly ?WorkFormat $workFormat,
        public readonly ?array $serviceLocation,
        public readonly ?int $age,
        public readonly ?string $experience,
        public readonly ?int $viewsCount,
        public readonly ?int $contactsShown,
        public readonly ?int $favoritesCount,
        public readonly ?string $expiresAt,
        public readonly ?AdContentData $content,
        public readonly ?AdPricingData $pricing,
        public readonly ?AdScheduleData $schedule,
        public readonly ?Collection $media,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            category: $data['category'],
            status: isset($data['status']) ? AdStatus::from($data['status']) : AdStatus::DRAFT,
            address: $data['address'] ?? null,
            workFormat: isset($data['work_format']) ? WorkFormat::from($data['work_format']) : null,
            serviceLocation: $data['service_location'] ?? null,
            age: $data['age'] ?? null,
            experience: $data['experience'] ?? null,
            viewsCount: $data['views_count'] ?? 0,
            contactsShown: $data['contacts_shown'] ?? 0,
            favoritesCount: $data['favorites_count'] ?? 0,
            expiresAt: $data['expires_at'] ?? null,
            content: isset($data['content']) ? AdContentData::fromArray($data['content']) : null,
            pricing: isset($data['pricing']) ? AdPricingData::fromArray($data['pricing']) : null,
            schedule: isset($data['schedule']) ? AdScheduleData::fromArray($data['schedule']) : null,
            media: isset($data['media']) ? collect($data['media']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'user_id' => $this->userId,
            'category' => $this->category,
            'status' => $this->status->value,
            'address' => $this->address,
            'work_format' => $this->workFormat?->value,
            'service_location' => $this->serviceLocation,
            'age' => $this->age,
            'experience' => $this->experience,
            'views_count' => $this->viewsCount,
            'contacts_shown' => $this->contactsShown,
            'favorites_count' => $this->favoritesCount,
            'expires_at' => $this->expiresAt,
            'content' => $this->content?->toArray(),
            'pricing' => $this->pricing?->toArray(),
            'schedule' => $this->schedule?->toArray(),
            'media' => $this->media?->toArray(),
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }
}