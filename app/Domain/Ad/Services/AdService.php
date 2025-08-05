<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\AdPricing;
// use App\Domain\Ad\Models\AdMedia; // Не используется - медиа хранится в основной таблице
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Services\AdMediaService;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Domain\User\Models\User;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления объявлениями
 */
class AdService
{
    private AdRepository $adRepository;
    private AdMediaService $adMediaService;

    public function __construct(
        AdRepository $adRepository,
        AdMediaService $adMediaService
    ) {
        $this->adRepository = $adRepository;
        $this->adMediaService = $adMediaService;
    }

    /**
     * Создать новое объявление из DTO
     */
    public function createFromDTO(CreateAdDTO $dto): Ad
    {
        $data = $dto->toArray();
        
        return DB::transaction(function () use ($data) {
            // Создаем основное объявление
            $adData = $this->prepareMainAdData($data);
            $adData['user_id'] = $data['user_id'];
            $adData['status'] = AdStatus::DRAFT->value; // По умолчанию черновик
            
            $ad = $this->adRepository->create($adData);
            
            // Создаем связанные компоненты
            $this->createAdComponents($ad, $data);
            
            Log::info('Ad created with components', ['ad_id' => $ad->id, 'user_id' => $data['user_id']]);
            
            return $ad;
        });
    }

    /**
     * Создать новое объявление (legacy метод для обратной совместимости)
     */
    public function create(array $data, User $user): Ad
    {
        return DB::transaction(function () use ($data, $user) {
            // Создаем основное объявление
            $adData = $this->prepareMainAdData($data);
            $adData['user_id'] = $user->id;
            $adData['status'] = AdStatus::DRAFT->value; // По умолчанию черновик
            
            $ad = $this->adRepository->create($adData);
            
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
            $adData['status'] = AdStatus::DRAFT->value;
            
            // Устанавливаем пустые значения для обязательных полей
            $adData['title'] = $adData['title'] ?? '';
            $adData['specialty'] = $adData['specialty'] ?? '';
            $adData['description'] = $adData['description'] ?? '';
            
            $ad = $this->adRepository->create($adData);
            
            // Создаем связанные компоненты, если есть данные
            if (!empty($data)) {
                $this->createAdComponents($ad, $data);
            }
            
            Log::info('Draft ad created', [
                'ad_id' => $ad->id, 
                'user_id' => $user->id,
                'photos_saved' => $ad->photos,
                'photos_count' => is_array($ad->photos) ? count($ad->photos) : 0
            ]);
            
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
            $updated = $this->adRepository->updateAd($ad, $preparedData);
            
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
            
            $published = $this->adRepository->updateAd($ad, [
                'status' => AdStatus::WAITING_PAYMENT->value,
                'published_at' => now()
            ]);
            
            Log::info('Ad published', ['ad_id' => $ad->id]);
            
            return $published;
        });
    }
    
    /**
     * Обновить черновик объявления
     */
    public function updateDraft(Ad $ad, array $data): Ad
    {
        return DB::transaction(function () use ($ad, $data) {
            // Подготавливаем данные
            $preparedData = $this->prepareMainAdData($data);
            
            // Сохраняем статус черновика
            $preparedData['status'] = AdStatus::DRAFT->value;
            
            // Обновляем объявление
            $updated = $this->adRepository->updateAd($ad, $preparedData);
            
            // Обновляем связанные компоненты
            $this->updateAdComponents($ad, $data);
            
            Log::info('Draft ad updated', ['ad_id' => $ad->id]);
            
            return $updated;
        });
    }

    /**
     * Сохранить как черновик
     */
    public function saveDraft(array $data, User $user, ?Ad $ad = null): Ad
    {
        if ($ad) {
            // Обновляем существующий черновик
            return $this->updateDraft($ad, $data);
        } else {
            // Создаем новый черновик
            return $this->createDraft($data, $user);
        }
    }
    
    /**
     * Архивировать объявление
     */
    public function archive(Ad $ad): Ad
    {
        $archived = $this->adRepository->updateAd($ad, ['status' => AdStatus::ARCHIVED->value]);
        
        Log::info('Ad archived', ['ad_id' => $ad->id]);
        
        return $archived;
    }
    
