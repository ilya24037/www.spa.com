<?php

namespace App\Infrastructure\Search\Indexers;

use App\Domain\Ad\Models\Ad;

/**
 * Трансформер объявлений в документы Elasticsearch
 */
class AdDocumentTransformer
{
    /**
     * Преобразовать модель в документ для индексации
     */
    public function transformToDocument(Ad $ad): array
    {
        $ad->load(['user', 'media']);
        
        return [
            // Основная информация
            'id' => $ad->id,
            'title' => $ad->title,
            'description' => strip_tags($ad->description),
            'specialty' => $ad->specialty,
            'additional_features' => $ad->additional_features,
            
            // Цена
            'price' => $ad->price,
            'price_currency' => $ad->price_currency ?? 'RUB',
            'price_type' => $ad->price_type ?? 'fixed',
            
            // Локация
            'city' => $ad->city,
            'region' => $ad->region,
            'metro_station' => $ad->metro_station,
            'address' => $ad->address,
            'location' => $this->getGeoPoint($ad),
            
            // Информация о мастере
            'master' => [
                'id' => $ad->user->id,
                'name' => $ad->user->name,
                'rating' => $ad->user->rating ?? 0,
                'reviews_count' => $ad->user->reviews_count ?? 0,
                'experience_years' => $ad->user->experience_years ?? 0,
                'is_verified' => $ad->user->is_verified ?? false,
                'is_premium' => $ad->user->is_premium ?? false,
                'avatar_url' => $ad->user->avatar_url
            ],
            
            // Статус и флаги
            'status' => $ad->status,
            'is_published' => $ad->is_published,
            'is_available' => $ad->is_available ?? true,
            'is_premium' => $ad->is_premium ?? false,
            'ad_type' => $ad->ad_type ?? 'standard',
            'work_format' => $ad->work_format,
            
            // Медиа
            'media_count' => $ad->media->count(),
            'has_photos' => $ad->media->where('type', 'image')->isNotEmpty(),
            'has_videos' => $ad->media->where('type', 'video')->isNotEmpty(),
            'media_urls' => $ad->media->pluck('url')->toArray(),
            
            // Даты
            'created_at' => $ad->created_at->toIso8601String(),
            'updated_at' => $ad->updated_at->toIso8601String(),
            'published_at' => $ad->published_at?->toIso8601String(),
            
            // Статистика
            'views_count' => $ad->views_count ?? 0,
            'favorites_count' => $ad->favorites_count ?? 0,
            'bookings_count' => $ad->bookings_count ?? 0,
            
            // Дополнительные поля для поиска
            'tags' => $this->extractTags($ad),
            'categories' => $this->extractCategories($ad),
            'services' => $this->extractServices($ad),
            
            // Для ранжирования
            'boost_score' => $this->calculateBoostScore($ad),
            'relevance_score' => 0 // Будет вычисляться при поиске
        ];
    }

    /**
     * Получить гео-точку для объявления
     */
    protected function getGeoPoint(Ad $ad): ?array
    {
        if ($ad->latitude && $ad->longitude) {
            return [
                'lat' => $ad->latitude,
                'lon' => $ad->longitude
            ];
        }
        
        return null;
    }

    /**
     * Извлечь теги из объявления
     */
    protected function extractTags(Ad $ad): array
    {
        $tags = [];
        
        // Извлекаем теги из описания и дополнительных функций
        if ($ad->tags) {
            $tags = array_merge($tags, $ad->tags);
        }
        
        // Добавляем специальность как тег
        if ($ad->specialty) {
            $tags[] = $ad->specialty;
        }
        
        // Добавляем формат работы
        if ($ad->work_format) {
            $tags[] = $ad->work_format;
        }
        
        return array_unique($tags);
    }

    /**
     * Извлечь категории
     */
    protected function extractCategories(Ad $ad): array
    {
        $categories = [];
        
        if ($ad->category_id) {
            $categories[] = $ad->category_id;
        }
        
        if ($ad->subcategory_id) {
            $categories[] = $ad->subcategory_id;
        }
        
        return $categories;
    }

    /**
     * Извлечь услуги
     */
    protected function extractServices(Ad $ad): array
    {
        if ($ad->services) {
            return $ad->services->pluck('id')->toArray();
        }
        
        return [];
    }

    /**
     * Вычислить boost score для ранжирования
     */
    protected function calculateBoostScore(Ad $ad): float
    {
        $score = 1.0;
        
        // Премиум объявления
        if ($ad->is_premium) {
            $score += 0.5;
        }
        
        // Верифицированный мастер
        if ($ad->user->is_verified) {
            $score += 0.3;
        }
        
        // Рейтинг мастера
        if ($ad->user->rating >= 4.5) {
            $score += 0.4;
        } elseif ($ad->user->rating >= 4.0) {
            $score += 0.2;
        }
        
        // Количество отзывов
        if ($ad->user->reviews_count >= 50) {
            $score += 0.3;
        } elseif ($ad->user->reviews_count >= 20) {
            $score += 0.2;
        } elseif ($ad->user->reviews_count >= 10) {
            $score += 0.1;
        }
        
        // Наличие медиа
        if ($ad->media->count() >= 5) {
            $score += 0.2;
        } elseif ($ad->media->count() >= 3) {
            $score += 0.1;
        }
        
        // Полнота профиля
        if ($ad->description && strlen($ad->description) > 200) {
            $score += 0.1;
        }
        
        // Активность
        if ($ad->updated_at->isAfter(now()->subDays(7))) {
            $score += 0.2;
        } elseif ($ad->updated_at->isAfter(now()->subDays(30))) {
            $score += 0.1;
        }
        
        return round($score, 2);
    }
}