<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdContent;
use App\Models\AdPricing;
use App\Models\AdSchedule;
use App\Models\AdMedia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления объявлениями
 */
class AdService
{
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
     * Обновить объявление
     */
    public function update(Ad $ad, array $data): Ad
    {
        return DB::transaction(function () use ($ad, $data) {
            // Подготавливаем данные
            $preparedData = $this->prepareAdData($data);
            
            // Обновляем объявление
            $ad->update($preparedData);
            
            Log::info('Ad updated', ['ad_id' => $ad->id]);
            
            return $ad->fresh();
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
            
            $ad->update([
                'status' => 'waiting_payment',
                'published_at' => now()
            ]);
            
            Log::info('Ad published', ['ad_id' => $ad->id]);
            
            return $ad->fresh();
        });
    }
    
    /**
     * Сохранить как черновик
     */
    public function saveDraft(array $data, User $user, Ad $ad = null): Ad
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
        $ad->update(['status' => 'archived']);
        
        Log::info('Ad archived', ['ad_id' => $ad->id]);
        
        return $ad->fresh();
    }
    
    /**
     * Восстановить объявление из архива
     */
    public function restore(Ad $ad): Ad
    {
        $ad->update(['status' => 'draft']);
        
        Log::info('Ad restored', ['ad_id' => $ad->id]);
        
        return $ad->fresh();
    }
    
    /**
     * Удалить объявление
     */
    public function delete(Ad $ad): bool
    {
        return DB::transaction(function () use ($ad) {
            $adId = $ad->id;
            
            // Можно добавить здесь удаление связанных файлов
            // $this->deleteAssociatedFiles($ad);
            
            $deleted = $ad->delete();
            
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
            'category', 'taxi_option', 'work_format', 'experience', 'education_level',
            'address', 'travel_area', 'phone', 'contact_method', 'whatsapp', 'telegram',
            'age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color',
            'appearance', 'nationality', 'has_girlfriend'
        ];
        
        foreach ($mainFields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }
        
        // JSON поля в основной таблице
        $jsonFields = [
            'clients', 'service_location', 'outcall_locations', 'service_provider', 'features', 'services'
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
        // Создаем контент
        $this->createAdContent($ad, $data);
        
        // Создаем цены
        $this->createAdPricing($ad, $data);
        
        // Создаем расписание
        $this->createAdSchedule($ad, $data);
        
        // Создаем медиа
        $this->createAdMedia($ad, $data);
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
        $ads = Ad::where('user_id', $user->id);
        
        return [
            'total' => $ads->count(),
            'active' => $ads->where('status', 'active')->count(),
            'drafts' => $ads->where('status', 'draft')->count(),
            'waiting_payment' => $ads->where('status', 'waiting_payment')->count(),
            'archived' => $ads->where('status', 'archived')->count(),
        ];
    }
}