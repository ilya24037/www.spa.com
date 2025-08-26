<?php

namespace App\Domain\Review\DTOs;

use App\Enums\ReviewType;
use App\Enums\ReviewRating;

/**
 * DTO для создания отзыва
 */
class CreateReviewDTO
{
    public function __construct(
        public int $userId,
        public string $reviewableType,
        public int $reviewableId,
        public ReviewType $type,
        public string $comment,
        public ?ReviewRating $rating = null,
        public ?string $title = null,
        public ?int $bookingId = null,
        public array $pros = [],
        public array $cons = [],
        public array $photos = [],
        public bool $isAnonymous = false,
        public bool $isRecommended = false,
        public array $metadata = [],
    ) {}

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            reviewableType: $data['reviewable_type'],
            reviewableId: $data['reviewable_id'],
            type: ReviewType::from($data['type']),
            comment: $data['comment'],
            rating: isset($data['rating']) ? ReviewRating::fromValue($data['rating']) : null,
            title: $data['title'] ?? null,
            bookingId: $data['booking_id'] ?? null,
            pros: $data['pros'] ?? [],
            cons: $data['cons'] ?? [],
            photos: $data['photos'] ?? [],
            isAnonymous: $data['is_anonymous'] ?? false,
            isRecommended: $data['is_recommended'] ?? false,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Создать для услуги
     */
    public static function forService(
        int $userId,
        int $serviceId,
        ReviewRating $rating,
        string $comment,
        ?int $bookingId = null,
        ?string $title = null,
        array $pros = [],
        array $cons = [],
        bool $isRecommended = false
    ): self {
        return new self(
            userId: $userId,
            reviewableType: 'App\\Models\\Service',
            reviewableId: $serviceId,
            type: ReviewType::SERVICE,
            comment: $comment,
            rating: $rating,
            title: $title,
            bookingId: $bookingId,
            pros: $pros,
            cons: $cons,
            isRecommended: $isRecommended,
        );
    }

    /**
     * Создать для мастера
     */
    public static function forMaster(
        int $userId,
        int $masterId,
        ReviewRating $rating,
        string $comment,
        ?int $bookingId = null,
        ?string $title = null,
        array $pros = [],
        array $cons = [],
        bool $isRecommended = false
    ): self {
        return new self(
            userId: $userId,
            reviewableType: 'App\\Models\\MasterProfile',
            reviewableId: $masterId,
            type: ReviewType::MASTER,
            comment: $comment,
            rating: $rating,
            title: $title,
            bookingId: $bookingId,
            pros: $pros,
            cons: $cons,
            isRecommended: $isRecommended,
        );
    }

    /**
     * Создать жалобу
     */
    public static function complaint(
        int $userId,
        string $reviewableType,
        int $reviewableId,
        string $comment,
        ?int $bookingId = null
    ): self {
        return new self(
            userId: $userId,
            reviewableType: $reviewableType,
            reviewableId: $reviewableId,
            type: ReviewType::COMPLAINT,
            comment: $comment,
            bookingId: $bookingId,
        );
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'reviewable_type' => $this->reviewableType,
            'reviewable_id' => $this->reviewableId,
            'type' => $this->type,
            'comment' => $this->comment,
            'rating' => $this->rating,
            'title' => $this->title,
            'booking_id' => $this->bookingId,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'photos' => $this->photos,
            'is_anonymous' => $this->isAnonymous,
            'is_recommended' => $this->isRecommended,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->comment)) {
            $errors[] = 'Comment is required';
        }

        if (strlen($this->comment) < $this->type->getMinLength()) {
            $errors[] = "Comment must be at least {$this->type->getMinLength()} characters";
        }

        if (strlen($this->comment) > $this->type->getMaxLength()) {
            $errors[] = "Comment must not exceed {$this->type->getMaxLength()} characters";
        }

        if ($this->type->requiresRating() && !$this->rating) {
            $errors[] = 'Rating is required for this review type';
        }

        if (!$this->type->allowsPhotos() && !empty($this->photos)) {
            $errors[] = 'Photos are not allowed for this review type';
        }

        if (count($this->photos) > 5) {
            $errors[] = 'Maximum 5 photos allowed';
        }

        return $errors;
    }

    /**
     * Проверить валидность
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Добавить фото
     */
    public function withPhotos(array $photos): self
    {
        $clone = clone $this;
        $clone->photos = array_merge($this->photos, $photos);
        return $clone;
    }

    /**
     * Установить как анонимный
     */
    public function asAnonymous(): self
    {
        $clone = clone $this;
        $clone->isAnonymous = true;
        return $clone;
    }

    /**
     * Добавить метаданные
     */
    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = array_merge($this->metadata, $metadata);
        return $clone;
    }
}