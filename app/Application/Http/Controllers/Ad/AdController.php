<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Ad\CreateAdRequest;
use App\Application\Http\Requests\Ad\UpdateAdRequest;
use App\Application\Http\Resources\Ad\AdResource;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\DraftService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\CreateAdDTO;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для базовых CRUD операций с объявлениями
 * Простой и понятный, следует принципу KISS
 */
class AdController extends Controller
{
    public function __construct(
        private AdService $adService
    ) {}

    /**
     * Список объявлений
     */
    public function index(): Response
    {
        $ads = $this->adService->getActiveAds(
            perPage: 20,
            withRelations: ['photos', 'user']
        );

        return Inertia::render('Ads/Index', [
            'ads' => AdResource::collection($ads)
        ]);
    }

    /**
     * Публичный просмотр объявления (доступен без авторизации)
     */
    public function showPublic(string $slug, int $ad): Response
    {
        // Загружаем объявление
        $adModel = Ad::with(['user'])->findOrFail($ad);
        
        // Проверяем, что объявление активно
        // ВРЕМЕННО: разрешаем просмотр неоплаченных объявлений для тестирования
        // if (!$adModel->isActive()) {
        //     abort(404);
        // }
        
        // Проверяем корректность slug
        if ($adModel->slug !== $slug && $adModel->slug) {
            return redirect()->route('ads.show.public', [
                'slug' => $adModel->slug,
                'ad' => $adModel->id
            ], 301);
        }
        
        // Увеличиваем просмотры
        $this->adService->incrementViews($adModel);
        
        // Подготавливаем данные для отображения в стиле мастера
        $masterData = [
            'id' => $adModel->id,
            'name' => $adModel->title ?? $adModel->name ?? 'Мастер',
            'avatar' => $adModel->avatar ?? $adModel->photosCollection?->first()?->url ?? null,
            'specialty' => $adModel->specialty ?? 'Массаж',
            'description' => $adModel->description,
            'rating' => $adModel->rating ?? 4.5,
            'reviews_count' => $adModel->reviews_count ?? 0,
            'services' => $this->prepareServices($adModel),
            'photos' => $this->preparePhotos($adModel),
            'location' => $adModel->address ?? $adModel->district ?? 'Москва',
            'price' => $adModel->price ?? $adModel->price_from ?? 2000,
            'phone' => $adModel->phone,
            'whatsapp' => $adModel->whatsapp,
            'telegram' => $adModel->telegram,
            'experience' => $adModel->experience ?? '5+ лет',
            'completion_rate' => '98%',
            'geo' => $adModel->geo,
            'parameters' => $adModel->parameters,
            'amenities' => $adModel->amenities,
            'comfort' => $adModel->comfort,
        ];
        
        return Inertia::render('Masters/Show', [
            'master' => $masterData
        ]);
    }
    
    /**
     * Просмотр объявления
     */
    public function show(Ad $ad): Response
    {
        // Проверяем доступ
        if (!$ad->isActive() && (!auth()->check() || auth()->id() !== $ad->user_id)) {
            abort(404);
        }

        // Увеличиваем просмотры
        $this->adService->incrementViews($ad);

        $ad->load(['user.profile']);

        return Inertia::render('Ads/Show', [
            'ad' => new AdResource($ad),
            'similarAds' => AdResource::collection(
                $this->adService->getSimilarAds($ad, limit: 4)
            )
        ]);
    }

    /**
     * Форма создания
     */
    public function create(): Response
    {
        // Рендерим новую страницу создания объявления
        // которая правильно очищает localStorage
        return Inertia::render('Ad/Create');
    }

    /**
     * Создание объявления
     */
    public function store(CreateAdRequest $request): RedirectResponse
    {
        $dto = CreateAdDTO::fromArray(
            array_merge(
                $request->validated(),
                ['user_id' => Auth::id()]
            )
        );

        $ad = $this->adService->createFromDTO($dto);

        // Если нужно сразу опубликовать
        if ($request->boolean('publish_immediately', true)) {
            $this->adService->publish($ad);
        }

        return redirect()
            ->route('profile.items.active')
            ->with('success', 'Объявление успешно создано');
    }

