<?php

declare(strict_types=1);

namespace App\Domain\Review\DTOs;

use Illuminate\Http\Request;

final class UpdateReviewDTO
{
    public function __construct(
        public readonly ?int $rating,
        public readonly ?string $comment,
        public readonly ?bool $isVisible,
        public readonly ?bool $isVerified,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            rating: $request->has('rating') ? (int) $request->input('rating') : null,
            comment: $request->has('comment') ? $request->input('comment') : null,
            isVisible: $request->has('is_visible') ? (bool) $request->input('is_visible') : null,
            isVerified: $request->has('is_verified') ? (bool) $request->input('is_verified') : null,
        );
    }

    public function toArray(): array
    {
        $data = [];
        
        if ($this->rating !== null) {
            $data['rating'] = $this->rating;
        }
        
        if ($this->comment !== null) {
            $data['comment'] = $this->comment;
        }
        
        if ($this->isVisible !== null) {
            $data['is_visible'] = $this->isVisible;
        }
        
        if ($this->isVerified !== null) {
            $data['is_verified'] = $this->isVerified;
        }
        
        return $data;
    }
}