<?php

namespace App\Domain\Review\DTOs;

use App\Enums\ReviewRating;

/**
 * DTO для обновления отзыва
 */
class UpdateReviewDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $comment = null,
        public ?ReviewRating $rating = null,
        public ?array $pros = null,
        public ?array $cons = null,
        public ?array $photos = null,
        public ?bool $isAnonymous = null,
        public ?bool $isRecommended = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            comment: $data['comment'] ?? null,
            rating: isset($data['rating']) ? ReviewRating::fromValue($data['rating']) : null,
            pros: $data['pros'] ?? null,
            cons: $data['cons'] ?? null,
            photos: $data['photos'] ?? null,
            isAnonymous: $data['is_anonymous'] ?? null,
            isRecommended: $data['is_recommended'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Преобразовать в массив (только заполненные поля)
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'comment' => $this->comment,
            'rating' => $this->rating,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'photos' => $this->photos,
            'is_anonymous' => $this->isAnonymous,
            'is_recommended' => $this->isRecommended,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    /**
     * Проверить, есть ли изменения
     */
    public function hasChanges(): bool
    {
        return !empty($this->toArray());
    }
}