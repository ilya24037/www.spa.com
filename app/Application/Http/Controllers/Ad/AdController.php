<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Ad\CreateAdRequest;
use App\Application\Http\Requests\Ad\UpdateAdRequest;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Infrastructure\Media\PathGenerator;
use App\Helpers\SimpleImageOptimizer;

/**
 * Основной контроллер для управления объявлениями
 * Отвечает за создание, редактирование и публикацию
 */
class AdController extends Controller
{
    private AdService $adService;
    
    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }
    
    /**
     * Страница создания объявления
     */
    public function addItem()
    {
        return Inertia::render('AddItem');
    }

    /**
     * Сохранить объявление
     */
    public function store(Request $request)
    {
        try {
            // Валидация основных полей
            $validated = $request->validate([
                'category' => 'required|string',
                'title' => 'required|string|max:255',
                'specialty' => 'required|string',
                'description' => 'required|string|min:50',
                'price' => 'required|numeric|min:0',
                'phone' => 'required|string',
            ]);
            
            // Добавляем user_id
            $validated['user_id'] = Auth::id();
            $validated['status'] = AdStatus::ACTIVE->value;
            
            // Добавляем дополнительные поля
            $data = array_merge($validated, $request->only([
                'clients', 'work_format', 'service_provider',
                'experience', 'services', 'services_additional_info', 'features',
                'additional_features', 'schedule', 'schedule_notes', 'price_unit',
                'is_starting_price', 'main_service_name', 'main_service_price',
                'main_service_price_unit', 'additional_services', 'age', 'height', 'weight',
                'breast_size', 'hair_color', 'eye_color', 'nationality', 'new_client_discount',
                'gift', 'photos', 'videos', 'media_settings', 'geo', 'address',
                'custom_travel_areas', 'travel_radius', 'travel_price',
                'travel_price_type', 'contact_method', 'whatsapp', 'telegram'
            ]));
            
            // Создаем DTO
            $dto = CreateAdDTO::fromArray($data);
            
            // Создаем объявление через сервис
            $ad = $this->adService->createFromDTO($dto);
            
            // Публикуем объявление
            $this->adService->publish($ad);
            
            // Перенаправляем на страницу активных объявлений
            return redirect()->route('profile.items.active')
                ->with('success', 'Объявление успешно создано и опубликовано!');
                
        } catch (\Exception $e) {
            \Log::error('Ошибка создания объявления', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Ошибка при создании объявления'])
                ->withInput();
        }
    }

    /**
     * Опубликовать объявление (AJAX)
     */
    public function publish(Request $request)
    {
        try {
            // Валидация обязательных полей для публикации
            $validated = $request->validate([
                'category' => 'required|string',
                'title' => 'required|string|max:255',
                'specialty' => 'required|string',
                'description' => 'required|string|min:50',
                'price' => 'required|numeric|min:0',
                'phone' => 'required|string',
            ]);
            
            // Добавляем user_id и статус
            $validated['user_id'] = Auth::id();
            $validated['status'] = AdStatus::ACTIVE->value;
            
            // Добавляем все остальные поля
            $data = array_merge($validated, $request->all());
            
            // Создаем DTO
            $dto = CreateAdDTO::fromArray($data);
            
            // Создаем и публикуем объявление
            $ad = $this->adService->createFromDTO($dto);
            $this->adService->publish($ad);
            
            // Для Inertia запросов возвращаем redirect
            if ($request->header('X-Inertia')) {
                return redirect()->route('profile.items.active')
                    ->with('success', 'Объявление успешно опубликовано');
            }
            
            // Для обычных AJAX запросов возвращаем JSON
            return response()->json([
                'success' => true,
                'message' => 'Объявление опубликовано',
                'ad' => [
                    'id' => $ad->id,
                    'title' => $ad->title
                ],
                'redirect' => route('profile.items.active')
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Заполните все обязательные поля'
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка публикации объявления', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при публикации объявления'
            ], 500);
        }
    }

    /**
     * Сохранить черновик объявления (логика из Backup)
     */
    public function storeDraft(Request $request)
    {
        // Для черновика не валидируем ничего - принимаем любые данные
        // Черновик может быть полностью пустым
        
        // Обрабатываем загруженные фотографии
        $photoUrls = [];
        
        // Проверяем, есть ли строка '[]' - значит все фото удалили
        if ($request->photos === '[]') {
            $photoUrls = [];
        } else {
            // Обрабатываем массив фотографий (могут быть и файлы, и URL-строки)
            if ($request->photos && is_array($request->photos)) {
                foreach ($request->photos as $key => $photo) {
                    // Проверяем, является ли элемент файлом
                    if ($request->hasFile("photos.$key")) {
                        $file = $request->file("photos.$key");
                        if ($file && $file->isValid()) {
                            // Определяем ID объявления (для новых используем временный ID)
                            $adId = $request->id ?: 0; // Используем 0 для новых черновиков
                            $userId = Auth::id();
                            
                            // Генерируем путь с новой структурой
                            $extension = $file->getClientOriginalExtension() ?: 'jpg';
                            $originalPath = PathGenerator::adPhotoPath($userId, $adId, $extension, 'original');
                            
                            // Оптимизируем изображение перед сохранением
                            $optimizedFile = SimpleImageOptimizer::optimize($file);
                            
                            // Сохраняем оригинал в новую структуру
                            Storage::disk('public')->put($originalPath, file_get_contents($optimizedFile->getRealPath()));
                            
                            // Пробуем конвертировать в WebP
                            $fullPath = storage_path('app/public/' . $originalPath);
                            $webpPath = SimpleImageOptimizer::convertToWebP($fullPath);
                            if ($webpPath && $webpPath !== $fullPath) {
                                $originalPath = str_replace(storage_path('app/public/'), '', $webpPath);
                            }
                            
                            // Создаем thumb версию (опционально)
                            // TODO: добавить создание thumb версии
                            
                            $photoUrls[] = '/storage/' . $originalPath;
                        }
                    } elseif (is_string($photo)) {
                        // Это URL существующего фото
                        $photoUrls[] = $photo;
                    }
                }
            }
        }
        
        // Обрабатываем загруженные видео (аналогично фото)
        $videoUrls = [];
        
        // Логирование для отладки
        \Log::info('Video processing started:', [
            'has_video' => $request->has('video'),
            'video_value' => $request->video,
            'is_array' => is_array($request->video)
        ]);
        
        // Проверяем, есть ли строка '[]' - значит все видео удалили (как у фото)
        if ($request->video === '[]') {
            $videoUrls = [];
            \Log::info('Video cleared (empty array)');
        } else {
            // Обрабатываем массив видео (могут быть и файлы, и URL-строки) - как у фото
            if ($request->video && is_array($request->video)) {
                foreach ($request->video as $key => $video) {
                    // Проверяем, является ли элемент файлом
                    if ($request->hasFile("video.$key")) {
                        $file = $request->file("video.$key");
                        if ($file && $file->isValid()) {
                            // Определяем ID объявления (для новых используем временный ID)
                            $adId = $request->id ?: 0; // Используем 0 для новых черновиков
                            $userId = Auth::id();
                            
                            // Генерируем путь с новой структурой
                            $extension = $file->getClientOriginalExtension() ?: 'mp4';
                            $videoPath = PathGenerator::adVideoPath($userId, $adId, $extension);
                            
                            // Сохраняем видео в новую структуру
                            Storage::disk('public')->put($videoPath, file_get_contents($file->getRealPath()));
                            
                            $videoUrls[] = '/storage/' . $videoPath;
                            
                            \Log::info('Video file saved:', [
                                'key' => $key,
                                'path' => $videoPath,
                                'url' => '/storage/' . $videoPath
                            ]);
                        }
                    } elseif (is_string($video)) {
                        // Это URL существующего видео
                        $videoUrls[] = $video;
                        \Log::info('Existing video URL added:', ['url' => $video]);
                    }
                }
            } else {
                \Log::info('No video array found or video is not an array');
            }
        }
        
        // Если передан ID - обновляем существующий черновик или объявление waiting_payment
        if ($request->id) {
            $ad = Ad::where('id', $request->id)
                    ->where('user_id', Auth::id())
                    ->whereIn('status', ['draft', 'waiting_payment'])
                    ->first();
                    
            if ($ad) {
                // Устанавливаем путь к папке пользователя
                $userFolder = PathGenerator::getUserBasePath(Auth::id());
                
                // Сохраняем текущий статус перед обновлением
                $currentStatus = $ad->status;
                
                // Логирование данных перед сохранением
                \Log::info('Updating ad with data:', [
                    'ad_id' => $ad->id,
                    'has_photos' => $request->has('photos'),
                    'has_video' => $request->has('video'),
                    'photo_urls_count' => count($photoUrls),
                    'video_urls_count' => count($videoUrls),
                    'video_urls' => $videoUrls
                ]);
                
                // Извлекаем адрес из geo если он не передан отдельно
                $addressToUpdate = $request->address;
                if (!$addressToUpdate && $request->geo) {
                    $geoData = $request->geo;
                    if (is_string($geoData)) {
                        $geoData = json_decode($geoData, true);
                    }
                    if (is_array($geoData) && isset($geoData['address'])) {
                        $addressToUpdate = $geoData['address'];
                    }
                }
                
                // Обновляем существующий черновик, но сохраняем статус
                $ad->update([
                    'category' => $request->category ?: $ad->category,
                    'title' => $request->title ?: $ad->title,
                    'specialty' => $request->specialty ?: $ad->specialty,
                    'clients' => !empty($request->clients) ? (is_string($request->clients) ? $request->clients : json_encode($request->clients)) : $ad->clients,
                    'work_format' => $request->work_format ?: $ad->work_format,
                    'service_provider' => !empty($request->service_provider) ? (is_string($request->service_provider) ? $request->service_provider : json_encode($request->service_provider)) : $ad->service_provider,
                    'experience' => $request->experience ?: $ad->experience,
                    'description' => $request->description ?: $ad->description,
                    'services' => !empty($request->services) ? (is_string($request->services) ? $request->services : json_encode($request->services)) : $ad->services,
                    'services_additional_info' => $request->services_additional_info ?: $ad->services_additional_info,
                    'features' => !empty($request->features) ? (is_string($request->features) ? $request->features : json_encode($request->features)) : $ad->features,
                    'additional_features' => $request->additional_features ?: $ad->additional_features,
                    'schedule' => !empty($request->schedule) ? (is_string($request->schedule) ? $request->schedule : json_encode($request->schedule)) : $ad->schedule,
                    'schedule_notes' => $request->schedule_notes ?: $ad->schedule_notes,
                    'price' => $request->price ? (float)$request->price : $ad->price,
                    'price_unit' => $request->price_unit ?: $ad->price_unit,
                    'is_starting_price' => $request->has('is_starting_price') ? (bool)$request->is_starting_price : $ad->is_starting_price,
                    'prices' => $request->has('prices') ? (is_string($request->prices) ? $request->prices : json_encode($request->prices)) : $ad->prices,
                    'new_client_discount' => $request->new_client_discount ?: $ad->new_client_discount,
                    'gift' => $request->gift ?: $ad->gift,
                    'age' => $request->age ?: $ad->age,
                    'height' => $request->height ?: $ad->height,
                    'weight' => $request->weight ?: $ad->weight,
                    'breast_size' => $request->breast_size ?: $ad->breast_size,
                    'hair_color' => $request->hair_color ?: $ad->hair_color,
                    'eye_color' => $request->eye_color ?: $ad->eye_color,
                    'nationality' => $request->nationality ?: $ad->nationality,
                    'photos' => $request->has('photos') ? json_encode($photoUrls) : $ad->photos,
                    'video' => $request->has('video') ? json_encode($videoUrls) : $ad->video,
                    'geo' => !empty($request->geo) ? (is_string($request->geo) ? $request->geo : json_encode($request->geo)) : $ad->geo,
                    'address' => $addressToUpdate ?: $ad->address,
                    'travel_price' => $request->travel_price ?: $ad->travel_price,
                    'phone' => $request->phone ?: $ad->phone,
                    'contact_method' => $request->contact_method ?: $ad->contact_method,
                    'whatsapp' => $request->whatsapp ?: $ad->whatsapp,
                    'telegram' => $request->telegram ?: $ad->telegram
                ]);
            }
        } else {
            // Устанавливаем путь к папке пользователя для нового черновика
            $userFolder = PathGenerator::getUserBasePath(Auth::id());
            
            // Логирование для нового черновика
            \Log::info('Creating new draft with data:', [
                'photo_urls_count' => count($photoUrls),
                'video_urls_count' => count($videoUrls),
                'video_urls' => $videoUrls
            ]);
            
            // Извлекаем адрес из geo если он не передан отдельно
            $addressToSave = $request->address;
            if (!$addressToSave && $request->geo) {
                $geoData = $request->geo;
                if (is_string($geoData)) {
                    $geoData = json_decode($geoData, true);
                }
                if (is_array($geoData) && isset($geoData['address'])) {
                    $addressToSave = $geoData['address'];
                }
            }
            
            // Создаем новый черновик (копируем из Backup)
            $ad = Ad::create([
                'user_id' => Auth::id(),
                'category' => $request->category ?: null,
                'title' => $request->title ?: 'Черновик объявления',
                'specialty' => $request->specialty ?: null,
                'clients' => !empty($request->clients) ? (is_string($request->clients) ? $request->clients : json_encode($request->clients)) : json_encode([]),
                'work_format' => $request->work_format ?: null,
                'service_provider' => !empty($request->service_provider) ? (is_string($request->service_provider) ? $request->service_provider : json_encode($request->service_provider)) : json_encode([]),
                'experience' => $request->experience ?: null,
                'description' => $request->description ?: null,
                'services' => !empty($request->services) ? (is_string($request->services) ? $request->services : json_encode($request->services)) : json_encode([]),
                'services_additional_info' => $request->services_additional_info ?: null,
                'features' => !empty($request->features) ? (is_string($request->features) ? $request->features : json_encode($request->features)) : json_encode([]),
                'additional_features' => $request->additional_features ?: null,
                'schedule' => !empty($request->schedule) ? (is_string($request->schedule) ? $request->schedule : json_encode($request->schedule)) : json_encode([]),
                'schedule_notes' => $request->schedule_notes ?: null,
                'price_unit' => $request->price_unit ?: 'service',
                'is_starting_price' => (bool)$request->is_starting_price,
                'prices' => $request->has('prices') ? (is_string($request->prices) ? $request->prices : json_encode($request->prices)) : null,
                'new_client_discount' => $request->new_client_discount ?: null,
                'gift' => $request->gift ?: null,
                'age' => $request->age ?: null,
                'height' => $request->height ?: null,
                'weight' => $request->weight ?: null,
                'breast_size' => $request->breast_size ?: null,
                'hair_color' => $request->hair_color ?: null,
                'eye_color' => $request->eye_color ?: null,
                'nationality' => $request->nationality ?: null,
                'photos' => json_encode($photoUrls),
                'video' => json_encode($videoUrls),
                'geo' => !empty($request->geo) ? (is_string($request->geo) ? $request->geo : json_encode($request->geo)) : json_encode([]),
                'address' => $addressToSave ?: null,
                'travel_price' => $request->travel_price ?: null,
                'phone' => $request->phone ?: null,
                'contact_method' => $request->contact_method ?: 'messages',
                'whatsapp' => $request->whatsapp ?: null,
                'telegram' => $request->telegram ?: null,
                'status' => 'draft'
            ]);
        }

        // Определяем URL для редиректа в зависимости от статуса
        $redirectUrl = '/profile/items/draft/all';
        
        // Для существующих объявлений проверяем исходный статус
        if ($request->id) {
            // Загружаем объявление заново, чтобы получить актуальный статус
            $checkAd = Ad::find($request->id);
            if ($checkAd && $checkAd->status === AdStatus::WAITING_PAYMENT) {
                $redirectUrl = '/profile/items/inactive/all';
            }
        }
        
        // Проверяем тип запроса
        if ($request->header('X-Inertia')) {
            \Log::info('Inertia request detected, redirecting to: ' . $redirectUrl);
            // Для Inertia запросов возвращаем redirect
            return redirect($redirectUrl)->with('success', 'Изменения сохранены!');
        }
        
        // Для обычных AJAX запросов возвращаем JSON
        return response()->json([
            'success' => true,
            'message' => 'Изменения сохранены!',
            'redirect' => $redirectUrl,
            'ad_id' => $ad->id ?? null
        ]);
    }
    
    /**
     * Показать форму редактирования
     */
    public function edit(Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            abort(403, 'Нет доступа к редактированию этого объявления');
        }

        // Загружаем все данные объявления включая JSON поля
        $adData = $ad->toArray();
        
        // Преобразуем JSON поля в массивы, если они строки
        $jsonFields = ['clients', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours', 'services', 'features', 'schedule', 
                      'additional_services', 'geo', 'media_settings', 'prices'];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $adData[$field] = json_decode($adData[$field], true) ?? [];
            }
        }

        return Inertia::render('EditAd', [
            'ad' => $adData
        ]);
    }

    /**
     * Обновить объявление
     */
    public function update(Request $request, Ad $ad)
    {
        \Log::info('🚀 AdController::update СТАРТ', [
            'ad_id' => $ad->id,
            'ad_status' => $ad->status,
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'all_data' => $request->all()
        ]);
        
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            \Log::error('❌ Нет доступа к объявлению', [
                'ad_id' => $ad->id,
                'auth_user' => auth()->id(),
                'ad_owner' => $ad->user_id
            ]);
            abort(403, 'Нет доступа к редактированию этого объявления');
        }

        // Преобразуем JSON строки в массивы для совместимости с FormData
        $requestData = $request->all();
        $jsonFields = ['clients', 'service_provider', 'features', 'services', 
                      'schedule', 'additional_services', 'geo', 'media_settings', 'custom_travel_areas', 'prices', 'is_starting_price'];
        
        foreach ($jsonFields as $field) {
            if (isset($requestData[$field]) && is_string($requestData[$field])) {
                $decoded = json_decode($requestData[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $requestData[$field] = $decoded;
                    \Log::info("Декодирован JSON для поля {$field}", ['value' => $decoded]);
                }
            }
        }
        
        // Специальная обработка для video - преобразуем массив в строку
        if (isset($requestData['video']) && is_array($requestData['video'])) {
            $requestData['video'] = json_encode($requestData['video']);
            \Log::info("Преобразован video массив в JSON строку", ['value' => $requestData['video']]);
        }
        
        // Заменяем данные запроса на обработанные
        $request->merge($requestData);
        
        \Log::info('📦 Обработанные данные', ['processed_data' => $requestData]);

        // Упрощенная валидация для активных объявлений (как в updateDraft)
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'specialty' => 'nullable|string',
            'clients' => 'nullable|array',
            'work_format' => 'nullable|string',
            'service_provider' => 'nullable|array',
            'experience' => 'nullable|string',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string',
            'is_starting_price' => 'nullable', // Может быть массивом или строкой
            'discount' => 'nullable|numeric|min:0|max:100',
            'gift' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string',
            'contact_method' => 'nullable|string|in:any,calls,messages',
            'photos' => 'nullable|array',
            'video' => 'nullable|string',
            'show_photos_in_gallery' => 'nullable|boolean',
            'allow_download_photos' => 'nullable|boolean',
            'watermark_photos' => 'nullable|boolean',
            // Добавляем поля которые могут прийти из FormData
            'services' => 'nullable', // Может быть массивом или строкой JSON
            'features' => 'nullable|array', 
            'schedule' => 'nullable|array',
            'additional_services' => 'nullable|array',
            'geo' => 'nullable', // Может быть массивом или строкой JSON
            'media_settings' => 'nullable|array',
            'custom_travel_areas' => 'nullable|array',
            'prices' => 'nullable' // Может быть массивом или строкой JSON
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $validated = $validator->validated();
        
        // Обрабатываем специальные поля
        $validated['clients'] = json_encode($validated['clients'] ?? []);
        $validated['service_provider'] = json_encode($validated['service_provider'] ?? []);
        
        // Обрабатываем загруженные фотографии (как в методе store)
        $photoUrls = [];
        
        // Проверяем, есть ли строка '[]' - значит все фото удалили
        if ($request->photos === '[]') {
            $photoUrls = [];
        } else {
            // Обрабатываем массив фотографий (могут быть и файлы, и URL-строки)
            if ($request->photos && is_array($request->photos)) {
                foreach ($request->photos as $key => $photo) {
                    // Проверяем, является ли элемент файлом
                    if ($request->hasFile("photos.$key")) {
                        $file = $request->file("photos.$key");
                        if ($file && $file->isValid()) {
                            // Используем реальный ID объявления для обновления
                            $adId = $ad->id;
                            $userId = Auth::id();
                            
                            // Генерируем путь с новой структурой
                            $extension = $file->getClientOriginalExtension() ?: 'jpg';
                            $originalPath = PathGenerator::adPhotoPath($userId, $adId, $extension, 'original');
                            
                            // Оптимизируем изображение перед сохранением
                            $optimizedFile = SimpleImageOptimizer::optimize($file);
                            
                            // Сохраняем оригинал в новую структуру
                            Storage::disk('public')->put($originalPath, file_get_contents($optimizedFile->getRealPath()));
                            
                            // Пробуем конвертировать в WebP
                            $fullPath = storage_path('app/public/' . $originalPath);
                            $webpPath = SimpleImageOptimizer::convertToWebP($fullPath);
                            if ($webpPath && $webpPath !== $fullPath) {
                                $originalPath = str_replace(storage_path('app/public/'), '', $webpPath);
                            }
                            
                            $photoUrls[] = '/storage/' . $originalPath;
                        }
                    } elseif (is_string($photo)) {
                        // Это URL существующего фото
                        $photoUrls[] = $photo;
                    }
                }
            }
        }
        
        // Теперь применяем фильтрацию пустых объектов к результату
        $cleanPhotos = [];
        foreach ($photoUrls as $photo) {
            if (!empty($photo)) {
                $cleanPhotos[] = $photo;
            }
        }
        
        // Заменяем логику фильтрации на обработанные файлы
        $validated['photos'] = json_encode($cleanPhotos);
        
        
        $validated['show_photos_in_gallery'] = $request->boolean('show_photos_in_gallery', true);
        $validated['allow_download_photos'] = $request->boolean('allow_download_photos', false);
        $validated['watermark_photos'] = $request->boolean('watermark_photos', true);
        
        $ad->update($validated);

        // Определяем URL для редиректа в зависимости от статуса
        $redirectUrl = match($ad->status) {
            'draft' => '/profile/items/draft/all',
            'waiting_payment' => '/profile/items/inactive/all',
            'active' => '/profile/items/active/all',
            default => '/profile/items/inactive/all'
        };

        \Log::info('✅ AdController::update УСПЕХ', [
            'ad_id' => $ad->id,
            'redirect_url' => $redirectUrl
        ]);
        
        return redirect($redirectUrl)->with('success', 'Объявление обновлено!');
    }

    /**
     * Удалить объявление
     */
    public function destroy(Ad $ad)
    {
        $this->authorize('delete', $ad);

        try {
            $this->adService->delete($ad);
            
            return redirect()->route('profile.items.active')
                ->with('success', 'Объявление удалено');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при удалении: ' . $e->getMessage()]);
        }
    }

    /**
     * Показать объявление для просмотра/редактирования
     */
    public function show(Ad $ad)
    {
        // Проверяем, что пользователь может просматривать это объявление
        $user = Auth::user();
        if (!$user || $ad->user_id !== $user->id) {
            abort(403, 'Нет доступа к объявлению');
        }

        // Загружаем связанные данные
        $ad->load(['user']);

        // Если это черновик, перенаправляем на специальную страницу черновика
        if ($ad->status === 'draft') {
            return redirect()->route('ads.draft.show', $ad);
        }

        // Для опубликованных объявлений показываем страницу просмотра
        return Inertia::render('Ads/Show', [
            'ad' => $ad,
            'isOwner' => true
        ]);
    }

    /**
     * Показать черновик объявления (рендерим форму редактирования напрямую)
     */
    public function showDraft(Ad $ad)
    {
        // Проверяем права доступа
        $user = Auth::user();
        if (!$user || $ad->user_id !== $user->id) {
            abort(403, 'Нет доступа к черновику');
        }

        // Разрешаем просмотр любых объявлений пользователя
        // (раньше было ограничение только для черновиков)

        // Подготавливаем данные для отображения черновика
        $adData = $ad->toArray();
        
        // Преобразуем JSON поля в массивы, если они строки
        $jsonFields = ['clients', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours', 'services', 'features', 'schedule', 
                      'additional_services', 'geo', 'media_settings', 'prices'];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $decoded = json_decode($adData[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $adData[$field] = $decoded;
                } else {
                    // Если JSON невалидный, логируем ошибку
                    \Log::warning("Failed to decode JSON for field {$field}", [
                        'value' => $adData[$field],
                        'error' => json_last_error_msg()
                    ]);
                    $adData[$field] = [];
                }
            } elseif (!isset($adData[$field])) {
                // Если поле отсутствует, устанавливаем пустой массив
                $adData[$field] = [];
            }
        }

        // Логирование данных черновика
        \Log::info('Draft Show data:', [
            'ad_id' => $ad->id,
            'video_raw' => $ad->video,
            'video_decoded' => $adData['video'] ?? null
        ]);

        // Рендерим страницу просмотра черновика
        return Inertia::render('Draft/Show', [
            'ad' => $adData
        ]);
    }

    /**
     * Обновить черновик
     */
    public function updateDraft(Request $request, Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            abort(403, 'Нет доступа к редактированию этого объявления');
        }

        // Разрешаем редактирование всех объявлений пользователя
        // (раньше было ограничение только для черновиков)

        try {
            // Принимаем любые данные без строгой валидации
            $data = $request->all();
            
            // Сохраняем оригинальный статус объявления
            $originalStatus = $ad->status;
            $data['status'] = $originalStatus;
            
            \Log::info('Обновление объявления', [
                'ad_id' => $ad->id,
                'status' => $originalStatus,
                'data_keys' => array_keys($data)
            ]);
            
            $ad = $this->adService->updateDraft($ad, $data);
            
            \Log::info('Объявление обновлено успешно', ['ad_id' => $ad->id]);
            
            // Для Inertia запросов делаем редирект
            if ($request->header('X-Inertia')) {
                \Log::info('Объявление сохранено, перенаправление в личный кабинет');
                
                // Перенаправляем в соответствующую вкладку в зависимости от статуса
                $redirectRoute = $originalStatus === 'draft' ? 'profile.items.draft' : 'profile.items.active';
                $message = $originalStatus === 'draft' ? 'Черновик успешно сохранен' : 'Объявление успешно обновлено';
                
                return redirect()->route($redirectRoute)
                    ->with('success', $message);
            }
            
            return response()->json([
                'success' => true,
                'message' => $originalStatus === 'draft' ? 'Черновик сохранен' : 'Объявление обновлено'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка обновления объявления', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Ошибка при обновлении объявления']);
        }
    }

    /**
     * Получить данные объявления для API
     */
    public function getData(Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            return response()->json(['error' => 'Нет доступа'], 403);
        }

        // Загружаем все данные объявления включая JSON поля
        $adData = $ad->toArray();
        
        // Преобразуем JSON поля в массивы, если они строки
        $jsonFields = ['clients', 'service_provider', 'is_starting_price', 
                      'photos', 'videos', 'media_settings', 'geo', 'custom_travel_areas', 
                      'schedule', 'services', 'features', 'additional_services'];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $adData[$field] = json_decode($adData[$field], true) ?? [];
            }
        }

        return response()->json($adData);
    }

}