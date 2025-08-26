<?php

namespace App\Domain\Review\DTOs;

use App\Enums\ReviewStatus;

class ReviewData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $masterId,
        public readonly int $clientId,
        public readonly ?int $bookingId,
        public readonly int $rating,
        public readonly string $comment,
        public readonly ReviewStatus $status,
        public readonly ?array $photos,
        public readonly ?string $masterResponse,
        public readonly ?string $masterRespondedAt,
        public readonly bool $isVerified,
        public readonly ?array $pros,
        public readonly ?array $cons,
        public readonly ?array $serviceIds,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            masterId: $data['master_id'],
            clientId: $data['client_id'],
            bookingId: $data['booking_id'] ?? null,
            rating: (int) $data['rating'],
            comment: $data['comment'],
            status: isset($data['status']) ? ReviewStatus::from($data['status']) : ReviewStatus::PENDING,
            photos: $data['photos'] ?? null,
            masterResponse: $data['master_response'] ?? null,
            masterRespondedAt: $data['master_responded_at'] ?? null,
            isVerified: $data['is_verified'] ?? false,
            pros: $data['pros'] ?? null,
            cons: $data['cons'] ?? null,
            serviceIds: $data['service_ids'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'master_id' => $this->masterId,
            'client_id' => $this->clientId,
            'booking_id' => $this->bookingId,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status->value,
            'photos' => $this->photos,
            'master_response' => $this->masterResponse,
            'master_responded_at' => $this->masterRespondedAt,
            'is_verified' => $this->isVerified,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'service_ids' => $this->serviceIds,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function getRatingStars(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function hasResponse(): bool
    {
        return !empty($this->masterResponse);
    }
}