    /**
     * Модерировать объявление
     */
    public function moderate(Ad $ad, bool $approved, ?string $reason = null): Ad
    {
        return DB::transaction(function () use ($ad, $approved, $reason) {
            // Проверяем, что объявление в состоянии ожидания модерации
            if ($ad->status !== AdStatus::WAITING_PAYMENT->value) {
                throw new \InvalidArgumentException('Модерировать можно только объявления в статусе "ждет оплаты"');
            }
            
            $updateData = [
                'moderated_at' => now(),
                'moderation_reason' => $reason
            ];
            
            if ($approved) {
                $updateData['status'] = AdStatus::ACTIVE->value;
                $updateData['expires_at'] = now()->addDays(30);
                
                Log::info('Ad approved during moderation', ['ad_id' => $ad->id]);
            } else {
                $updateData['status'] = AdStatus::REJECTED->value;
                
                Log::info('Ad rejected during moderation', [
                    'ad_id' => $ad->id, 
                    'reason' => $reason
                ]);
            }
            
            return $this->adRepository->updateAd($ad, $updateData);
        });
    }
    
    /**
     * Восстановить объявление из архива
     */
    public function restore(Ad $ad): Ad
    {
        $restored = $this->adRepository->updateAd($ad, ['status' => AdStatus::DRAFT->value]);
        
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
            'appearance', 'nationality', 'has_girlfriend', 'schedule_notes',
            // Поля цены
            'price', 'price_unit', 'price_per_hour', 'outcall_price', 'express_price', 
            'price_two_hours', 'price_night', 'min_duration', 'contacts_per_hour',
            'discount', 'new_client_discount', 'gift', 'additional_features'
        ];
        