    /**
     * Форма редактирования
     */
    public function edit(Ad $ad): Response
    {
        // Защита от несуществующих объявлений
        if (!$ad->exists) {
            abort(404, 'Объявление не найдено');
        }
        
        $this->authorize('update', $ad);

        // ✅ ПРИНУДИТЕЛЬНОЕ ЛОГИРОВАНИЕ В AdController::edit
        Log::info("🔍 AdController::edit НАЧАЛО", [
            'ad_id' => $ad->id,
            'ad_status' => $ad->status,
            'ad_exists' => $ad->exists,
            'ad_attributes' => $ad->getAttributes(),
            'ad_keys' => array_keys($ad->getAttributes())
        ]);
        
        // Для черновиков используем DraftService для правильной подготовки данных
        if ($ad->status->value === 'draft') {
            Log::info("📸 AdController::edit: Это черновик, вызываем DraftService", [
                'ad_id' => $ad->id,
                'ad_status' => $ad->status
            ]);
            
            $draftService = app(\App\Domain\Ad\Services\DraftService::class);
            $preparedData = $draftService->prepareForDisplay($ad);
            
            Log::info("📸 AdController::edit: DraftService вернул данные", [
                'prepared_data_keys' => array_keys($preparedData),
                'prepared_data_count' => count($preparedData),
                'has_media_settings' => isset($preparedData['media_settings']),
                'media_settings_value' => $preparedData['media_settings'] ?? 'undefined'
            ]);
            
            // ВАЖНО: Убедимся, что ID всегда присутствует и имеет правильный тип
            $preparedData['id'] = (int) $ad->id;
            
            // ВАЖНО: Преобразуем enum work_format в строковое значение
            if (isset($preparedData['work_format']) && is_object($preparedData['work_format'])) {
                $preparedData['work_format'] = $preparedData['work_format']->value ?? 'individual';
            }
            
            // УНИФИКАЦИЯ: Возвращаем подготовленные данные напрямую для черновиков
            // AdResource не нужен для черновиков, так как DraftService уже подготовил данные
            
            return Inertia::render('Ad/Edit', [
                'ad' => ['data' => $preparedData], // Структура как в AdResource  
                'isActive' => false
            ]);
        }

        // ✅ ПРИНУДИТЕЛЬНОЕ ЛОГИРОВАНИЕ ДЛЯ АКТИВНЫХ ОБЪЯВЛЕНИЙ
        Log::info("📸 AdController::edit: Это активное объявление, используем AdResource", [
            'ad_id' => $ad->id,
            'ad_status' => $ad->status,
            'ad_is_active' => $ad->isActive()
        ]);
        
        // Для активных объявлений используем стандартный AdResource
        return Inertia::render('Ad/Edit', [
            'ad' => new AdResource($ad),
            'isActive' => $ad->isActive()
        ]);
    }

