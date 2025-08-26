<?php

declare(strict_types=1);

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn() => $this->user->name),
                'avatar' => $this->whenLoaded('user', fn() => $this->user->avatar),
            ],
            'reviewable_user' => [
                'id' => $this->reviewable_user_id,
                'name' => $this->whenLoaded('reviewableUser', fn() => $this->reviewableUser->name),
                'avatar' => $this->whenLoaded('reviewableUser', fn() => $this->reviewableUser->avatar),
            ],
            'ad' => $this->when($this->ad_id, [
                'id' => $this->ad_id,
                'title' => $this->whenLoaded('ad', fn() => $this->ad->title),
            ]),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_anonymous' => $this->is_anonymous,
            'status' => $this->status,
            'photos' => $this->whenLoaded('media', function () {
                return $this->media->map(fn($photo) => [
                    'id' => $photo->id,
                    'url' => $photo->getUrl(),
                    'thumbnail' => $photo->getUrl('thumbnail'),
                ]);
            }),
            'created_at' => $this->created_at->toIsoString(),
            'updated_at' => $this->updated_at->toIsoString(),
        ];
    }
}