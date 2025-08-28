<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Services\MasterService;
use App\Domain\Master\Services\MasterGalleryService;
use App\Domain\Master\Services\MasterDTOBuilder;
use App\Domain\Master\Services\MasterApiService;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Application\Http\Requests\UpdateMasterRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterController extends Controller
{
    public function __construct(
        private MasterService $masterService,
        private MasterGalleryService $galleryService,
        private MasterDTOBuilder $dtoBuilder,
        private MasterApiService $apiService,
        private \App\Domain\Master\Repositories\MasterRepository $masterRepository
    ) {}
    /**
     * Публичная карточка мастера
     */
    public function show(string $slug, int $master)
    {
        // ОТЛАДКА: логируем что метод вызван
        \Log::info('🎯 MasterController::show вызван', [
            'slug' => $slug,
            'master_id' => $master,
            'url' => request()->url(),
            'route_name' => request()->route()?->getName()
        ]);
        
        // Загружаем профиль через репозиторий
        $profile = $this->masterRepository->findWithRelations($master, [
            'services',
            'reviews',
            'photos',
            'videos',
            'workZones',
            'schedules'
        ]);

        if (!$profile) {
            abort(404);
        }

        // Проверяем SEO-URL
        if (!$this->masterService->isValidSlug($profile, $slug)) {
            return redirect()->route('masters.show', [
                'slug'   => $profile->slug,
                'master' => $profile->id,
            ], 301);
        }

        // Обновляем метрики
        $this->masterService->ensureMetaTags($profile);
        $this->masterService->incrementViews($profile);

        // Строим DTO через сервис
        $masterDTO = $this->dtoBuilder->buildProfileDTO($profile, auth()->id());
        $meta = $this->dtoBuilder->buildMeta($profile);

        // ИСПРАВЛЕНИЕ: Загружаем фотографии из связанного объявления
        $adPhotos = [];
        try {
            // Ищем активное объявление пользователя мастера
            $ad = \DB::table('ads')
                ->where('user_id', $profile->user_id)
                ->where('status', 'active')
                ->first();
            
            // ОТЛАДКА: логируем что нашли
            \Log::info('MasterController DEBUG', [
                'master_id' => $profile->id,
                'user_id' => $profile->user_id,
                'ad_found' => !!$ad,
                'ad_data' => $ad ? ['id' => $ad->id, 'title' => $ad->title, 'has_photos' => !!$ad->photos] : null
            ]);
            
            if ($ad && $ad->photos) {
                // ИСПРАВЛЕНИЕ: двойное декодирование JSON (данные хранятся как строка JSON внутри JSON)
                $photosArray = json_decode(json_decode($ad->photos, true), true);
                \Log::info('Photos double JSON decoded', [
                    'raw_photos' => $ad->photos,
                    'decoded_photos' => $photosArray,
                    'is_array' => is_array($photosArray),
                    'count' => is_array($photosArray) ? count($photosArray) : 0
                ]);
                
                if (is_array($photosArray) && count($photosArray) > 0) {
                    $adPhotos = array_map(function($photoUrl) {
                        return [
                            'url' => $photoUrl,
                            'thumbnail_url' => $photoUrl,
                            'alt' => 'Фото мастера'
                        ];
                    }, $photosArray);
                    
                    \Log::info('Transformed photos SUCCESS', ['count' => count($adPhotos), 'first_url' => $adPhotos[0]['url'] ?? 'none']);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка загрузки фотографий: ' . $e->getMessage());
        }

        // Безопасная обработка reviews (всегда Collection в DTO)
        $reviews = $masterDTO->reviews->take(10)->toArray();
        
        // Добавляем фотографии в массив мастера
        $masterArray = $masterDTO->toArray();
        if (!empty($adPhotos)) {
            $masterArray['photos'] = $adPhotos;
        }

        return Inertia::render('Masters/Show', [
            'master'         => $masterArray,
            'gallery'        => !empty($adPhotos) ? $adPhotos : $masterDTO->gallery,
            'meta'           => $meta,
            'similarMasters' => $this->masterService->getSimilarMasters($profile->id, $profile->city, 5),
            'reviews'        => $reviews,
            'availableSlots' => [],
            'canReview'      => auth()->check(),
        ]);
    }

    /**
     * Страница редактирования профиля мастера
     */
    public function edit(MasterProfile $master)
    {
        $this->authorize('update', $master);

        // Загружаем профиль с медиафайлами через репозиторий
        $masterWithMedia = $this->masterRepository->findWithMedia($master->id);
        
        if (!$masterWithMedia) {
            abort(404);
        }

        return Inertia::render('Masters/Edit', [
            'master' => $this->dtoBuilder->buildEditDTO($masterWithMedia)->toArray()
        ]);
    }

    /**
     * Обновление профиля мастера
     */
    public function update(UpdateMasterRequest $request, MasterProfile $master)
    {
        $this->authorize('update', $master);

        try {
            $dto = UpdateMasterDTO::fromRequest($request->validated());
            $this->masterService->updateProfile($master->id, $dto);
            
            return redirect()->back()->with('success', 'Профиль обновлен успешно!');
            
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Ошибка при обновлении профиля: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Список мастеров с фильтрацией и сортировкой
     */
    public function apiIndex(Request $request)
    {
        try {
            $filters = $request->only(['city', 'search', 'sort', 'category', 'price_min', 'price_max']);
            
            // Получаем и трансформируем объявления через сервис
            $ads = $this->apiService->getFilteredAds($filters);
            $transformed = $this->apiService->transformForApi($ads);
            
            return response()->json([
                'data' => $transformed,
                'total' => $transformed->count()
            ]);

        } catch (\Throwable $e) {
            // Логируем ошибку для отладки
            \Log::error('MasterController::apiIndex error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Возвращаем пустой результат для карты
            return response()->json([
                'data' => [],
                'total' => 0,
                'error' => 'Временно недоступно'
            ], 200); // 200 вместо 500 чтобы карта не ломалась
        }
    }

    /**
     * API: Данные конкретного мастера
     */
    public function apiShow(int $master)
    {
        try {
            $profile = $this->masterRepository->findWithRelations($master, [
                'services',
                'reviews',
                'photos',
                'videos'
            ]);
            
            if (!$profile) {
                return response()->json([
                    'error' => 'Мастер не найден'
                ], 404);
            }
            
            $dto = $this->dtoBuilder->buildListItemDTO($profile);
            
            return response()->json($dto->toArray());

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Мастер не найден',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}
