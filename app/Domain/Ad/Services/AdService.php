<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Services\AdMediaService;
use App\Domain\Ad\Services\AdValidationService;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Domain\User\Models\User;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Основной сервис для управления объявлениями
 * Содержит только CRUD операции - остальная логика вынесена в отдельные сервисы
 */
class AdService
{
    private AdRepository $adRepository;
    private AdMediaService $adMediaService;
    private AdValidationService $validationService;

    public function __construct(
        AdRepository $adRepository,
        AdMediaService $adMediaService,
        AdValidationService $validationService
    ) {
        $this->adRepository = $adRepository;
        $this->adMediaService = $adMediaService;
        $this->validationService = $validationService;
    }

    /**
     * Создать новое объявление из DTO
     */
    public function createFromDTO(CreateAdDTO $dto): Ad
    {
        $data = $dto->toArray();
        
        return DB::transaction(function () use ($data) {
            $adData = $this->prepareMainAdData($data);
            $adData['user_id'] = $data['user_id'];
            $adData['status'] = AdStatus::DRAFT->value;
            
            $ad = $this->adRepository->create($adData);
            $this->createAdComponents($ad, $data);
            
            Log::info('Ad created with components', ['ad_id' => $ad->id, 'user_id' => $data['user_id']]);
            return $ad;
        });
    }

    /**
     * Создать объявление
     */
    public function create(array $data, User $user): Ad
    {
        $data['user_id'] = $user->id;
        $this->validationService->validateCreateData($data, $user);
        
        return DB::transaction(function () use ($data) {
            $adData = $this->prepareMainAdData($data);
            $adData['status'] = AdStatus::DRAFT->value;
            
            $ad = $this->adRepository->create($adData);
            $this->createAdComponents($ad, $data);
            
            return $ad;
        });
    }

    /**
     * Создать черновик объявления
     */
    public function createDraft(array $data, User $user): Ad
    {
        $data['user_id'] = $user->id;
        
        return DB::transaction(function () use ($data) {
            $adData = $this->prepareMainAdData($data);
            $adData['status'] = AdStatus::DRAFT->value;
            
            $ad = $this->adRepository->create($adData);
            $this->createAdComponents($ad, $data);
            
            return $ad;
        });
    }

    /**
     * Обновить объявление
     */
    public function update(Ad $ad, array $data): Ad
    {
        $this->validationService->validateUpdateData($ad, $data, $ad->user);
        
        return DB::transaction(function () use ($ad, $data) {
            $adData = $this->prepareMainAdData($data);
            $ad = $this->adRepository->update($ad, $adData);
            $this->updateAdComponents($ad, $data);
            
            return $ad;
        });
    }

    /**
     * Публиковать объявление
     */
    public function publish(Ad $ad): Ad
    {
        $this->validationService->validateForPublishing($ad);
        
        $ad = $this->adRepository->update($ad, [
            'status' => AdStatus::ACTIVE->value,
            'published_at' => now()
        ]);
        
        Log::info('Ad published', ['ad_id' => $ad->id]);
        return $ad;
    }

    /**
     * Обновить черновик
     */
    public function updateDraft(Ad $ad, array $data): Ad
    {
        return DB::transaction(function () use ($ad, $data) {
            $adData = $this->prepareMainAdData($data);
            $ad = $this->adRepository->update($ad, $adData);
            $this->updateAdComponents($ad, $data);
            
            return $ad;
        });
    }

    /**
     * Архивировать объявление
     */
    public function archive(Ad $ad): Ad
    {
        return $this->adRepository->update($ad, [
            'status' => AdStatus::ARCHIVED->value,
            'archived_at' => now()
        ]);
    }

    /**
     * Восстановить объявление
     */
    public function restore(Ad $ad): Ad
    {
        return $this->adRepository->update($ad, [
            'status' => AdStatus::ACTIVE->value,
            'archived_at' => null
        ]);
    }

    /**
     * Удалить объявление
     */
    public function delete(Ad $ad): bool
    {
        return DB::transaction(function () use ($ad) {
            $this->deleteAdComponents($ad);
            return $this->adRepository->delete($ad);
        });
    }

    /**
     * Подготовить основные данные объявления
     */
    private function prepareMainAdData(array $data): array
    {
        $adData = [];
        
        // Основные поля
        $mainFields = [
            'title', 'description', 'category_id', 'subcategory_id',
            'price_from', 'price_to', 'price_fixed', 'price_currency',
            'service_location', 'address', 'latitude', 'longitude',
            'photos', 'video', 'working_days', 'working_hours',
            'metro_stations', 'phone', 'telegram', 'whatsapp'
        ];
        
        foreach ($mainFields as $field) {
            if (isset($data[$field])) {
                $adData[$field] = $data[$field];
            }
        }
        
        return $adData;
    }

    /**
     * Создать связанные компоненты объявления
     */
    private function createAdComponents(Ad $ad, array $data): void
    {
        // Все компоненты теперь в основной таблице ads
        // Этот метод оставлен для совместимости
    }

    /**
     * Обновить связанные компоненты объявления
     */
    private function updateAdComponents(Ad $ad, array $data): void
    {
        // Все компоненты теперь в основной таблице ads
        // Этот метод оставлен для совместимости
    }

    /**
     * Удалить компоненты объявления
     */
    private function deleteAdComponents(Ad $ad): void
    {
        // Если есть связанные таблицы, удаляем их здесь
        // Пока все хранится в основной таблице ads
    }
}