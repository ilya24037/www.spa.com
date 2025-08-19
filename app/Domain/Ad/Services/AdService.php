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
            $adData['status'] = AdStatus::DRAFT->value; // Убеждаемся что статус остается draft
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
     * Получить активные объявления для главной страницы
     * 
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getActiveAdsForHome(int $limit = 12): \Illuminate\Support\Collection
    {
        return $this->adRepository->getActiveForHome($limit);
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
            return $this->adRepository->deleteModel($ad);
        });
    }

    /**
     * Проверить, можно ли редактировать объявление
     */
    public function canEdit(Ad $ad): bool
    {
        // Редактировать можно черновики, архивные и ожидающие оплату объявления
        return in_array($ad->status, [
            AdStatus::DRAFT->value, 
            AdStatus::ARCHIVED->value, 
            AdStatus::WAITING_PAYMENT->value
        ]);
    }

    /**
     * Подготовить данные объявления для отображения
     */
    public function prepareAdDataForView(Ad $ad): array
    {
        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'description' => $ad->description,
            'category' => $ad->category,
            'specialty' => $ad->specialty,
            'clients' => $ad->clients ?? [],
            'service_location' => $ad->service_location ?? [],
            'work_format' => $ad->work_format,
            'service_provider' => $ad->service_provider,
            'experience' => $ad->experience,
            'services' => $ad->services ?? [],
            'features' => $ad->features ?? [],
            'additional_features' => $ad->additional_features,
            'schedule' => $ad->schedule ?? [],
            'schedule_notes' => $ad->schedule_notes,
            'price' => $ad->price,
            'price_unit' => $ad->price_unit,
            'is_starting_price' => $ad->is_starting_price,
            'discount' => $ad->discount,
            'new_client_discount' => $ad->new_client_discount,
            'gift' => $ad->gift,
            'height' => $ad->height,
            'weight' => $ad->weight,
            'hair_color' => $ad->hair_color,
            'eye_color' => $ad->eye_color,
            'nationality' => $ad->nationality,
            'photos' => $ad->photos ?? [],
            'videos' => $ad->videos ?? [],
            'media_settings' => $ad->media_settings ?? [],
            'geo' => $ad->geo ?? [],
            'address' => $ad->address,
            'travel_area' => $ad->travel_area,
            'custom_travel_areas' => $ad->custom_travel_areas ?? [],
            'travel_radius' => $ad->travel_radius,
            'travel_price' => $ad->travel_price,
            'travel_price_type' => $ad->travel_price_type,
            'phone' => $ad->phone,
            'contact_method' => $ad->contact_method,
            'whatsapp' => $ad->whatsapp,
            'telegram' => $ad->telegram,
            'status' => $ad->status,
            'created_at' => $ad->created_at,
            'updated_at' => $ad->updated_at,
        ];
    }

    /**
     * Подготовить основные данные объявления
     */
    private function prepareMainAdData(array $data): array
    {
        $adData = [];
        
        // Основные поля объявления которые есть в таблице ads
        // Маппинг полей из формы на поля в БД
        $fieldMapping = [
            'title' => 'title',
            'description' => 'description',
            'category' => 'category',
            'specialty' => 'specialty',
            'clients' => 'clients',
            'service_provider' => 'service_provider',
            'work_format' => 'work_format',
            'experience' => 'experience',
            'features' => 'features',
            'additional_features' => 'additional_features',
            'services' => 'services',
            'services_additional_info' => 'services_additional_info',
            'schedule' => 'schedule',
            'schedule_notes' => 'schedule_notes',
            'price' => 'price',
            'price_unit' => 'price_unit',
            'is_starting_price' => 'is_starting_price',
            'main_service_name' => 'main_service_name',
            'main_service_price' => 'main_service_price',
            'main_service_price_unit' => 'main_service_price_unit',
            'additional_services' => 'additional_services',
            'height' => 'height',
            'weight' => 'weight',
            'hair_color' => 'hair_color',
            'eye_color' => 'eye_color',
            'nationality' => 'nationality',
            'new_client_discount' => 'new_client_discount',
            'gift' => 'gift',
            'discount' => 'discount',
            'address' => 'address',
            'geo' => 'geo',
            'travel_area' => 'travel_area',
            'custom_travel_areas' => 'custom_travel_areas',
            'travel_radius' => 'travel_radius',
            'travel_price' => 'travel_price',
            'travel_price_type' => 'travel_price_type',
            'phone' => 'phone',
            'contact_method' => 'contact_method',
            'telegram' => 'telegram',
            'whatsapp' => 'whatsapp',
            'service_location' => 'service_location',
            'photos' => 'photos',
            'videos' => 'videos',
            'video' => 'video',
            'media_settings' => 'media_settings',
            'status' => 'status',
            'user_id' => 'user_id'
        ];
        
        foreach ($fieldMapping as $formField => $dbField) {
            if (isset($data[$formField])) {
                $value = $data[$formField];
                // Преобразуем массивы в JSON для хранения
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $adData[$dbField] = $value;
            }
        }
        
        // Убеждаемся что есть обязательные поля для черновика
        if (!isset($adData['title']) || empty($adData['title'])) {
            $adData['title'] = 'Черновик объявления';
        }
        
        if (!isset($adData['category']) || empty($adData['category'])) {
            $adData['category'] = 'erotic';
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

    /**
     * Восстановить из архива
     */
    public function restoreFromArchive(Ad $ad): Ad
    {
        return $this->restore($ad);
    }

    /**
     * Деактивировать объявление (перевести в архив)
     */
    public function deactivate(Ad $ad): Ad
    {
        return $this->archive($ad);
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(Ad $ad): void
    {
        $ad->increment('views_count');
    }

    /**
     * Получить похожие объявления
     */
    public function getSimilarAds(Ad $ad, int $limit = 4): \Illuminate\Support\Collection
    {
        return Ad::where('category', $ad->category)
            ->where('id', '!=', $ad->id)
            ->where('status', AdStatus::ACTIVE->value)
            ->limit($limit)
            ->get();
    }

    /**
     * Получить активные объявления с пагинацией
     */
    public function getActiveAds(int $perPage = 20, array $withRelations = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Ad::where('status', AdStatus::ACTIVE->value);
        
        if (!empty($withRelations)) {
            $query->with($withRelations);
        }
        
        return $query->latest()->paginate($perPage);
    }

    /**
     * Проверить готовность черновика к публикации
     */
    public function validateDraftForPublication(Ad $ad): array
    {
        $missingFields = [];
        
        if (!$ad->title) {
            $missingFields[] = 'Заголовок';
        }
        if (!$ad->description) {
            $missingFields[] = 'Описание';
        }
        if (!$ad->price || $ad->price <= 0) {
            $missingFields[] = 'Цена';
        }
        if (!$ad->phone) {
            $missingFields[] = 'Телефон';
        }
        if (!$ad->category) {
            $missingFields[] = 'Категория';
        }
        
        return [
            'ready' => empty($missingFields),
            'missing_fields' => $missingFields
        ];
    }
}