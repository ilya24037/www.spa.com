<?php

namespace App\Application\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Application\Http\Resources\User\UserResource;
use App\Application\Http\Resources\Media\MediaCollectionResource;
use App\Application\Http\Resources\Ad\AdResource;

/**
 * API Resource для профилей мастеров
 * Структурирует данные мастера для API ответов
 */
class MasterResource extends JsonResource
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
            'display_name' => $this->display_name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'status' => $this->status,
            
            // Контактная информация (зависит от настройки show_contacts)
            'contacts' => $this->when($this->show_contacts || $this->isOwner($request->user()), [
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'telegram' => $this->telegram,
            ]),
            'show_contacts' => $this->show_contacts,
            
            // Профессиональная информация
            'professional' => [
                'experience_years' => $this->experience_years,
                'certificates' => $this->certificates,
                'education' => $this->education,
                'medical_certificate' => $this->medical_certificate,
                'works_during_period' => $this->works_during_period,
            ],
            
            // Физические параметры (публичная информация)
            'appearance' => [
                'age' => $this->age,
                'height' => $this->height,
                'weight' => $this->weight,
                'breast_size' => $this->breast_size,
                'hair_color' => $this->hair_color,
                'eye_color' => $this->eye_color,
                'nationality' => $this->nationality,
            ],
            
            // Особенности и дополнительные услуги
            'features' => $this->features,
            'additional_features' => $this->additional_features,
            
            // Статистика и рейтинг
            'stats' => [
                'rating' => $this->rating,
                'reviews_count' => $this->reviews_count,
                'completed_bookings' => $this->completed_bookings,
                'views_count' => $this->views_count,
            ],
            
            // Статусы и особые отметки
            'badges' => [
                'is_verified' => $this->is_verified,
                'is_premium' => $this->is_premium,
                'premium_until' => $this->premium_until,
            ],
            
            // SEO данные
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
            ],
            
            // Временные метки
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Связанные данные (загружаются при необходимости)
            'user' => new UserResource($this->whenLoaded('user')),
            'ads' => AdResource::collection($this->whenLoaded('ads')),
            'photos' => new MediaCollectionResource($this->whenLoaded('photos')),
            'videos' => new MediaCollectionResource($this->whenLoaded('videos')),
            'schedule' => $this->whenLoaded('schedule'),
            'reviews' => $this->whenLoaded('reviews'),
            
            // Вычисляемые поля
            'is_active' => $this->isActive(),
            'is_available' => $this->isAvailable(),
            'next_available_slot' => $this->getNextAvailableSlot(),
            'profile_completion' => $this->getProfileCompletion(),
            'url' => route('masters.show', $this->slug),
            
            // Поля только для владельца профиля
            $this->mergeWhen($this->isOwner($request->user()), [
                'admin_notes' => $this->admin_notes,
                'last_activity' => $this->last_activity,
                'earnings_total' => $this->earnings_total,
                'earnings_this_month' => $this->earnings_this_month,
                'pending_bookings_count' => $this->pending_bookings_count,
            ])
        ];
    }
    
    /**
     * Проверяет, является ли текущий пользователь владельцем профиля
     */
    private function isOwner($user): bool
    {
        return $user && $user->id === $this->user_id;
    }
}