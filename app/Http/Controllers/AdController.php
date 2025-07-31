<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ad;
use App\Services\AdService;
use App\Http\Requests\CreateAdRequest;
use App\Http\Requests\SaveAdDraftRequest;
use App\Http\Requests\UpdateAdRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdController extends Controller
{
    private AdService $adService;
    
    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }
    
    /**
     * Разрешенные поля для массового присвоения
     */
    private const ALLOWED_FIELDS = [
        'category', 'title', 'specialty', 'clients', 'service_location', 
        'outcall_locations', 'taxi_option', 'work_format', 'service_provider',
        'experience', 'education_level', 'features', 'additional_features',
        'description', 'price', 'price_unit', 'is_starting_price',
        'contacts_per_hour', 'discount', 'gift', 'address', 'travel_area',
        'phone', 'contact_method', 'whatsapp', 'telegram', 'age', 'height',
        'weight', 'breast_size', 'hair_color', 'eye_color', 'appearance',
        'nationality', 'has_girlfriend', 'services', 'services_additional_info',
        'schedule', 'schedule_notes', 'photos', 'video', 'show_photos_in_gallery',
        'allow_download_photos', 'watermark_photos', 'new_client_discount'
    ];
    /**
     * Страница создания объявления в стиле Avito (/additem)
     */
    public function addItem()
    {
        return Inertia::render('AddItem');
    }



    /**
     * Сохранить объявление (как у Avito - один метод для черновика и публикации)
     */
    public function store(CreateAdRequest $request)
    {
        try {
            // Используем сервис для создания объявления (данные уже валидированы)
            $ad = $this->adService->create($request->validated(), Auth::user());
            
            // Пытаемся сразу опубликовать (как было в оригинале)
            $this->adService->publish($ad);
            
            return redirect()->route('dashboard')->with('success', 'Объявление успешно создано и опубликовано!');
            
        } catch (\InvalidArgumentException $e) {
            // Если не удалось опубликовать, сохраняем как черновик
            return redirect()->route('dashboard')->with('warning', 'Объявление сохранено как черновик. ' . $e->getMessage());
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при создании объявления: ' . $e->getMessage()]);
        }
    }

    /**
     * Сохранить черновик объявления
     */
    public function storeDraft(SaveAdDraftRequest $request)
    {
        try {
            // Проверяем, обновляем ли существующий черновик
            $existingAd = null;
            $requestId = $request->input('id');
            
            if ($requestId && $requestId !== 'null' && $requestId !== '') {
                $adId = is_numeric($requestId) ? (int)$requestId : null;
                
                if ($adId) {
                    $existingAd = Ad::where('id', $adId)
                                   ->where('user_id', Auth::id())
                                   ->where('status', 'draft')
                                   ->first();
                }
            }
            
            // Используем сервис для сохранения черновика (данные уже валидированы и обработаны)
            $ad = $this->adService->saveDraft($request->validated(), Auth::user(), $existingAd);
            
            $message = $existingAd ? 'Черновик обновлен!' : 'Черновик сохранен!';
            
            return redirect('/profile/items/draft/all')->with('success', $message);
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при сохранении черновика: ' . $e->getMessage()]);
        }
    }

    /**
     * Опубликовать объявление (с валидацией обязательных полей)
     */
    public function publish(Request $request)
    {
        // Валидация обязательных полей для публикации
        $validator = Validator::make($request->only(self::ALLOWED_FIELDS), [
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'specialty' => 'required|string',
            'clients' => 'required|array|min:1',
            'service_location' => 'required|array|min:1',
            'work_format' => 'required|string',
            'experience' => 'required|string|in:3260137,3260142,3260146,3260149,3260152',
            'education_level' => 'nullable|string|in:2,3,4,5,6,7',
            'description' => 'required|string|min:50',
            'price' => 'required|numeric|min:0',
            'phone' => 'required|string',
            // Физические параметры (необязательные)
            'age' => 'nullable|integer|min:18|max:65',
            'height' => 'nullable|integer|min:140|max:200',
            'weight' => 'nullable|integer|min:40|max:120',
            'breast_size' => 'nullable|integer|min:1|max:7',
            'hair_color' => 'nullable|string|max:50',
            'eye_color' => 'nullable|string|max:50',
            'appearance' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
        ], [
            'title.required' => 'Название объявления обязательно для заполнения',
            'specialty.required' => 'Специальность или сфера обязательна для заполнения',
            'clients.required' => 'Выберите хотя бы одну категорию клиентов',
            'clients.min' => 'Выберите хотя бы одну категорию клиентов',
            'service_location.required' => 'Укажите где вы оказываете услуги',
            'service_location.min' => 'Укажите хотя бы одно место оказания услуг',
            'work_format.required' => 'Укажите формат работы',
            'experience.required' => 'Укажите опыт работы',
            'description.required' => 'Описание услуги обязательно для заполнения',
            'description.min' => 'Описание должно содержать не менее 50 символов',
            'price.required' => 'Укажите стоимость услуги',
            'phone.required' => 'Укажите телефон для связи',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Заполните все обязательные поля'
            ], 422);
        }

        // Создаем или обновляем объявление
        $ad = Ad::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'id' => $request->id ?? null
            ],
            [
                'category' => $request->category,
                'title' => $request->title,
                'description' => $request->description ?: '',
                'specialty' => $request->specialty,
                'clients' => is_array($request->clients) ? json_encode($request->clients) : '[]',
                'service_location' => is_array($request->service_location) ? json_encode($request->service_location) : '[]',
                'outcall_locations' => is_array($request->outcall_locations) ? json_encode($request->outcall_locations) : '[]',
                'taxi_option' => $request->taxi_option ?: null,
                'work_format' => $request->work_format,
                'service_provider' => is_array($request->service_provider) ? json_encode($request->service_provider) : '[]',
                'experience' => $request->experience,
                'education_level' => $request->education_level,
                'features' => !empty($request->features) ? json_encode($request->features) : json_encode([]),
                'additional_features' => $request->additional_features ?: null,
                'price' => $request->price,
                'price_unit' => $request->price_unit ?: 'session',
                'is_starting_price' => is_array($request->is_starting_price) ? json_encode($request->is_starting_price) : '[]',
                'pricing_data' => !empty($request->pricing_data) ? json_encode($request->pricing_data) : null,
                'contacts_per_hour' => $request->contacts_per_hour ?: null,
                'discount' => $request->discount ?: null,
                'gift' => $request->gift ?: null,
                // Услуги
                'services' => !empty($request->services) ? json_encode($request->services) : json_encode((object)[]),
                'services_additional_info' => $request->services_additional_info ?: null,
                'address' => $request->address ?: null,
                'travel_area' => $request->travel_area ?: null,
                'phone' => $request->phone,
                'contact_method' => $request->contact_method ?: 'messages',
                // Физические параметры
                'age' => $request->age ?: null,
                'height' => $request->height ?: null,
                'weight' => $request->weight ?: null,
                'breast_size' => $request->breast_size ?: null,
                'hair_color' => $request->hair_color ?: null,
                'eye_color' => $request->eye_color ?: null,
                'appearance' => $request->appearance ?: null,
                'nationality' => $request->nationality ?: null,
                'has_girlfriend' => $request->boolean('has_girlfriend', false),
                'photos' => is_array($request->photos) ? json_encode($request->photos) : json_encode([]),
                // Видео - сохраняем только ID, если передан
                'video' => $request->video_id ? json_encode(['id' => $request->video_id]) : null,
                'show_photos_in_gallery' => $request->boolean('show_photos_in_gallery', true),
                'allow_download_photos' => $request->boolean('allow_download_photos', false),
                'watermark_photos' => $request->boolean('watermark_photos', true),
                'status' => 'waiting_payment' // Статус для ожидания оплаты
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Объявление готово к публикации',
            'id' => $ad->id,
            'redirect' => route('select-plan', ['ad' => $ad->id])
        ]);
    }

    /**
     * Показать форму редактирования объявления
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
        $jsonFields = ['clients', 'service_location', 'outcall_locations', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours', 'features', 'pricing_data', 'services', 'schedule'];
        
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
        $jsonFields = ['clients', 'service_location', 'outcall_locations', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours', 'features', 'pricing_data', 'services', 'schedule', 'payment_methods'];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $adData[$field] = json_decode($adData[$field], true) ?? [];
            }
        }

        // Убеждаемся, что price всегда строка
        if (isset($adData['price'])) {
            $adData['price'] = (string) $adData['price'];
        }
        
        // Преобразуем payment_methods в правильный массив
        if (!empty($adData['payment_methods'])) {
            // Если это массив - убеждаемся что индексы правильные
            if (is_array($adData['payment_methods'])) {
                // Проверяем не объект ли это {cash: true, transfer: false}
                if (isset($adData['payment_methods']['cash']) || isset($adData['payment_methods']['transfer'])) {
                    // Старый объект формата - преобразуем в массив
                    $methods = [];
                    if (!empty($adData['payment_methods']['cash'])) $methods[] = 'cash';
                    if (!empty($adData['payment_methods']['transfer'])) $methods[] = 'transfer';
                    $adData['payment_methods'] = count($methods) > 0 ? $methods : ['cash'];
                } else {
                    // Уже массив - очищаем от пустых значений и перестраиваем индексы
                    $adData['payment_methods'] = array_values(array_filter($adData['payment_methods'], function($value) {
                        return !empty($value) && in_array($value, ['cash', 'transfer']);
                    }));
                    if (empty($adData['payment_methods'])) {
                        $adData['payment_methods'] = [];
                    }
                }
            }
            else {
                $adData['payment_methods'] = [];
            }
        } else {
            $adData['payment_methods'] = [];
        }

        return response()->json($adData);
    }

    /**
     * Обновить объявление
     */
    public function update(UpdateAdRequest $request, Ad $ad)
    {
        try {
            // Используем сервис для обновления объявления (данные уже валидированы и права проверены в Request)
            $ad = $this->adService->update($ad, $request->validated());
            
            return redirect()->route('profile.dashboard')->with('success', 'Объявление обновлено!');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при обновлении объявления: ' . $e->getMessage()]);
        }
    }

    /**
     * Удалить объявление
     */
    public function destroy(Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            abort(403, 'Нет доступа к удалению этого объявления');
        }

        try {
            $ad->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Объявление успешно удалено'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при удалении объявления: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Переключить статус объявления
     */
    public function toggleStatus(Request $request, Ad $ad)
    {
        // Проверяем права доступа
        if (auth()->id() !== $ad->user_id) {
            abort(403, 'Нет доступа к изменению статуса этого объявления');
        }

        $request->validate([
            'status' => 'required|in:draft,active,paused,archived,inactive'
        ]);

        try {
            $ad->update(['status' => $request->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Статус объявления изменен',
                'status' => $request->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при изменении статуса: ' . $e->getMessage()
            ], 500);
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
     * Показать черновик объявления (как на Авито)
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

        // Загружаем связанные данные
        $ad->load(['user']);

        // Получаем данные с правильным преобразованием JSON полей
        $adData = $ad->toArray();
        
        // Убеждаемся что JSON поля декодированы в массивы
        $jsonFields = ['clients', 'service_location', 'outcall_locations', 'service_provider', 'photos', 'video', 'services', 'pricing_data', 'features', 'schedule', 'payment_methods'];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $decoded = json_decode($adData[$field], true);
                $adData[$field] = is_array($decoded) ? $decoded : [];
            } elseif (!isset($adData[$field])) {
                $adData[$field] = [];
            }
        }

        // Убеждаемся, что price всегда строка
        if (isset($adData['price'])) {
            $adData['price'] = (string) $adData['price'];
        }
        
        // Преобразуем payment_methods в правильный массив
        if (!empty($adData['payment_methods'])) {
            // Если это массив - убеждаемся что индексы правильные
            if (is_array($adData['payment_methods'])) {
                // Проверяем не объект ли это {cash: true, transfer: false}
                if (isset($adData['payment_methods']['cash']) || isset($adData['payment_methods']['transfer'])) {
                    // Старый объект формата - преобразуем в массив
                    $methods = [];
                    if (!empty($adData['payment_methods']['cash'])) $methods[] = 'cash';
                    if (!empty($adData['payment_methods']['transfer'])) $methods[] = 'transfer';
                    $adData['payment_methods'] = count($methods) > 0 ? $methods : ['cash'];
                } else {
                    // Уже массив - очищаем от пустых значений и перестраиваем индексы
                    $adData['payment_methods'] = array_values(array_filter($adData['payment_methods'], function($value) {
                        return !empty($value) && in_array($value, ['cash', 'transfer']);
                    }));
                    if (empty($adData['payment_methods'])) {
                        $adData['payment_methods'] = [];
                    }
                }
            }
            else {
                $adData['payment_methods'] = [];
            }
        } else {
            $adData['payment_methods'] = [];
        }

        // Показываем страницу черновика
        return Inertia::render('Draft/Show', [
            'ad' => $adData,
            'isOwner' => true
        ]);
    }

    /**
     * Удалить черновик объявления
     */
    public function deleteDraft(Ad $ad)
    {
        // Проверяем права доступа
        $user = Auth::user();
        if (!$user || $ad->user_id !== $user->id) {
            abort(403, 'Нет доступа к черновику');
        }

        // Проверяем что это действительно черновик
        if ($ad->status !== 'draft') {
            return redirect()->route('ads.show', $ad)->with('error', 'Можно удалять только черновики');
        }

        // Удаляем черновик
        $ad->delete();

        // Перенаправляем на страницу "Мои объявления"
        return redirect()->route('my-ads.index')
            ->with('success', 'Черновик успешно удален');
    }

    /**
     * Загрузить видео для объявления
     */
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,avi,mov|max:102400', // 100MB
        ]);

        try {
            // Создаем уникальное имя файла
            $video = $request->file('video');
            $filename = 'video_' . time() . '_' . Str::random(10) . '.' . $video->getClientOriginalExtension();
            
            // Сохраняем в публичную папку
            $path = $video->storeAs('public/videos', $filename);
            
            // Создаем URL для доступа к видео
            $videoUrl = asset('storage/videos/' . $filename);
            
            // Возвращаем информацию о загруженном видео
            return response()->json([
                'success' => true,
                'video' => [
                    'id' => 'video_' . time() . '_' . Str::random(5),
                    'filename' => $filename,
                    'path' => $path,
                    'url' => $videoUrl,
                    'size' => $video->getSize(),
                    'type' => $video->getMimeType(),
                    'name' => $video->getClientOriginalName()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка загрузки видео: ' . $e->getMessage()
            ], 500);
        }
    }
} 