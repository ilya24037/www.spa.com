<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\AdPricing;
use App\Domain\Ad\Models\AdMedia;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления объявлениями
 */
class AdService
{
    private AdRepository $adRepository;

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Создать новое объявление
     */
    public function create(array $data, User $user): Ad
    {
        return DB::transaction(function () use ($data, $user) {
            // Создаем основное объявление
            $adData = $this->prepareMainAdData($data);
            $adData['user_id'] = $user->id;
            $adData['status'] = 'draft'; // По умолчанию черновик
            
            $ad = Ad::create($adData);
            
            // Создаем связанные компоненты
            $this->createAdComponents($ad, $data);
            
            Log::info('Ad created with components', ['ad_id' => $ad->id, 'user_id' => $user->id]);
            
            return $ad;
        });
    }
    
    /**
     * Создать черновик объявления
     */
    public function createDraft(array $data, User $user): Ad
    {
        return DB::transaction(function () use ($data, $user) {
            // Создаем основное объявление в статусе черновика
            $adData = $this->prepareMainAdData($data);
            $adData['user_id'] = $user->id;
            $adData['status'] = 'draft';
            
            // Устанавливаем пустые значения для обязательных полей
            $adData['title'] = $adData['title'] ?? '';
            $adData['specialty'] = $adData['specialty'] ?? '';
            $adData['description'] = $adData['description'] ?? '';
            
            $ad = Ad::create($adData);
            
            // Создаем связанные компоненты, если есть данные
            if (!empty($data)) {
                $this->createAdComponents($ad, $data);
            }
            
            Log::info('Draft ad created', ['ad_id' => $ad->id, 'user_id' => $user->id]);
            
            return $ad;
        });
    }
    
    /**
     * Обновить объявление
     */
    public function update(Ad $ad, array $data): Ad
    {
        return DB::transaction(function () use ($ad, $data) {
            // Подготавливаем данные
            $preparedData = $this->prepareMainAdData($data);
            
            // Обновляем объявление
            $updated = $this->adRepository->update($ad, $preparedData);
            
            // Обновляем связанные компоненты
            $this->updateAdComponents($ad, $data);
            
            Log::info('Ad updated', ['ad_id' => $ad->id]);
            
            return $updated;
        });
    }
    
    /**
     * Опубликовать объявление
     */
    public function publish(Ad $ad): Ad
    {
        return DB::transaction(function () use ($ad) {
            // Проверяем готовность к публикации
            if (!$this->canBePublished($ad)) {
                throw new \InvalidArgumentException('Объявление не готово к публикации');
            }
            
            $published = $this->adRepository->update($ad, [
                'status' => 'waiting_payment',
                'published_at' => now()
            ]);
            
            Log::info('Ad published', ['ad_id' => $ad->id]);
            
            return $published;
        });
    }
    
    /**
     * Сохранить как черновик
     */
    public function saveDraft(array $data, User $user, ?Ad $ad = null): Ad
    {
        if ($ad) {
            // Обновляем существующий черновик
            return $this->update($ad, $data);
        } else {
            // Создаем новый черновик
            return $this->create($data, $user);
        }
    }
    
    /**
     * Архивировать объявление
     */
    public function archive(Ad $ad): Ad
    {
        $archived = $this->adRepository->update($ad, ['status' => 'archived']);
        
        Log::info('Ad archived', ['ad_id' => $ad->id]);
        
        return $archived;
    }
    
    /**
     * Восстановить объявление из архива
     */
    public function restore(Ad $ad): Ad
    {
        $restored = $this->adRepository->update($ad, ['status' => 'draft']);
        
        Log::info('Ad restored', ['ad_id' => $ad->id]);
        
        return $restored;
    }
    
    /**
     * Удалить объявление
     */
    public function delete(Ad $ad): bool
    {
        return DB::transaction(function () use ($ad) {
            $adId = $ad->id;
            
            // Удаляем связанные компоненты
            $this->deleteAdComponents($ad);
            
            // Удаляем само объявление
            $deleted = $this->adRepository->delete($ad);
            
            if ($deleted) {
                Log::info('Ad deleted', ['ad_id' => $adId]);
            }
            
            return $deleted;
        });
    }
    
