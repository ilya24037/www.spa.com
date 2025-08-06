<?php

namespace App\Infrastructure\Search\Transformers;

use App\Domain\Master\Models\MasterProfile;

/**
 * Трансформер для преобразования мастера в документ Elasticsearch
 */
class MasterDocumentTransformer
{
    /**
     * Преобразовать мастера в документ для индексации
     */
    public function transform(MasterProfile $master): array
    {
        return [
            // Основная информация
            'id' => $master->id,
            'user_id' => $master->user_id,
            'name' => $master->user->name ?? '',
            'slug' => $master->slug,
            'about' => $master->about,
            'specialty' => $master->specialty,
            'specializations' => $this->extractSpecializations($master),
            
            // Локация
            'city' => $master->city,
            'district' => $master->district,
            'address' => $master->address,
            'metro_stations' => $this->extractMetroStations($master),
            'location' => $this->getGeoPoint($master),
            
            // Рейтинг и отзывы
            'rating' => $master->rating ?? 0,
            'reviews_count' => $master->reviews_count ?? 0,
            'likes_count' => $master->likes_count ?? 0,
            'views_count' => $master->views_count ?? 0,
            
            // Опыт и статистика
            'experience_years' => $master->experience_years ?? 0,
            'completed_orders' => $master->completed_orders ?? 0,
            'repeat_clients_percent' => $master->repeat_clients_percent ?? 0,
            
            // Статус
            'is_active' => $master->is_active,
            'is_verified' => $master->is_verified ?? false,
            'is_premium' => $master->is_premium ?? false,
            'is_online' => $this->isOnline($master),
            'verification_level' => $master->verification_level ?? 'none',
            
            // Услуги и цены
            'services' => $this->transformServices($master),
            'price_min' => $this->getMinPrice($master),
            'price_max' => $this->getMaxPrice($master),
            'average_price' => $this->getAveragePrice($master),
            
            // Расписание
            'working_hours' => $this->transformWorkingHours($master),
            'available_now' => $this->isAvailableNow($master),
            'next_available_slot' => $this->getNextAvailableSlot($master),
            
            // Медиа
            'avatar_url' => $master->user->avatar_url,
            'photos_count' => $master->media->where('type', 'image')->count(),
            'videos_count' => $master->media->where('type', 'video')->count(),
            'has_portfolio' => $master->media->isNotEmpty(),
            
            // Образование и сертификаты
            'education' => $this->transformEducation($master),
            'certificates' => $this->transformCertificates($master),
            
            // Даты
            'created_at' => $master->created_at->toIso8601String(),
            'updated_at' => $master->updated_at->toIso8601String(),
            'last_active_at' => $master->last_active_at?->toIso8601String(),
            'verified_at' => $master->verified_at?->toIso8601String(),
            
            // SEO и дополнительные поля
            'meta_title' => $master->meta_title,
            'meta_description' => $master->meta_description,
            'meta_keywords' => $this->extractKeywords($master),
            'tags' => $this->extractTags($master),
            'skills' => $this->extractSkills($master),
            'languages' => $master->languages ?? ['русский'],
            'work_formats' => $this->extractWorkFormats($master),
        ];
    }

    /**
     * Извлечь специализации
     */
    private function extractSpecializations(MasterProfile $master): array
    {
        $specializations = [];
        
        if ($master->specializations) {
            $specializations = is_array($master->specializations) 
                ? $master->specializations 
                : json_decode($master->specializations, true) ?? [];
        }
        
        if ($master->specialty && !in_array($master->specialty, $specializations)) {
            $specializations[] = $master->specialty;
        }
        
        return $specializations;
    }

    /**
     * Извлечь станции метро
     */
    private function extractMetroStations(MasterProfile $master): array
    {
        if ($master->metro_stations) {
            return is_array($master->metro_stations)
                ? $master->metro_stations
                : json_decode($master->metro_stations, true) ?? [];
        }
        
        return [];
    }

    /**
     * Получить гео-точку
     */
    private function getGeoPoint(MasterProfile $master): ?array
    {
        if ($master->latitude && $master->longitude) {
            return [
                'lat' => $master->latitude,
                'lon' => $master->longitude
            ];
        }
        
        return null;
    }

    /**
     * Проверить онлайн статус
     */
    private function isOnline(MasterProfile $master): bool
    {
        return $master->last_active_at && $master->last_active_at->diffInMinutes(now()) <= 15;
    }

