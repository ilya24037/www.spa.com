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
        // 🔍 ПРОСТОЕ ЛОГИРОВАНИЕ
        \Log::info('🔍 AdResource::toArray ВЫЗВАН', [
            'ad_id' => $this->id,
            'work_format' => $this->work_format,
            'photos_raw' => $this->photos,
            'photos_type' => gettype($this->photos),
            'photos_is_array' => is_array($this->photos),
            'photos_count' => is_array($this->photos) ? count($this->photos) : 'НЕ МАССИВ'
        ]);
        
        // Парсим JSON поля для корректной работы с формами
        $parsedData = $this->parseJsonFields();
        
        return array_merge([
            // Основные поля
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'category' => $this->category,
            'description' => $this->description,

            // Простое поле цены для карточек (как было в архиве)
            'price' => $this->price_per_hour ?? $this->price ?? null,
            
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
            
            // Рабочая информация (с парсингом JSON)
            'work' => [
                'work_format' => $this->work_format,
                'experience' => $this->experience,
                'clients' => $parsedData['clients'] ?? $this->clients,
                'service_provider' => $parsedData['service_provider'] ?? $this->service_provider,
                'schedule' => $parsedData['schedule'] ?? $this->schedule,
                'schedule_notes' => $this->schedule_notes,
            ],
            
            // Ключевые поля для форм (с парсингом JSON)
            'clients' => $parsedData['clients'] ?? $this->clients,
            'service_provider' => $parsedData['service_provider'] ?? $this->service_provider,
            'work_format' => $this->work_format,
            'schedule' => $parsedData['schedule'] ?? $this->schedule,
            'faq' => $parsedData['faq'] ?? $this->faq,

            // Возвращаем prices на корневой уровень (как было в архиве)
            'prices' => $parsedData['prices'] ?? $this->prices,
            
            // Параметры (для форм редактирования)
            'title' => $this->title, // Дублируем для совместимости
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            'breast_size' => $this->breast_size,
            'hair_color' => $this->hair_color,
            'eye_color' => $this->eye_color,
            'nationality' => $this->nationality,
            'appearance' => $this->appearance,
            'has_girlfriend' => $this->has_girlfriend,
            'discount' => $this->discount,
            'new_client_discount' => $this->new_client_discount,
            'gift' => $this->gift,
            
            // Дополнительные поля для совместимости с формами (с парсингом JSON)
            'prices' => $parsedData['prices'] ?? $this->prices,
            'geo' => $parsedData['geo'] ?? $this->geo,
            'description' => $this->description,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'telegram' => $this->telegram,
            'contact_method' => $this->contact_method,
            'address' => $this->address,
            'category' => $this->category,
            'photos' => $parsedData['photos'] ?? $this->photos,
            'video' => $parsedData['video'] ?? $this->video,
            'services_additional_info' => $this->services_additional_info,
            'schedule_notes' => $this->schedule_notes,
            
            // Услуги и особенности (с парсингом JSON)
            'services' => $parsedData['services'] ?? $this->services,
            'features' => $parsedData['features'] ?? $this->features,
            'additional_features' => $this->additional_features,
            'education' => $this->education,
            'certificates' => $this->certificates,
            
            // Медиа контент
            'media' => [
                'main_photo' => $this->main_photo,
                'photos' => $parsedData['photos'] ?? $this->photos,
                'videos' => $parsedData['video'] ?? $this->video,
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

            // Вычисляемые поля
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_premium' => false, // Заглушка до реализации премиум функционала
            'can_book' => $this->canBePublished(), // Используем существующий метод
            'url' => route('ads.show', $this->id), // Используем id вместо slug
            
            // Поля верификации
            'verification_photo' => $this->verification_photo,
            'verification_video' => $this->verification_video,
            'verification_status' => $this->verification_status,
            'verification_type' => $this->verification_type,
            'verified_at' => $this->verified_at,
            'verification_expires_at' => $this->verification_expires_at,
            'verification_comment' => $this->verification_comment,
            'verification_metadata' => $this->verification_metadata,
            
            // Поля для владельца объявления
            $this->mergeWhen($this->isOwner($request->user()), [
                'draft_data' => $this->draft_data,
                'admin_notes' => $this->admin_notes,
                'rejection_reason' => $this->rejection_reason,
                'moderation_status' => $this->moderation_status,
            ])
        ], $parsedData);
    }
    
    /**
     * Парсит JSON поля для корректной работы с формами
     */
    private function parseJsonFields(): array
    {
        $jsonFields = ['clients', 'service_provider', 'services', 'features', 'photos', 'video', 'geo', 'prices', 'schedule', 'faq'];
        $parsed = [];
        
        foreach ($jsonFields as $field) {
            $value = $this->$field ?? null;
            \Log::info("🔍 parseJsonFields: Обрабатываем поле {$field}", [
                'value' => $value,
                'value_type' => gettype($value),
                'is_string' => is_string($value),
                'is_array' => is_array($value)
            ]);
            
            if (isset($this->$field) && is_string($this->$field)) {
                try {
                    $decoded = json_decode($this->$field, true);
                    if (is_array($decoded)) {
                        $parsed[$field] = $decoded;
                        \Log::info("🔍 parseJsonFields: {$field} - строка, декодировали", [
                            'decoded' => $decoded,
                            'result' => $parsed[$field]
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::info("🔍 parseJsonFields: {$field} - ошибка декодирования", [
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            } else {
                // Для photos: обрабатываем как массив строк (путей к файлам) или объектов
                if ($field === 'photos' && is_array($value)) {
                    $processedPhotos = [];
                    foreach ($value as $photo) {
                        if (is_string($photo) && !empty($photo)) {
                            // Это строка (путь к файлу) - преобразуем в объект
                            $processedPhotos[] = [
                                'url' => $photo,
                                'preview' => $photo
                            ];
                        } elseif (is_array($photo) && !empty($photo) && (isset($photo['url']) || isset($photo['preview']))) {
                            // Это уже объект с url/preview
                            $processedPhotos[] = $photo;
                        }
                    }
                    $parsed[$field] = $processedPhotos;
                    \Log::info("🔍 parseJsonFields: {$field} - массив, обработали фотографии", [
                        'original' => $value,
                        'processed_count' => count($processedPhotos),
                        'result' => $parsed[$field]
                    ]);
                } else {
                    $parsed[$field] = $value ?? [];
                    \Log::info("🔍 parseJsonFields: {$field} - не строка, используем как есть", [
                        'result' => $parsed[$field]
                    ]);
                }
            }
        }
        
        // Специальная обработка для geo - синхронизируем адрес
        if (!empty($this->address)) {
            if (!isset($parsed['geo']['address']) || empty($parsed['geo']['address'])) {
                $parsed['geo']['address'] = $this->address;
                \Log::info("🔍 parseJsonFields: Синхронизирован адрес в geo", [
                    'address' => $this->address,
                    'geo_after' => $parsed['geo']
                ]);
            }
        }
        
        return $parsed;
    }
    
    /**
     * Проверяет, является ли текущий пользователь владельцем объявления
     */
    private function isOwner($user): bool
    {
        return $user && $user->id === $this->user_id;
    }
}