    /**
     * Обновление объявления
     */
    public function update(UpdateAdRequest $request, Ad $ad): RedirectResponse
    {
        \Log::info('🟢 AdController::update НАЧАЛО', [
            'ad_id' => $ad->id,
            'ad_status' => $ad->status,
            'request_data_keys' => array_keys($request->validated()),
            'service_provider' => $request->input('service_provider'),
            'clients' => $request->input('clients'),
            'user_id' => auth()->id()
        ]);
        
        $this->authorize('update', $ad);
        \Log::info('🟢 AdController::update авторизация пройдена');

        $updatedAd = $this->adService->update($ad, $request->validated());
        
        \Log::info('🟢 AdController::update обновление завершено', [
            'ad_id' => $updatedAd->id,
            'ad_status' => $updatedAd->status,
            'is_paid' => $updatedAd->is_paid,
            'expires_at' => $updatedAd->expires_at,
            'is_active' => $updatedAd->isActive(),
            'status_enum_value' => $updatedAd->status->value ?? 'null'
        ]);

        // Для активных объявлений перенаправляем на страницу активных
        // ВРЕМЕННО: проверяем только статус, не is_paid
        if ($updatedAd->status === \App\Domain\Ad\Enums\AdStatus::ACTIVE) {
            \Log::info('🟢 AdController::update АКТИВНОЕ объявление - перенаправляем на /profile/items/active/all', [
                'ad_id' => $updatedAd->id,
                'redirect_to' => '/profile/items/active/all'
            ]);
            return redirect()
                ->to('/profile/items/active/all')
                ->with('success', 'Изменения сохранены!');
        }
        
        // Для остальных объявлений переходим к просмотру
        \Log::info('🟢 AdController::update НЕ АКТИВНОЕ объявление - перенаправляем на ads.show', [
            'ad_id' => $updatedAd->id,
            'redirect_route' => 'ads.show'
        ]);
        return redirect()
            ->route('ads.show', $updatedAd)
            ->with('success', 'Объявление успешно обновлено');
    }

    /**
     * Удаление объявления
     */
    public function destroy(Ad $ad): RedirectResponse
    {
        $this->authorize('delete', $ad);

        $this->adService->delete($ad);

        return redirect()
            ->route('profile.items')
            ->with('success', 'Объявление успешно удалено');
    }
    
    /**
     * Подготовка услуг для отображения
     */
    private function prepareServices(Ad $ad): array
    {
        $services = [];
        
        // Если есть поле services в JSON
        if ($ad->services) {
            $servicesData = is_string($ad->services) ? json_decode($ad->services, true) : $ad->services;
            if (is_array($servicesData)) {
                foreach ($servicesData as $key => $value) {
                    if (is_array($value) && isset($value['name'])) {
                        $services[] = [
                            'id' => $key,
                            'name' => $value['name'],
                            'price' => $value['price'] ?? $ad->price ?? 2000,
                            'duration' => $value['duration'] ?? 60
                        ];
                    } elseif (is_string($value)) {
                        $services[] = [
                            'id' => $key,
                            'name' => $value,
                            'price' => $ad->price ?? 2000,
                            'duration' => 60
                        ];
                    }
                }
            }
        }
        
        // Если услуг нет, добавляем дефолтную
        if (empty($services)) {
            $services[] = [
                'id' => 1,
                'name' => $ad->specialty ?? 'Классический массаж',
                'price' => $ad->price ?? $ad->price_from ?? 2000,
                'duration' => 60
            ];
        }
        
        return $services;
    }
    
    /**
     * Подготовка фотографий для галереи
     */
    private function preparePhotos(Ad $ad): array
    {
        $photos = [];
        
        // Используем новый аксессор photosCollection
        $photosCollection = $ad->photosCollection;
        if ($photosCollection && $photosCollection->count() > 0) {
            foreach ($photosCollection as $photo) {
                $photos[] = [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'thumbnail_url' => $photo->thumbnail_url,
                    'alt' => 'Фото ' . ($photo->position + 1),
                    'caption' => null
                ];
            }
        }
        
        // Если фотографий нет, добавляем заглушку
        if (empty($photos)) {
            // Пробуем получить из других полей
            if ($ad->avatar) {
                $photos[] = [
                    'id' => 'avatar',
                    'url' => $ad->avatar,
                    'thumbnail_url' => $ad->avatar,
                    'alt' => 'Главное фото'
                ];
            } else {
                // Добавляем дефолтное изображение
                $photos[] = [
                    'id' => 'default',
                    'url' => '/images/no-photo.svg',
                    'thumbnail_url' => '/images/no-photo.svg',
                    'alt' => 'Нет фото'
                ];
            }
        }
        
        return $photos;
    }
}