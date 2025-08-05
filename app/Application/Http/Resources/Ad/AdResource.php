<?php

namespace App\Application\Http\Resources\Ad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Application\Http\Resources\User\UserResource;
use App\Application\Http\Resources\Media\MediaCollectionResource;

/**
 * API Resource для объявлений
 * Структурирует данные объявления для API ответов
 */
class AdResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'category' => $this->category,
            'specialty' => $this->specialty,
            'description' => $this->description,
            
            // Контактная информация
            'contact' => [
                'phone' => $this->phone,
                'contact_method' => $this->contact_method,
                'whatsapp' => $this->whatsapp,
                'telegram' => $this->telegram,
            ],
            
            // Ценообразование
            'pricing' => [
                'price' => $this->price,
                'price_unit' => $this->price_unit,
                'price_per_hour' => $this->price_per_hour,
                'outcall_price' => $this->outcall_price,
                'min_order' => $this->min_order,
                'has_discounts' => $this->has_discounts,
            ],
            
            // Местоположение и география
            'location' => [
                'service_location' => $this->service_location,
                'address' => $this->address,
                'geo' => $this->geo,
                'outcall_locations' => $this->outcall_locations,
                'travel_area' => $this->travel_area,
            ],
            
            // Рабочая информация
            'work' => [
                'work_format' => $this->work_format,
                'experience' => $this->experience,
                'clients' => $this->clients,
                'service_provider' => $this->service_provider,
                'schedule' => $this->schedule,
                'schedule_notes' => $this->schedule_notes,
            ],
            
            // Услуги и особенности
            'services' => $this->services,
            'features' => $this->features,
            'additional_features' => $this->additional_features,
            'education' => $this->education,
            'certificates' => $this->certificates,
            
            // Медиа контент
            'media' => [
                'main_photo' => $this->main_photo,
                'photos' => new MediaCollectionResource($this->whenLoaded('photos')),
                'videos' => new MediaCollectionResource($this->whenLoaded('videos')),
            ],
            
            // Статистика и метрики
            'stats' => [
                'views_count' => $this->views_count,
                'favorites_count' => $this->favorites_count,
                'bookings_count' => $this->bookings_count,
                'rating' => $this->rating,
                'reviews_count' => $this->reviews_count,
            ],
            
            // Временные метки
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            
            // Связанные данные (загружаются при необходимости)
            'user' => new UserResource($this->whenLoaded('user')),
            'master_profile' => $this->whenLoaded('masterProfile'),
            
            // Вычисляемые поля
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_premium' => $this->isPremium(),
            'can_book' => $this->canBook(),
            'url' => route('ads.show', $this->slug),
            
            // Поля для владельца объявления
            $this->mergeWhen($this->isOwner($request->user()), [
                'draft_data' => $this->draft_data,
                'admin_notes' => $this->admin_notes,
                'rejection_reason' => $this->rejection_reason,
                'moderation_status' => $this->moderation_status,
            ])
        ];
    }
    
    /**
     * Проверяет, является ли текущий пользователь владельцем объявления
     */
    private function isOwner($user): bool
    {
        return $user && $user->id === $this->user_id;
    }
}