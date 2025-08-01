<?php

namespace App\Domain\Search\DTOs;

class SearchResultData
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly ?float $price,
        public readonly ?string $location,
        public readonly ?float $rating,
        public readonly ?int $reviewsCount,
        public readonly ?bool $isVerified,
        public readonly ?bool $isPremium,
        public readonly ?array $highlights,
        public readonly float $relevanceScore,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            type: $data['type'],
            title: $data['title'],
            description: $data['description'] ?? null,
            imageUrl: $data['image_url'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            location: $data['location'] ?? null,
            rating: isset($data['rating']) ? (float) $data['rating'] : null,
            reviewsCount: $data['reviews_count'] ?? null,
            isVerified: $data['is_verified'] ?? false,
            isPremium: $data['is_premium'] ?? false,
            highlights: $data['highlights'] ?? null,
            relevanceScore: (float) ($data['relevance_score'] ?? 0),
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'price' => $this->price,
            'location' => $this->location,
            'rating' => $this->rating,
            'reviews_count' => $this->reviewsCount,
            'is_verified' => $this->isVerified,
            'is_premium' => $this->isPremium,
            'highlights' => $this->highlights,
            'relevance_score' => $this->relevanceScore,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function getFormattedPrice(): ?string
    {
        if ($this->price === null) {
            return null;
        }
        
        return number_format($this->price, 0, ',', ' ') . ' ₽';
    }

    public function getRatingStars(): ?string
    {
        if ($this->rating === null) {
            return null;
        }
        
        $fullStars = floor($this->rating);
        $halfStar = ($this->rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;
        
        return str_repeat('★', $fullStars) . 
               str_repeat('☆', $halfStar) . 
               str_repeat('☆', $emptyStars);
    }
}