    /**
     * Подготовить основные данные объявления для сохранения
     */
    private function prepareMainAdData(array $data): array
    {
        $prepared = [];
        
        // Основные поля объявления
        $mainFields = [
            'title', 'specialty', 'description', 'category', 'taxi_option', 'work_format', 'experience', 'education_level',
            'address', 'travel_area', 'phone', 'contact_method', 'whatsapp', 'telegram',
            'age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color',
            'appearance', 'nationality', 'has_girlfriend', 'schedule_notes'
        ];
        
        foreach ($mainFields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }
        
        // JSON поля в основной таблице
        $jsonFields = [
            'clients', 'service_location', 'outcall_locations', 'service_provider', 'features', 'services',
            'schedule' // Добавлено поле расписания
        ];
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = is_array($data[$field]) 
                    ? json_encode($data[$field]) 
                    : $data[$field];
            }
        }
        
        // Булевы поля
        if (isset($data['has_girlfriend'])) {
            $prepared['has_girlfriend'] = (bool) $data['has_girlfriend'];
        }
        
        return $prepared;
    }
    
    /**
     * Создать компоненты объявления
     */
    private function createAdComponents(Ad $ad, array $data): void
    {
        // Создаем контент - закомментировано, т.к. контент хранится в основной таблице
        // $this->createAdContent($ad, $data);
        
        // Создаем цены
        $this->createAdPricing($ad, $data);
        
        // Создаем расписание - закомментировано, т.к. расписание хранится в основной таблице
        // $this->createAdSchedule($ad, $data);
        
        // Создаем медиа
        $this->createAdMedia($ad, $data);
    }
    
    /**
     * Обновить компоненты объявления
     */
    private function updateAdComponents(Ad $ad, array $data): void
    {
        // Обновляем контент - закомментировано, т.к. контент хранится в основной таблице
        // if ($ad->content) {
        //     $this->updateAdContent($ad->content, $data);
        // } else {
        //     $this->createAdContent($ad, $data);
        // }
        
        // Обновляем цены
        if ($ad->pricing) {
            $this->updateAdPricing($ad->pricing, $data);
        } else {
            $this->createAdPricing($ad, $data);
        }
        
        // Обновляем расписание - закомментировано, т.к. расписание хранится в основной таблице
        // if ($ad->schedule) {
        //     $this->updateAdSchedule($ad->schedule, $data);
        // } else {
        //     $this->createAdSchedule($ad, $data);
        // }
        
        // Обновляем медиа
        if ($ad->media) {
            $this->updateAdMedia($ad->media, $data);
        } else {
            $this->createAdMedia($ad, $data);
        }
    }
    
    /**
     * Создать контент объявления
     */
    private function createAdContent(Ad $ad, array $data): void
    {
        $contentData = [];
        
        $contentFields = ['title', 'description', 'specialty', 'additional_features', 'services_additional_info', 'schedule_notes'];
        
        foreach ($contentFields as $field) {
            if (isset($data[$field])) {
                $contentData[$field] = $data[$field];
            }
        }
        
        if (!empty($contentData)) {
            $contentData['ad_id'] = $ad->id;
            AdContent::create($contentData);
        }
    }
    
    /**
     * Обновить контент объявления
     */
    private function updateAdContent(AdContent $content, array $data): void
    {
        $contentData = [];
        
        $contentFields = ['title', 'description', 'specialty', 'additional_features', 'services_additional_info', 'schedule_notes'];
        
        foreach ($contentFields as $field) {
            if (isset($data[$field])) {
                $contentData[$field] = $data[$field];
            }
        }
        
        if (!empty($contentData)) {
            $content->update($contentData);
        }
    }
    
    /**
     * Создать цены объявления
     */
    private function createAdPricing(Ad $ad, array $data): void
    {
        $pricingData = [];
        
        $pricingFields = ['price', 'price_unit', 'contacts_per_hour', 'discount', 'new_client_discount', 'gift', 'pricing_data'];
        
        foreach ($pricingFields as $field) {
            if (isset($data[$field])) {
                $pricingData[$field] = $data[$field];
            }
        }
        
        // Обработка is_starting_price
        if (isset($data['is_starting_price'])) {
            $pricingData['is_starting_price'] = is_array($data['is_starting_price']) 
                ? !empty($data['is_starting_price'])
                : (bool) $data['is_starting_price'];
        }
        
        if (!empty($pricingData)) {
            $pricingData['ad_id'] = $ad->id;
            AdPricing::create($pricingData);
        }
    }
    
    /**
     * Обновить цены объявления
     */
    private function updateAdPricing(AdPricing $pricing, array $data): void
    {
        $pricingData = [];
        
        $pricingFields = ['price', 'price_unit', 'contacts_per_hour', 'discount', 'new_client_discount', 'gift', 'pricing_data'];
        
        foreach ($pricingFields as $field) {
            if (isset($data[$field])) {
                $pricingData[$field] = $data[$field];
            }
        }
        
        // Обработка is_starting_price
        if (isset($data['is_starting_price'])) {
            $pricingData['is_starting_price'] = is_array($data['is_starting_price']) 
                ? !empty($data['is_starting_price'])
                : (bool) $data['is_starting_price'];
        }
        
        if (!empty($pricingData)) {
            $pricing->update($pricingData);
        }
    }
    
    /**
     * Создать расписание объявления
     */
    private function createAdSchedule(Ad $ad, array $data): void
    {
        $scheduleData = [];
        
        $scheduleFields = ['schedule', 'schedule_notes', 'working_days', 'working_hours'];
        
        foreach ($scheduleFields as $field) {
            if (isset($data[$field])) {
                $scheduleData[$field] = $data[$field];
            }
        }
        
        if (!empty($scheduleData)) {
            $scheduleData['ad_id'] = $ad->id;
            AdSchedule::create($scheduleData);
        }
    }
    
    /**
     * Обновить расписание объявления
     */
    private function updateAdSchedule(AdSchedule $schedule, array $data): void
    {
        $scheduleData = [];
        
        $scheduleFields = ['schedule', 'schedule_notes', 'working_days', 'working_hours'];
        
        foreach ($scheduleFields as $field) {
            if (isset($data[$field])) {
                $scheduleData[$field] = $data[$field];
            }
        }
        
        if (!empty($scheduleData)) {
            $schedule->update($scheduleData);
        }
    }
    
    /**
     * Создать медиа объявления
     */
    private function createAdMedia(Ad $ad, array $data): void
    {
        $mediaData = [];
        
        $mediaFields = ['photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos'];
        
        foreach ($mediaFields as $field) {
            if (isset($data[$field])) {
                $mediaData[$field] = $data[$field];
            }
        }
        
        if (!empty($mediaData)) {
            $mediaData['ad_id'] = $ad->id;
            AdMedia::create($mediaData);
        }
    }
    
    /**
     * Обновить медиа объявления
     */
    private function updateAdMedia(AdMedia $media, array $data): void
    {
        $mediaData = [];
        
        $mediaFields = ['photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos'];
        
        foreach ($mediaFields as $field) {
            if (isset($data[$field])) {
                $mediaData[$field] = $data[$field];
            }
        }
        
        if (!empty($mediaData)) {
            $media->update($mediaData);
        }
    }
    
    /**
     * Удалить компоненты объявления
     */
    private function deleteAdComponents(Ad $ad): void
    {
        $ad->content()->delete();
        $ad->pricing()->delete();
        $ad->schedule()->delete();
        $ad->media()->delete();
    }
    
    /**
     * Проверить, может ли объявление быть опубликовано
     */
    private function canBePublished(Ad $ad): bool
    {
        // Используем новый метод из модели
        return $ad->canBePublished();
    }
    
    /**
     * Получить статистику объявлений пользователя
     */
    public function getUserAdStats(User $user): array
    {
        return $this->adRepository->getUserStats($user->id);
    }
    
    /**
     * Найти активные объявления
     */
    public function findActive(int $limit = 20): array
    {
        return $this->adRepository->findActive($limit);
    }
    
    /**
     * Найти объявления по фильтрам
     */
    public function findByFilters(array $filters): array
    {
        return $this->adRepository->findByFilters($filters);
    }
}