        foreach ($mainFields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = $data[$field];
            }
        }
        

        
        // JSON поля в основной таблице - теперь обрабатываются через JsonFieldsTrait
        $jsonFields = [
            'clients', 'service_location', 'outcall_locations', 'service_provider', 'features', 'services',
            'schedule', 'geo', 'photos', 'video'
        ];
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                // JsonFieldsTrait автоматически обработает массивы при сохранении
                $prepared[$field] = $data[$field];
            }
        }
        
        // Булевы поля
        $booleanFields = ['has_girlfriend', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $prepared[$field] = (bool) $data[$field];
            }
        }
        
        return $prepared;
    }
    
    /**
     * Создать компоненты объявления
     */
    private function createAdComponents(Ad $ad, array $data): void
    {
        // Создаем контент в отдельной таблице AdContent
        $this->createAdContent($ad, $data);
        
        // Создаем цены в отдельной таблице AdPricing
        $this->createAdPricing($ad, $data);
        
        // Создаем расписание в отдельной таблице AdSchedule
        $this->createAdSchedule($ad, $data);
        
        // Создаем локацию в отдельной таблице AdLocation
        $this->createAdLocation($ad, $data);
        
        // Медиа обрабатываем через AdMediaService
        if (isset($data['photos']) && is_array($data['photos'])) {
            $this->adMediaService->syncPhotos($ad, $data['photos']);
        }
        
        Log::info('Ad components created', ['ad_id' => $ad->id]);
    }
    
    /**
     * Обновить компоненты объявления
     */
    private function updateAdComponents(Ad $ad, array $data): void
    {
        // Обновляем контент в отдельной таблице AdContent
        if ($ad->content) {
            $this->updateAdContent($ad->content, $data);
        } else {
            $this->createAdContent($ad, $data);
        }
        
        // Обновляем цены в отдельной таблице AdPricing
        if ($ad->pricing) {
            $this->updateAdPricing($ad->pricing, $data);
        } else {
            $this->createAdPricing($ad, $data);
        }
        
        // Обновляем расписание в отдельной таблице AdSchedule
        if ($ad->schedule) {
            $this->updateAdSchedule($ad->schedule, $data);
        } else {
            $this->createAdSchedule($ad, $data);
        }
        
        // Обновляем локацию в отдельной таблице AdLocation
        if ($ad->location) {
            $this->updateAdLocation($ad->location, $data);
        } else {
            $this->createAdLocation($ad, $data);
        }
        
        // Медиа обрабатываем через AdMediaService
        if (isset($data['photos']) && is_array($data['photos'])) {
            $this->adMediaService->syncPhotos($ad, $data['photos']);
        }
        
        Log::info('Ad components updated', ['ad_id' => $ad->id]);
    }
    
    /**
     * Добавить услугу к объявлению используя JsonFieldsTrait
     */
    public function addService(Ad $ad, string $service): Ad
    {
        $ad->appendToJsonField('services', $service);
        $ad->save();
        
        Log::info('Service added to ad', ['ad_id' => $ad->id, 'service' => $service]);
        return $ad;
    }
    
    /**
     * Удалить услугу из объявления используя JsonFieldsTrait
     */
    public function removeService(Ad $ad, string $service): Ad
    {
        $ad->removeFromJsonField('services', $service);
        $ad->save();
        
        Log::info('Service removed from ad', ['ad_id' => $ad->id, 'service' => $service]);
        return $ad;
    }
    
    /**
     * Установить геолокацию объявления используя JsonFieldsTrait
     */
    public function setLocation(Ad $ad, float $lat, float $lng, ?string $address = null): Ad
    {
        $ad->setJsonFieldKey('geo', 'lat', $lat);
        $ad->setJsonFieldKey('geo', 'lng', $lng);
        
        if ($address) {
            $ad->setJsonFieldKey('geo', 'address', $address);
        }
        
        $ad->save();
        
        Log::info('Location updated for ad', ['ad_id' => $ad->id, 'lat' => $lat, 'lng' => $lng]);
        return $ad;
    }
    
    /**
     * Добавить фотографию к объявлению используя JsonFieldsTrait
     */
    public function addPhoto(Ad $ad, array $photoData): Ad
    {
        // Проверяем структуру фото
        if (!isset($photoData['url']) || !isset($photoData['thumbnail'])) {
            throw new \InvalidArgumentException('Photo data must contain url and thumbnail');
        }
        
        $ad->appendToJsonField('photos', $photoData);
        $ad->save();
        
        Log::info('Photo added to ad', ['ad_id' => $ad->id, 'photo_url' => $photoData['url']]);
        return $ad;
    }
    
    /**
     * Обновить расписание объявления используя JsonFieldsTrait
     */
    public function updateSchedule(Ad $ad, array $scheduleData): Ad
    {
        $ad->mergeJsonField('schedule', $scheduleData);
        $ad->save();
        
        Log::info('Schedule updated for ad', ['ad_id' => $ad->id]);
        return $ad;
    }
    
    /**
     * Получить количество услуг в объявлении
     */
    public function getServicesCount(Ad $ad): int
    {
        $services = $ad->getJsonField('services', []);
        return count($services);
    }
    
    /**
     * Проверить наличие услуги в объявлении
     */
    public function hasService(Ad $ad, string $service): bool
    {
        return $ad->hasInJsonField('services', $service);
    }
    
    /**
     * Получить координаты объявления
     */
    public function getCoordinates(Ad $ad): ?array
    {
        $lat = $ad->getJsonFieldKey('geo', 'lat');
        $lng = $ad->getJsonFieldKey('geo', 'lng');
        
        if ($lat && $lng) {
            return ['lat' => $lat, 'lng' => $lng];
        }
        
        return null;
    }
    
    /**
     * Очистить все фотографии объявления
     */
    public function clearPhotos(Ad $ad): Ad
    {
        $ad->clearJsonField('photos');
        $ad->save();
        
        Log::info('Photos cleared for ad', ['ad_id' => $ad->id]);
        return $ad;
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
    
    // /**
    //  * Создать медиа объявления - УСТАРЕЛО
    //  * Медиа теперь хранится в основной таблице ads
    //  */
    // private function createAdMedia(Ad $ad, array $data): void
    // {
    //     $mediaData = [];
    //     
    //     $mediaFields = ['photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos'];
    //     
    //     foreach ($mediaFields as $field) {
    //         if (isset($data[$field])) {
    //             $mediaData[$field] = $data[$field];
    //         }
    //     }
    //     
    //     if (!empty($mediaData)) {
    //         $mediaData['ad_id'] = $ad->id;
    //         AdMedia::create($mediaData);
    //     }
    // }
    
    // /**
    //  * Обновить медиа объявления - УСТАРЕЛО
    //  * Медиа теперь хранится в основной таблице ads
    //  */
    // private function updateAdMedia(AdMedia $media, array $data): void
    // {
    //     $mediaData = [];
    //     
    //     $mediaFields = ['photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos'];
    //     
    //     foreach ($mediaFields as $field) {
    //         if (isset($data[$field])) {
    //             $mediaData[$field] = $data[$field];
    //         }
    //     }
    //     
    //     if (!empty($mediaData)) {
    //         $media->update($mediaData);
    //     }
    // }
    
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

    /**
     * Найти существующий черновик пользователя
     */
    public function findExistingDraft(int $userId, ?int $adId = null): ?Ad
    {
        if (!$adId) {
            return null;
        }
        
        return $this->adRepository->findUserDraft($userId, $adId);
    }

    /**
     * Удалить черновик
     */
    public function deleteDraft(Ad $ad): bool
    {
        // Проверяем, что это черновик
        if ($ad->status !== AdStatus::DRAFT->value) {
            throw new \InvalidArgumentException('Можно удалять только черновики');
        }
        
        return $this->delete($ad);
    }

    /**
     * Проверить готовность черновика к публикации
     */
    public function validateDraftForPublication(Ad $ad): array
    {
        if ($ad->status !== AdStatus::DRAFT->value) {
            throw new \InvalidArgumentException('Опубликовать можно только черновик');
        }
        
        if (!$ad->canBePublished()) {
            return [
                'ready' => false,
                'missing_fields' => $ad->getMissingFieldsForPublication()
            ];
        }
        
        return ['ready' => true, 'missing_fields' => []];
    }

    /**
     * Подготовить данные объявления для отображения
     */
    public function prepareAdDataForView(Ad $ad): array
    {
        $adData = $ad->toArray();
        
        $jsonFields = [
            'clients', 'service_location', 'outcall_locations', 'service_provider', 
            'is_starting_price', 'photos', 'video', 'features', 'pricing_data', 
            'services', 'schedule', 'geo'
        ];
        
        foreach ($jsonFields as $field) {
            if (!isset($adData[$field]) || $adData[$field] === null) {
                // Обеспечиваем что JSON поля всегда массивы/объекты, а не null
                $adData[$field] = [];
            }
            // JsonFieldsTrait автоматически обработает кодирование/декодирование
        }
        
        // Обработка скалярных полей - обеспечиваем что они не null
        $scalarFields = [
            'price_per_hour', 'outcall_price', 'express_price', 'price_two_hours', 
            'price_night', 'min_duration', 'contacts_per_hour', 'age', 'height', 
            'weight', 'breast_size', 'hair_color', 'eye_color', 'appearance', 
            'nationality', 'work_format', 'experience', 'additional_features',
            'description', 'price', 'price_unit'
        ];
        
        foreach ($scalarFields as $field) {
            if (!isset($adData[$field]) || $adData[$field] === null) {
                $adData[$field] = '';
            }
        }
        
        // Обеспечиваем boolean поля
        $adData['has_girlfriend'] = (bool) ($adData['has_girlfriend'] ?? false);
        
        return $adData;
    }
    
    /**
     * Проверить возможность редактирования объявления
     */
    public function canEdit(Ad $ad): bool
    {
        return $ad->status === AdStatus::DRAFT->value || $ad->status === 'pending';
    }
    
    /**
     * Пометить как оплаченное и активировать
     */
    public function markAsPaid(Ad $ad): Ad
    {
        $updated = $this->adRepository->updateAd($ad, [
            'status' => AdStatus::ACTIVE->value,
            'is_paid' => true,
            'paid_at' => now(),
            'expires_at' => now()->addDays(30)
        ]);
        
        Log::info('Ad marked as paid and activated', ['ad_id' => $ad->id]);
        
        return $updated;
    }

    /**
     * Создать локацию объявления
     */
    private function createAdLocation(Ad $ad, array $data): void
    {
        $locationData = [
            'ad_id' => $ad->id,
            'work_format' => $data['work_format'] ?? null,
            'service_location' => $data['service_location'] ?? null,
            'outcall_locations' => $data['outcall_locations'] ?? null,
            'taxi_option' => $data['taxi_option'] ?? false,
            'address' => $data['address'] ?? null,
            'travel_area' => $data['travel_area'] ?? null,
            'phone' => $data['phone'] ?? null,
            'contact_method' => $data['contact_method'] ?? 'phone',
        ];

        $ad->location()->create($locationData);
    }

    /**
     * Обновить локацию объявления
     */
    private function updateAdLocation($location, array $data): void
    {
        $locationData = [];

        $locationFields = [
            'work_format', 'service_location', 'outcall_locations', 'taxi_option',
            'address', 'travel_area', 'phone', 'contact_method'
        ];
        
        foreach ($locationFields as $field) {
            if (isset($data[$field])) {
                $locationData[$field] = $data[$field];
            }
        }

        if (!empty($locationData)) {
            $location->update($locationData);
        }
    }
}