    /**
     * Трансформировать услуги
     */
    private function transformServices(MasterProfile $master): array
    {
        return $master->services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'duration' => $service->duration,
                'description' => $service->description
            ];
        })->toArray();
    }

    /**
     * Получить минимальную цену
     */
    private function getMinPrice(MasterProfile $master): int
    {
        return $master->services->min('price') ?? 0;
    }

    /**
     * Получить максимальную цену
     */
    private function getMaxPrice(MasterProfile $master): int
    {
        return $master->services->max('price') ?? 0;
    }

    /**
     * Получить среднюю цену
     */
    private function getAveragePrice(MasterProfile $master): int
    {
        return (int) $master->services->avg('price') ?? 0;
    }

    /**
     * Трансформировать рабочие часы
     */
    private function transformWorkingHours(MasterProfile $master): array
    {
        if ($master->working_hours) {
            return is_array($master->working_hours)
                ? $master->working_hours
                : json_decode($master->working_hours, true) ?? [];
        }
        
        return [];
    }

    /**
     * Проверить доступность сейчас
     */
    private function isAvailableNow(MasterProfile $master): bool
    {
        $workingHours = $this->transformWorkingHours($master);
        $now = now();
        $dayOfWeek = $now->format('l');
        
        if (!isset($workingHours[strtolower($dayOfWeek)])) {
            return false;
        }
        
        $todayHours = $workingHours[strtolower($dayOfWeek)];
        if (!$todayHours['is_working']) {
            return false;
        }
        
        $currentTime = $now->format('H:i');
        return $currentTime >= $todayHours['start'] && $currentTime <= $todayHours['end'];
    }

    /**
     * Получить следующий доступный слот
     */
    private function getNextAvailableSlot(MasterProfile $master): ?string
    {
        // Упрощенная логика - в реальности нужно учитывать забронированное время
        $workingHours = $this->transformWorkingHours($master);
        
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i);
            $dayOfWeek = strtolower($date->format('l'));
            
            if (isset($workingHours[$dayOfWeek]) && $workingHours[$dayOfWeek]['is_working']) {
                return $date->format('Y-m-d') . ' ' . $workingHours[$dayOfWeek]['start'];
            }
        }
        
        return null;
    }

    /**
     * Трансформировать образование
     */
    private function transformEducation(MasterProfile $master): array
    {
        if ($master->education) {
            $education = is_array($master->education)
                ? $master->education
                : json_decode($master->education, true) ?? [];
                
            return array_map(function ($edu) {
                return [
                    'institution' => $edu['institution'] ?? '',
                    'specialty' => $edu['specialty'] ?? '',
                    'year' => $edu['year'] ?? null
                ];
            }, $education);
        }
        
        return [];
    }

    /**
     * Трансформировать сертификаты
     */
    private function transformCertificates(MasterProfile $master): array
    {
        if ($master->certificates) {
            $certificates = is_array($master->certificates)
                ? $master->certificates
                : json_decode($master->certificates, true) ?? [];
                
            return array_map(function ($cert) {
                return [
                    'name' => $cert['name'] ?? '',
                    'organization' => $cert['organization'] ?? '',
                    'year' => $cert['year'] ?? null,
                    'number' => $cert['number'] ?? ''
                ];
            }, $certificates);
        }
        
        return [];
    }

    /**
     * Извлечь ключевые слова
     */
    private function extractKeywords(MasterProfile $master): array
    {
        $keywords = [];
        
        if ($master->about) {
            $keywords = array_merge($keywords, explode(' ', $master->about));
        }
        
        if ($master->specialty) {
            $keywords[] = $master->specialty;
        }
        
        $keywords = array_merge($keywords, $this->extractSpecializations($master));
        
        return array_unique(array_filter($keywords));
    }

    /**
     * Извлечь теги
     */
    private function extractTags(MasterProfile $master): array
    {
        return $master->tags ?? [];
    }

    /**
     * Извлечь навыки
     */
    private function extractSkills(MasterProfile $master): array
    {
        return $master->skills ?? [];
    }

    /**
     * Извлечь форматы работы
     */
    private function extractWorkFormats(MasterProfile $master): array
    {
        return $master->work_formats ?? ['на дому у мастера', 'выезд к клиенту'];
    }
}