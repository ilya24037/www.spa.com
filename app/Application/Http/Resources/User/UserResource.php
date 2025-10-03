<?php

namespace App\Application\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource для пользователей
 * Структурирует данные пользователя для API ответов
 */
class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Публичные данные профиля (объединены из MasterProfile в User)
            'slug' => $this->slug,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'views_count' => $this->views_count,
            'is_verified' => $this->is_verified,
            'join_date' => $this->created_at?->format('Y-m-d'),

            // Профиль пользователя (если загружен - deprecated, используйте поля выше)
            'profile' => $this->whenLoaded('profile'),

            // Приватные данные только для владельца аккаунта
            $this->mergeWhen($this->isOwner($request->user()), [
                'email' => $this->email,
                'phone' => $this->phone,
                'settings' => $this->settings ?? [],
            ])
        ];
    }
    
    /**
     * Проверяет, является ли текущий пользователь владельцем профиля
     */
    private function isOwner($user): bool
    {
        return $user && $user->id === $this->id;
    }
}