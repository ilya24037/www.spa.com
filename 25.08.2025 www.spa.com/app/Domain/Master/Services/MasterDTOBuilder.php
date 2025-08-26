<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Services\MasterGalleryService;
use App\Domain\Master\Services\MasterService;
use Illuminate\Support\Collection;

/**
 * Сервис для построения DTO профиля мастера
 * Отвечает за формирование структурированных данных для различных представлений
 */
class MasterDTOBuilder
{
    public function __construct(
        private MasterGalleryService $galleryService,
        private MasterService $masterService
    ) {}
    
    /**
     * Построение полного DTO профиля мастера для страницы просмотра
     * 
     * @param MasterProfile $profile
     * @param int|null $userId ID текущего пользователя для проверки избранного
     * @return \App\Domain\Master\DTOs\MasterProfileDTO
     */
    public function buildProfileDTO(MasterProfile $profile, ?int $userId = null): \App\Domain\Master\DTOs\MasterProfileDTO
    {
        return \App\Domain\Master\DTOs\MasterProfileDTO::fromModel($profile, $userId);
    }
    
    /**
     * Построение DTO для страницы редактирования
     * 
     * @param MasterProfile $profile
     * @return \App\Domain\Master\DTOs\MasterEditDTO
     */
    public function buildEditDTO(MasterProfile $profile): \App\Domain\Master\DTOs\MasterEditDTO
    {
        return \App\Domain\Master\DTOs\MasterEditDTO::fromModel($profile);
    }
    
    /**
     * Построение мета-тегов для SEO
     * 
     * @param MasterProfile $profile
     * @return array
     */
    public function buildMeta(MasterProfile $profile): array
    {
        return [
            'title' => $profile->meta_title,
            'description' => $profile->meta_description,
            'keywords' => $this->buildKeywords($profile),
            'og:title' => $profile->meta_title,
            'og:description' => $profile->meta_description,
            'og:image' => $profile->avatar_url ?? asset('images/default-master.jpg'),
            'og:url' => $profile->url,
            'og:type' => 'profile'
        ];
    }
    
    /**
     * Построение упрощенного DTO для списков
     * 
     * @param MasterProfile $profile
     * @return \App\Domain\Master\DTOs\MasterListItemDTO
     */
    public function buildListItemDTO(MasterProfile $profile): \App\Domain\Master\DTOs\MasterListItemDTO
    {
        return \App\Domain\Master\DTOs\MasterListItemDTO::fromModel($profile);
    }
    
    /**
     * Расчет диапазона цен
     * 
     * @param MasterProfile $profile
     * @return array
     */
    private function calculatePriceRange(MasterProfile $profile): array
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return ['min' => 0, 'max' => 0];
        }
        
        return [
            'min' => $profile->services->min('price') ?? 0,
            'max' => $profile->services->max('price') ?? 0
        ];
    }
    
    /**
     * Построение списка услуг
     * 
     * @param MasterProfile $profile
     * @return Collection
     */
    private function buildServices(MasterProfile $profile): Collection
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return collect([]);
        }
        
        return $profile->services->map(fn($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'category' => $service->category->name ?? 'Массаж',
            'price' => $service->price,
            'duration' => $service->duration,
            'description' => $service->description
        ]);
    }
    
    /**
     * Построение рабочих зон
     * 
     * @param MasterProfile $profile
     * @return Collection
     */
    private function buildWorkZones(MasterProfile $profile): Collection
    {
        if (!$profile->workZones || $profile->workZones->isEmpty()) {
            return collect([]);
        }
        
        return $profile->workZones->map(fn($zone) => [
            'id' => $zone->id,
            'district' => $zone->district,
            'city' => $zone->city ?? $profile->city,
            'is_active' => $zone->is_active ?? true
        ]);
    }
    
    /**
     * Построение расписания
     * 
     * @param MasterProfile $profile
     * @return Collection
     */
    private function buildSchedules(MasterProfile $profile): Collection
    {
        if (!$profile->schedules || $profile->schedules->isEmpty()) {
            return collect([]);
        }
        
        return $profile->schedules->map(fn($schedule) => [
            'id' => $schedule->id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'is_working_day' => $schedule->is_working_day ?? true
        ]);
    }
    
    /**
     * Построение отзывов
     * 
     * @param MasterProfile $profile
     * @param int $limit
     * @return Collection
     */
    private function buildReviews(MasterProfile $profile, int $limit = 5): Collection
    {
        if (!$profile->reviews || $profile->reviews->isEmpty()) {
            return collect([]);
        }
        
        return $profile->reviews->take($limit)->map(fn($review) => [
            'id' => $review->id,
            'rating' => $review->rating_overall ?? $review->rating,
            'comment' => $review->comment,
            'client_name' => $review->user->name ?? 'Анонимный клиент',
            'created_at' => $review->created_at
        ]);
    }
    
    /**
     * Построение данных о видео
     * 
     * @param MasterProfile $profile
     * @return array|null
     */
    private function buildVideoData(MasterProfile $profile): ?array
    {
        if (!$profile->video) {
            return null;
        }
        
        return [
            'id' => $profile->video->id,
            'filename' => $profile->video->filename,
            'video_url' => $profile->video->video_url,
            'poster_url' => $profile->video->poster_url,
            'duration' => $profile->video->formatted_duration,
            'file_size' => $profile->video->file_size
        ];
    }
    
    /**
     * Построение ключевых слов для SEO
     * 
     * @param MasterProfile $profile
     * @return string
     */
    private function buildKeywords(MasterProfile $profile): string
    {
        $keywords = [
            $profile->display_name,
            'массаж',
            'массажист',
            $profile->city,
            $profile->district
        ];
        
        // Добавляем основные услуги
        $mainServices = $this->getMainServices($profile, 3);
        foreach ($mainServices as $service) {
            $keywords[] = strtolower($service);
        }
        
        return implode(', ', array_filter($keywords));
    }
    
    /**
     * Получение основных услуг
     * 
     * @param MasterProfile $profile
     * @param int $limit
     * @return array
     */
    private function getMainServices(MasterProfile $profile, int $limit = 3): array
    {
        if (!$profile->services || $profile->services->isEmpty()) {
            return ['Классический массаж'];
        }
        
        return $profile->services
            ->take($limit)
            ->pluck('name')
            ->toArray();
    }
}