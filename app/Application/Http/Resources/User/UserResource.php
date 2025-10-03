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
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Профиль пользователя (если загружен)
            'profile' => $this->whenLoaded('profile'),

            // Профиль мастера (если загружен)
            'masterProfile' => $this->whenLoaded('masterProfile'),

            // Публичные данные
            'is_verified' => $this->email_verified_at !== null,
            'join_date' => $this->created_at?->format('Y-m-d'),
            
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