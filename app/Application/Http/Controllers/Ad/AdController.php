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
                'clients', 'service_location', 'work_format', 'service_provider',
                'experience', 'services', 'services_additional_info', 'features',
                'additional_features', 'schedule', 'schedule_notes', 'price_unit',
                'is_starting_price', 'main_service_name', 'main_service_price',
                'main_service_price_unit', 'additional_services', 'age', 'height', 'weight',
                'breast_size', 'hair_color', 'eye_color', 'nationality', 'new_client_discount',
                'gift', 'photos', 'videos', 'media_settings', 'geo', 'address',
                'travel_area', 'custom_travel_areas', 'travel_radius', 'travel_price',
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
                            $optimizedFile = \App\Helpers\SimpleImageOptimizer::optimize($file);
                            
                            // Сохраняем оригинал в новую структуру
                            Storage::disk('public')->put($originalPath, file_get_contents($optimizedFile->getRealPath()));
                            
                            // Пробуем конвертировать в WebP
                            $fullPath = storage_path('app/public/' . $originalPath);
                            $webpPath = \App\Helpers\SimpleImageOptimizer::convertToWebP($fullPath);
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
        
        // Обрабатываем загруженные видео
        $videoUrls = [];
        
        // Проверяем, есть ли строка '[]' - значит все видео удалили
        if ($request->video === '[]') {
            $videoUrls = [];
        } else {
            // Обрабатываем массив видео (могут быть и файлы, и URL-строки)
            if ($request->video && is_array($request->video)) {
                foreach ($request->video as $key => $video) {
                    // Проверяем, является ли элемент файлом
                    if ($request->hasFile("video.$key")) {
                        $file = $request->file("video.$key");
                        if ($file && $file->isValid()) {
                            // Определяем ID объявления
                            $adId = $request->id ?: 0;
                            $userId = Auth::id();
                            
                            // Генерируем путь с новой структурой
                            $extension = $file->getClientOriginalExtension() ?: 'mp4';
                            $videoPath = PathGenerator::adVideoPath($userId, $adId, $extension);
                            
                            // Сохраняем видео в новую структуру
                            Storage::disk('public')->put($videoPath, file_get_contents($file->getRealPath()));
                            
                            $videoUrls[] = '/storage/' . $videoPath;
                        }
                    } elseif (is_string($video)) {
                        // Это URL существующего видео
                        $videoUrls[] = $video;
                    }
                }
            }
        }
        
        // Если передан ID - обновляем существующий черновик
        if ($request->id) {
            $ad = Ad::where('id', $request->id)
                    ->where('user_id', Auth::id())
                    ->where('status', 'draft')
                    ->first();
                    
            if ($ad) {
                // Устанавливаем путь к папке пользователя
                $userFolder = PathGenerator::getUserBasePath(Auth::id());
                
                // Обновляем существующий черновик
                $ad->update([
                    'category' => $request->category ?: $ad->category,
                    'title' => $request->title ?: $ad->title,
                    'specialty' => $request->specialty ?: $ad->specialty,
                    'clients' => !empty($request->clients) ? (is_string($request->clients) ? $request->clients : json_encode($request->clients)) : $ad->clients,
                    'service_location' => !empty($request->service_location) ? (is_string($request->service_location) ? $request->service_location : json_encode($request->service_location)) : $ad->service_location,
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
                    'prices' => !empty($request->prices) ? (is_string($request->prices) ? $request->prices : json_encode($request->prices)) : $ad->prices,
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
                    'address' => $request->address ?: $ad->address,
                    'travel_area' => $request->travel_area ?: $ad->travel_area,
                    'travel_price' => $request->travel_price ?: $ad->travel_price,
                    'phone' => $request->phone ?: $ad->phone,
                    'contact_method' => $request->contact_method ?: $ad->contact_method,
                    'whatsapp' => $request->whatsapp ?: $ad->whatsapp,
                    'telegram' => $request->telegram ?: $ad->telegram,
                    'user_folder' => $userFolder,
                    'media_paths' => json_encode([
                        'photos' => $photoUrls,
                        'videos' => $videoUrls,
                        'migrated_at' => now()
                    ])
                ]);
            }
        } else {
            // Устанавливаем путь к папке пользователя для нового черновика
            $userFolder = PathGenerator::getUserBasePath(Auth::id());
            
            // Создаем новый черновик (копируем из Backup)
            $ad = Ad::create([
                'user_id' => Auth::id(),
                'category' => $request->category ?: null,
                'title' => $request->title ?: 'Черновик объявления',
                'specialty' => $request->specialty ?: null,
                'clients' => !empty($request->clients) ? (is_string($request->clients) ? $request->clients : json_encode($request->clients)) : json_encode([]),
                'service_location' => !empty($request->service_location) ? (is_string($request->service_location) ? $request->service_location : json_encode($request->service_location)) : json_encode([]),
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
                'price' => $request->price ? (float)$request->price : null,
                'price_unit' => $request->price_unit ?: 'service',
                'is_starting_price' => (bool)$request->is_starting_price,
                'prices' => !empty($request->prices) ? (is_string($request->prices) ? $request->prices : json_encode($request->prices)) : null,
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
                'address' => $request->address ?: null,
                'travel_area' => $request->travel_area ?: null,
                'travel_price' => $request->travel_price ?: null,
                'phone' => $request->phone ?: null,
                'contact_method' => $request->contact_method ?: 'messages',
                'whatsapp' => $request->whatsapp ?: null,
                'telegram' => $request->telegram ?: null,
                'status' => 'draft',
                'user_folder' => $userFolder,
                'media_paths' => json_encode([
                    'photos' => $photoUrls,
                    'videos' => $videoUrls,
                    'created_at' => now()
                ])
            ]);
        }

        // Всегда возвращаем редирект для Inertia (как в Backup)
        return redirect('/profile/items/draft/all')->with('success', 'Черновик сохранен!');
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
        $jsonFields = ['clients', 'service_location', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours', 'services', 'features', 'schedule', 
                      'additional_services', 'geo', 'media_settings'];
        
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
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            abort(403, 'Нет доступа к редактированию этого объявления');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'specialty' => 'required|string',
            'clients' => 'array',
            'service_location' => 'required|array|min:1',
            'work_format' => 'required|string',
            'service_provider' => 'array',
            'experience' => 'required|string',
            'description' => 'required|string|min:50',
            'price' => 'required|numeric|min:0',
            'price_unit' => 'required|string',
            'is_starting_price' => 'array',
            'discount' => 'nullable|numeric|min:0|max:100',
            'gift' => 'nullable|string|max:500',
            'address' => 'required|string|max:500',
            'travel_area' => 'required|string',
            'phone' => 'required|string',
            'contact_method' => 'required|string|in:any,calls,messages',
            'photos' => 'nullable|array',
            'video' => 'nullable|string',
            'show_photos_in_gallery' => 'boolean',
            'allow_download_photos' => 'boolean',
            'watermark_photos' => 'boolean'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $validated = $validator->validated();
        
        // Обрабатываем специальные поля
        $validated['clients'] = json_encode($validated['clients'] ?? []);
        $validated['service_location'] = json_encode($validated['service_location']);
        $validated['service_provider'] = json_encode($validated['service_provider'] ?? []);
        $validated['photos'] = is_array($validated['photos']) ? json_encode($validated['photos']) : json_encode([]);
        $validated['show_photos_in_gallery'] = $request->boolean('show_photos_in_gallery', true);
        $validated['allow_download_photos'] = $request->boolean('allow_download_photos', false);
        $validated['watermark_photos'] = $request->boolean('watermark_photos', true);
        
        $ad->update($validated);

        // Определяем URL для редиректа в зависимости от статуса
        $redirectUrl = $ad->status === 'draft' ? '/profile/items/draft/all' : '/profile/items/active/all';

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

        // Проверяем что это действительно черновик
        if ($ad->status !== 'draft') {
            return redirect()->route('ads.show', $ad);
        }

        // Для черновиков рендерим форму редактирования напрямую
        // Используем ту же логику что и в методе edit()
        $adData = $ad->toArray();
        
        // Преобразуем JSON поля в массивы, если они строки
        $jsonFields = ['clients', 'service_location', 'service_provider', 'is_starting_price', 
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
     * Обновить черновик
     */
    public function updateDraft(Request $request, Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            abort(403, 'Нет доступа к редактированию этого объявления');
        }

        // Проверяем что это действительно черновик
        if ($ad->status !== 'draft') {
            return back()->withErrors(['error' => 'Можно обновлять только черновики']);
        }

        try {
            // Для черновика принимаем любые данные без строгой валидации
            $data = $request->all();
            $data['status'] = 'draft'; // Убеждаемся что статус остается draft
            
            \Log::info('Обновление черновика', [
                'ad_id' => $ad->id,
                'data_keys' => array_keys($data)
            ]);
            
            $ad = $this->adService->updateDraft($ad, $data);
            
            \Log::info('Черновик обновлен успешно', ['ad_id' => $ad->id]);
            
            // Для Inertia запросов делаем редирект
            if ($request->header('X-Inertia')) {
                \Log::info('Черновик сохранен, перенаправление в личный кабинет');
                
                // Перенаправляем в личный кабинет во вкладку черновики
                return redirect()->route('profile.items.draft')
                    ->with('success', 'Черновик успешно сохранен');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Черновик сохранен'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка обновления черновика', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Ошибка при обновлении черновика']);
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
        $jsonFields = ['clients', 'service_location', 'service_provider', 'is_starting_price', 
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