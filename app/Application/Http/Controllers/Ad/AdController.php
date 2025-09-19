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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для базовых CRUD операций с объявлениями
 * Простой и понятный, следует принципу KISS
 */
class AdController extends Controller
{
    public function __construct(
        private AdService $adService,
        private DraftService $draftService
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
     * Создание объявления с модерацией
     */
    public function store(CreateAdRequest $request): RedirectResponse
    {
        Log::info('🟢 AdController::store НАЧАЛО', [
            'user_id' => Auth::id(),
            'request_method' => $request->method(),
            'request_keys' => array_keys($request->all()),
            'has_title' => !empty($request->input('title')),
            'has_display_name' => !empty($request->input('display_name')),
            'category' => $request->input('category'),
            'content_type' => $request->header('Content-Type')
        ]);

        Log::info('📝 AdController: Подготовка валидации данных', [
            'validation_rules_count' => count(app(CreateAdRequest::class)->rules()),
            'request_data_preview' => [
                'title' => $request->input('title', 'не указан'),
                'age' => $request->input('age', 'не указан'),
                'work_format' => $request->input('work_format', 'не указан'),
                'address' => $request->input('address', 'не указан'),
                'services' => $request->input('services') ? 'есть' : 'нет',
                'photos' => $request->hasFile('photos') ? 'есть файлы' : 'нет файлов',
                'geo' => $request->input('geo') ? 'есть' : 'нет'
            ]
        ]);

        // Обрабатываем фотографии, видео и проверочное фото перед передачей в DraftService
        $processedPhotos = $this->processPhotosFromRequest($request);
        $processedVideo = $this->processVideoFromRequest($request);
        $processedVerificationPhoto = $this->processVerificationPhotoFromRequest($request);
        
        // Используем DraftService для создания объявления (как черновики)
        $data = array_merge(
            $request->validated(),
            [
                'user_id' => Auth::id(),
                'status' => 'active',
                'is_published' => false, // На модерацию
                'photos' => $processedPhotos, // Добавляем обработанные фотографии
                'video' => $processedVideo, // Добавляем обработанные видео
                'verification_photo' => $processedVerificationPhoto // Добавляем обработанное проверочное фото
            ]
        );

        Log::info('📋 AdController: Данные подготовлены, отправляем в DraftService', [
            'data_keys' => array_keys($data),
            'user_id' => Auth::id(),
            'data_preview' => [
                'title' => $data['title'] ?? 'нет',
                'age' => $data['age'] ?? 'нет',
                'work_format' => $data['work_format'] ?? 'нет',
                'address' => $data['address'] ?? 'нет',
                'photos_count' => count($data['photos'] ?? []),
                'geo' => isset($data['geo']) ? 'есть' : 'нет'
            ]
        ]);

        $ad = $this->draftService->saveOrUpdate($data, Auth::user());

        Log::info('✅ AdController: Объявление создано успешно', [
            'ad_id' => $ad->id,
            'user_id' => Auth::id(),
            'is_published' => $ad->is_published,
            'status' => $ad->status->value ?? 'unknown'
        ]);

        $redirectUrl = route('additem.success', ['ad' => $ad->id]);
        Log::info('🔄 AdController: Выполняем redirect на страницу успеха', [
            'ad_id' => $ad->id,
            'redirect_route' => 'additem.success',
            'redirect_url' => $redirectUrl
        ]);

        // Перенаправляем на страницу успеха
        return redirect()
            ->route('additem.success', ['ad' => $ad->id]);
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
            
            return Inertia::render('Ad/Edit', [
                'ad' => $preparedData,
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

        // Обрабатываем фотографии, видео и проверочное фото перед передачей в DraftService
        $processedPhotos = $this->processPhotosFromRequest($request);
        $processedVideo = $this->processVideoFromRequest($request);
        $processedVerificationPhoto = $this->processVerificationPhotoFromRequest($request);
        
        // Используем DraftService для обновления (как черновики)
        $data = array_merge(
            $request->validated(),
            [
                'photos' => $processedPhotos, // Добавляем обработанные фотографии
                'video' => $processedVideo, // Добавляем обработанные видео
                'verification_photo' => $processedVerificationPhoto // Добавляем обработанное проверочное фото
            ]
        );
        
        \Log::info('🟢 AdController::update Данные после валидации', [
            'data_keys' => array_keys($data),
            'status' => $data['status'] ?? 'НЕТ',
            'is_published' => $data['is_published'] ?? 'НЕТ',
            'request_status' => $request->input('status'),
            'request_is_published' => $request->input('is_published'),
            'photos_count' => count($processedPhotos)
        ]);
        
        $updatedAd = $this->draftService->saveOrUpdate($data, Auth::user(), $ad->id);
        
        \Log::info('🟢 AdController::update обновление завершено', [
            'ad_id' => $updatedAd->id,
            'ad_status' => $updatedAd->status,
            'is_published' => $updatedAd->is_published,
            'request_status' => $request->input('status'),
            'is_paid' => $updatedAd->is_paid,
            'expires_at' => $updatedAd->expires_at,
            'is_active' => $updatedAd->isActive(),
            'status_enum_value' => $updatedAd->status->value ?? 'null'
        ]);

        // Если объявление только что опубликовано (переход из черновика в активные на модерацию)
        if ($updatedAd->status === \App\Domain\Ad\Enums\AdStatus::ACTIVE &&
            $updatedAd->is_published === false) {
            \Log::info('🟢 AdController::update ПУБЛИКАЦИЯ - перенаправляем на страницу успеха', [
                'ad_id' => $updatedAd->id,
                'redirect_to' => route('additem.success', ['ad' => $updatedAd->id])
            ]);
            return redirect()
                ->route('additem.success', ['ad' => $updatedAd->id]);
        }

        // Для активных объявлений перенаправляем на страницу активных
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
     * ВРЕМЕННЫЙ DEBUG ENDPOINT для диагностики данных формы
     */
    public function debugForm(Request $request)
    {
        Log::info('🔍 DEBUG ENDPOINT: Полные данные запроса', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'all_data' => $request->all(),
            'files' => $request->allFiles(),
            'user_id' => Auth::id(),
            'content_type' => $request->header('Content-Type'),
            'raw_input' => $request->getContent()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Debug data logged',
            'data_received' => $request->all(),
            'files_received' => array_keys($request->allFiles())
        ]);
    }

    /**
     * Страница успешной публикации объявления
     */
    public function success(Ad $ad): Response
    {
        // Проверяем права доступа
        if ($ad->user_id !== auth()->id()) {
            abort(403, 'Доступ запрещен');
        }

        return Inertia::render('AddItem/Success', [
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'status' => $ad->status->value,
                'is_published' => $ad->is_published,
                'moderated_at' => $ad->moderated_at,
                'created_at' => $ad->created_at,
            ]
        ]);
    }
    
    /**
     * Обработать массив фотографий из запроса
     * Упрощенная логика для читаемости
     * @param Request $request Запрос
     * @param int $maxPhotos Максимальное количество фото для обработки
     * @return array Массив путей к фотографиям
     */
    private function processPhotosFromRequest(Request $request, int $maxPhotos = 50): array
    {
        \Log::info('🔍 AdController::processPhotosFromRequest: НАЧАЛО', [
            'request_all' => array_keys($request->all()),
            'request_files' => array_keys($request->allFiles()),
            'request_photos' => $request->input('photos'),
            'request_photos_type' => gettype($request->input('photos'))
        ]);
        
        $photosData = [];
        
        // Проверяем, есть ли photos как JSON строка (новый формат)
        if ($request->has('photos')) {
            $photosValue = $request->input('photos');
            
            // Если это массив (уже распарсенный JSON)
            if (is_array($photosValue)) {
                \Log::info('🔍 AdController: photos уже массив, обрабатываем', [
                    'photos_count' => count($photosValue),
                    'photos_sample' => array_slice($photosValue, 0, 2)
                ]);
                
                foreach ($photosValue as $photo) {
                    if (is_array($photo) && isset($photo['file'])) {
                        // Это File объект - сохраняем
                        if ($photo['file'] instanceof \Illuminate\Http\UploadedFile) {
                            $file = $photo['file'];
                            if ($file->getSize() <= 10 * 1024 * 1024) {
                                try {
                                    $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    $path = $file->storeAs('photos/' . Auth::id(), $fileName, 'public');
                                    $photosData[] = '/storage/' . $path;
                                    \Log::info("✅ AdController: Сохранен файл фото: {$path}");
                                } catch (\Exception $e) {
                                    \Log::error('AdController: Ошибка загрузки фото: ' . $e->getMessage());
                                }
                            }
                        }
                    } elseif (is_string($photo) && !empty($photo)) {
                        // Это строка (URL или base64)
                        if (str_starts_with($photo, 'data:image/')) {
                            $savedPath = $this->saveBase64Photo($photo);
                            if ($savedPath) {
                                $photosData[] = $savedPath;
                                \Log::info("✅ AdController: Сохранено base64 фото: {$savedPath}");
                            }
                        } else {
                            $photosData[] = $photo;
                            \Log::info("✅ AdController: Сохранен URL фото: {$photo}");
                        }
                    }
                }
            }
            // Если это JSON строка - парсим
            elseif (is_string($photosValue) && (str_starts_with($photosValue, '[') || str_starts_with($photosValue, '{'))) {
                try {
                    $decoded = json_decode($photosValue, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        \Log::info('🔍 AdController: photos JSON строка, парсим', [
                            'photos_count' => count($decoded),
                            'photos_sample' => array_slice($decoded, 0, 2)
                        ]);
                        
                        foreach ($decoded as $photo) {
                            if (is_array($photo) && isset($photo['file'])) {
                                // Это File объект - сохраняем
                                if ($photo['file'] instanceof \Illuminate\Http\UploadedFile) {
                                    $file = $photo['file'];
                                    if ($file->getSize() <= 10 * 1024 * 1024) {
                                        try {
                                            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                                            $path = $file->storeAs('photos/' . Auth::id(), $fileName, 'public');
                                            $photosData[] = '/storage/' . $path;
                                            \Log::info("✅ AdController: Сохранен файл фото: {$path}");
                                        } catch (\Exception $e) {
                                            \Log::error('AdController: Ошибка загрузки фото: ' . $e->getMessage());
                                        }
                                    }
                                }
                            } elseif (is_string($photo) && !empty($photo)) {
                                // Это строка (URL или base64)
                                if (str_starts_with($photo, 'data:image/')) {
                                    $savedPath = $this->saveBase64Photo($photo);
                                    if ($savedPath) {
                                        $photosData[] = $savedPath;
                                        \Log::info("✅ AdController: Сохранено base64 фото: {$savedPath}");
                                    }
                                } else {
                                    $photosData[] = $photo;
                                    \Log::info("✅ AdController: Сохранен URL фото: {$photo}");
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('AdController: Ошибка парсинга JSON photos: ' . $e->getMessage());
                }
            }
        }
        
        // Fallback: старый формат photos[0], photos[1], etc.
        if (empty($photosData)) {
            \Log::info('🔍 AdController: Fallback к старому формату photos[index]');
            for ($index = 0; $index < $maxPhotos; $index++) {
                $bracketNotation = "photos[{$index}]";
                $dotNotation = "photos.{$index}";
                
                // Проверяем файл
                if ($request->hasFile($bracketNotation) || $request->hasFile($dotNotation)) {
                    $file = $request->file($bracketNotation) ?: $request->file($dotNotation);
                    
                    // Проверка размера (10MB)
                    if ($file && $file->getSize() <= 10 * 1024 * 1024) {
                        try {
                            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                            $path = $file->storeAs('photos/' . Auth::id(), $fileName, 'public');
                            $photosData[] = '/storage/' . $path;
                            \Log::info("✅ AdController: Сохранен файл фото: {$path}");
                        } catch (\Exception $e) {
                            \Log::error('AdController: Ошибка загрузки фото: ' . $e->getMessage());
                        }
                    }
                }
                // Проверяем значение (существующее фото или base64)
                elseif ($request->has($bracketNotation) || $request->has($dotNotation)) {
                    $photoValue = $request->input($bracketNotation) ?: $request->input($dotNotation);
                    
                    if (is_string($photoValue) && !empty($photoValue) && $photoValue !== '[]') {
                        // Если это base64 - сохраняем как файл
                        if (str_starts_with($photoValue, 'data:image/')) {
                            $savedPath = $this->saveBase64Photo($photoValue);
                            if ($savedPath) {
                                $photosData[] = $savedPath;
                                \Log::info("✅ AdController: Сохранено base64 фото: {$savedPath}");
                            }
                        } else {
                            // Обычный URL
                            $photosData[] = $photoValue;
                            \Log::info("✅ AdController: Сохранен URL фото: {$photoValue}");
                        }
                    }
                } else {
                    // Нет больше фото
                    break;
                }
            }
        }
        
        \Log::info('🔍 AdController::processPhotosFromRequest: РЕЗУЛЬТАТ', [
            'photos_count' => count($photosData),
            'result' => $photosData
        ]);
        
        return $photosData;
    }
    
    /**
     * Сохранить base64 фото как файл
     * @param string $base64Data Base64 данные
     * @return string|null Путь к файлу или null
     */
    private function saveBase64Photo(string $base64Data): ?string
    {
        try {
            // Проверяем что это base64
            if (!str_starts_with($base64Data, 'data:image/')) {
                return null;
            }
            
            // Декодируем base64
            $parts = explode(',', $base64Data, 2);
            if (count($parts) !== 2) {
                return null;
            }
            
            $imageData = base64_decode($parts[1]);
            if (!$imageData) {
                return null;
            }
            
            // Определяем расширение
            preg_match('/data:image\/([^;]+)/', $base64Data, $matches);
            $extension = $matches[1] ?? 'jpg';
            
            // Генерируем имя файла
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $path = 'photos/' . Auth::id() . '/' . $fileName;
            
            // Сохраняем файл
            Storage::disk('public')->put($path, $imageData);
            
            return '/storage/' . $path;
        } catch (\Exception $e) {
            \Log::error('AdController: Ошибка сохранения base64 фото: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Обработка видео из запроса
     */
    private function processVideoFromRequest(Request $request, int $maxVideos = 10): array
    {
        \Log::info('🔍 AdController::processVideoFromRequest: НАЧАЛО', [
            'request_all' => array_keys($request->all()),
            'request_files' => array_keys($request->allFiles()),
            'request_video' => $request->input('video'),
            'request_video_type' => gettype($request->input('video'))
        ]);
        
        $videoData = [];
        
        // Проверяем, есть ли video как JSON строка (новый формат)
        if ($request->has('video')) {
            $videoValue = $request->input('video');
            
            // Если это массив (уже распарсенный JSON)
            if (is_array($videoValue)) {
                \Log::info('🔍 AdController: video уже массив, обрабатываем', [
                    'video_count' => count($videoValue),
                    'video_sample' => array_slice($videoValue, 0, 2)
                ]);
                
                foreach ($videoValue as $video) {
                    if (is_array($video) && isset($video['file'])) {
                        // Это File объект - сохраняем
                        if ($video['file'] instanceof \Illuminate\Http\UploadedFile) {
                            $file = $video['file'];
                            if ($file->getSize() <= 100 * 1024 * 1024) { // 100MB для видео
                                try {
                                    $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                                    $videoData[] = '/storage/' . $path;
                                    \Log::info("✅ AdController: Сохранен файл видео: {$path}");
                                } catch (\Exception $e) {
                                    \Log::error('AdController: Ошибка загрузки видео: ' . $e->getMessage());
                                }
                            }
                        }
                    } elseif (is_string($video) && !empty($video)) {
                        // Это строка (URL или base64)
                        if (str_starts_with($video, 'data:video/')) {
                            $savedPath = $this->saveBase64Video($video);
                            if ($savedPath) {
                                $videoData[] = $savedPath;
                                \Log::info("✅ AdController: Сохранено base64 видео: {$savedPath}");
                            }
                        } else {
                            $videoData[] = $video;
                            \Log::info("✅ AdController: Сохранен URL видео: {$video}");
                        }
                    }
                }
            }
            // Если это JSON строка - парсим
            elseif (is_string($videoValue) && (str_starts_with($videoValue, '[') || str_starts_with($videoValue, '{'))) {
                try {
                    $decoded = json_decode($videoValue, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        \Log::info('🔍 AdController: video JSON строка, парсим', [
                            'video_count' => count($decoded),
                            'video_sample' => array_slice($decoded, 0, 2)
                        ]);
                        
                        foreach ($decoded as $video) {
                            if (is_array($video) && isset($video['file'])) {
                                // Это File объект - сохраняем
                                if ($video['file'] instanceof \Illuminate\Http\UploadedFile) {
                                    $file = $video['file'];
                                    if ($file->getSize() <= 100 * 1024 * 1024) { // 100MB для видео
                                        try {
                                            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                                            $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                                            $videoData[] = '/storage/' . $path;
                                            \Log::info("✅ AdController: Сохранен файл видео: {$path}");
                                        } catch (\Exception $e) {
                                            \Log::error('AdController: Ошибка загрузки видео: ' . $e->getMessage());
                                        }
                                    }
                                }
                            } elseif (is_string($video) && !empty($video)) {
                                // Это строка (URL или base64)
                                if (str_starts_with($video, 'data:video/')) {
                                    $savedPath = $this->saveBase64Video($video);
                                    if ($savedPath) {
                                        $videoData[] = $savedPath;
                                        \Log::info("✅ AdController: Сохранено base64 видео: {$savedPath}");
                                    }
                                } else {
                                    $videoData[] = $video;
                                    \Log::info("✅ AdController: Сохранен URL видео: {$video}");
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('AdController: Ошибка парсинга JSON video: ' . $e->getMessage());
                }
            }
        }
        
        // Fallback: старый формат video[0], video[1], etc.
        if (empty($videoData)) {
            \Log::info('🔍 AdController: Fallback к старому формату video[index]');
            for ($index = 0; $index < $maxVideos; $index++) {
                $bracketNotation = "video[{$index}]";
                $dotNotation = "video.{$index}";
                
                // Проверяем файл
                if ($request->hasFile($bracketNotation) || $request->hasFile($dotNotation)) {
                    $file = $request->file($bracketNotation) ?: $request->file($dotNotation);
                    
                    // Проверка размера (100MB для видео)
                    if ($file && $file->getSize() <= 100 * 1024 * 1024) {
                        try {
                            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                            $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                            $videoData[] = '/storage/' . $path;
                            \Log::info("✅ AdController: Сохранен файл видео: {$path}");
                        } catch (\Exception $e) {
                            \Log::error('AdController: Ошибка загрузки видео: ' . $e->getMessage());
                        }
                    }
                }
                // Проверяем значение (существующее видео или base64)
                elseif ($request->has($bracketNotation) || $request->has($dotNotation)) {
                    $videoValue = $request->input($bracketNotation) ?: $request->input($dotNotation);
                    
                    if (is_string($videoValue) && !empty($videoValue) && $videoValue !== '[]') {
                        // Если это base64 - сохраняем как файл
                        if (str_starts_with($videoValue, 'data:video/')) {
                            $savedPath = $this->saveBase64Video($videoValue);
                            if ($savedPath) {
                                $videoData[] = $savedPath;
                                \Log::info("✅ AdController: Сохранено base64 видео: {$savedPath}");
                            }
                        } else {
                            // Обычный URL
                            $videoData[] = $videoValue;
                            \Log::info("✅ AdController: Сохранен URL видео: {$videoValue}");
                        }
                    }
                } else {
                    // Нет больше видео
                    break;
                }
            }
        }
        
        \Log::info('🔍 AdController::processVideoFromRequest: РЕЗУЛЬТАТ', [
            'video_count' => count($videoData),
            'result' => $videoData
        ]);
        
        return $videoData;
    }

    /**
     * Сохранение base64 видео
     */
    private function saveBase64Video(string $base64Data): ?string
    {
        try {
            // Извлекаем данные
            $data = explode(',', $base64Data);
            if (count($data) !== 2) {
                return null;
            }
            
            $videoData = base64_decode($data[1]);
            if ($videoData === false) {
                return null;
            }
            
            // Определяем расширение
            preg_match('/data:video\/([^;]+)/', $base64Data, $matches);
            $extension = $matches[1] ?? 'mp4';
            
            // Генерируем имя файла
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $path = 'videos/' . Auth::id() . '/' . $fileName;
            
            // Сохраняем файл
            Storage::disk('public')->put($path, $videoData);
            
            return '/storage/' . $path;
        } catch (\Exception $e) {
            \Log::error('AdController: Ошибка сохранения base64 видео: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Обработка проверочного фото из запроса
     */
    private function processVerificationPhotoFromRequest(Request $request): ?string
    {
        \Log::info('🔍 AdController::processVerificationPhotoFromRequest: НАЧАЛО', [
            'request_verification_photo' => $request->input('verification_photo'),
            'request_verification_photo_type' => gettype($request->input('verification_photo'))
        ]);
        
        $verificationPhoto = $request->input('verification_photo');
        
        if (empty($verificationPhoto)) {
            \Log::info('🔍 AdController: verification_photo пустое');
            return null;
        }
        
        // Если это base64 - сохраняем как файл
        if (str_starts_with($verificationPhoto, 'data:image/')) {
            $savedPath = $this->saveBase64Photo($verificationPhoto);
            if ($savedPath) {
                \Log::info("✅ AdController: Сохранено base64 проверочное фото: {$savedPath}");
                return $savedPath;
            }
        } else {
            // Обычный URL
            \Log::info("✅ AdController: Сохранен URL проверочного фото: {$verificationPhoto}");
            return $verificationPhoto;
        }
        
        \Log::info('🔍 AdController::processVerificationPhotoFromRequest: РЕЗУЛЬТАТ', [
            'result' => $verificationPhoto
        ]);
        
        return $verificationPhoto;
    }
}