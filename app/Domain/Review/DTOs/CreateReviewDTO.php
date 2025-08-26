<?php

declare(strict_types=1);

namespace App\Domain\Review\DTOs;

use Illuminate\Http\Request;

final class CreateReviewDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $reviewableUserId,
        public readonly ?int $adId,
        public readonly int $rating,
        public readonly ?string $comment,
        public readonly bool $isAnonymous = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: auth()->id(),
            reviewableUserId: (int) $request->input('reviewable_user_id'),
            adId: $request->input('ad_id') ? (int) $request->input('ad_id') : null,
            rating: (int) $request->input('rating'),
            comment: $request->input('comment'),
            isAnonymous: (bool) $request->input('is_anonymous', false),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'reviewable_user_id' => $this->reviewableUserId,
            'ad_id' => $this->adId,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_anonymous' => $this->isAnonymous,
            'is_visible' => true,
            'is_verified' => false,
        ];
    }
}