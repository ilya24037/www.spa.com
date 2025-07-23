<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdController extends Controller
{
    /**
     * Показать форму создания объявления
     */
    public function create()
    {
        return Inertia::render('AddService');
    }

    /**
     * Сохранить объявление
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string',
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
            'contact_method' => 'required|string|in:any,calls,messages'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $ad = Ad::create([
            'user_id' => Auth::id(),
            'category' => $request->category,
            'title' => $request->title,
            'specialty' => $request->specialty,
            'clients' => json_encode($request->clients ?? []),
            'service_location' => json_encode($request->service_location),
            'work_format' => $request->work_format,
            'service_provider' => json_encode($request->service_provider ?? []),
            'experience' => $request->experience,
            'description' => $request->description,
            'price' => $request->price,
            'price_unit' => $request->price_unit,
            'is_starting_price' => $request->is_starting_price ? true : false,
            'discount' => $request->discount,
            'gift' => $request->gift,
            'photos' => $request->photos ?? [],
            'video' => $request->video,
            'show_photos_in_gallery' => $request->show_photos_in_gallery ?? [],
            'allow_download_photos' => $request->allow_download_photos ?? [],
            'watermark_photos' => $request->watermark_photos ?? [],
            'address' => $request->address,
            'travel_area' => $request->travel_area,
            'phone' => $request->phone,
            'contact_method' => $request->contact_method,
            'status' => 'active'
        ]);

        return redirect()->route('dashboard')->with('success', 'Объявление успешно создано!');
    }

    /**
     * Сохранить черновик объявления
     */
    public function storeDraft(Request $request)
    {
        // Для черновика не валидируем ничего - принимаем любые данные
        // Черновик может быть полностью пустым

        $ad = Ad::create([
            'user_id' => Auth::id(),
            'category' => $request->category ?: null,
            'title' => $request->title ?: 'Черновик объявления',
            'specialty' => $request->specialty ?: null,
            'clients' => !empty($request->clients) ? json_encode($request->clients) : json_encode([]),
            'service_location' => !empty($request->service_location) ? json_encode($request->service_location) : json_encode([]),
            'work_format' => $request->work_format ?: null,
            'service_provider' => !empty($request->service_provider) ? json_encode($request->service_provider) : json_encode([]),
            'experience' => $request->experience ?: null,
            'description' => $request->description ?: null,
            'price' => $request->price ? (float)$request->price : null,
            'price_unit' => $request->price_unit ?: 'service',
            'is_starting_price' => $request->is_starting_price ? true : false,
            'discount' => $request->discount ? (int)$request->discount : null,
            'gift' => $request->gift ?: null,
            'photos' => $request->photos ?? [],
            'video' => $request->video,
            'show_photos_in_gallery' => !empty($request->show_photos_in_gallery) ? json_encode($request->show_photos_in_gallery) : json_encode([]),
            'allow_download_photos' => !empty($request->allow_download_photos) ? json_encode($request->allow_download_photos) : json_encode([]),
            'watermark_photos' => !empty($request->watermark_photos) ? json_encode($request->watermark_photos) : json_encode([]),
            'address' => $request->address ?: null,
            'travel_area' => $request->travel_area ?: null,
            'phone' => $request->phone ?: null,
            'contact_method' => $request->contact_method ?: 'messages',
            'status' => 'draft'
        ]);

        // Всегда возвращаем редирект для Inertia
        return redirect('/profile/items/draft/all')->with('success', 'Черновик сохранен!');
    }

    /**
     * Опубликовать объявление (с валидацией обязательных полей)
     */
    public function publish(Request $request)
    {
        // Валидация обязательных полей для публикации
        $validator = Validator::make($request->all(), [
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'specialty' => 'required|string',
            'clients' => 'required|array|min:1',
            'service_location' => 'required|array|min:1',
            'work_format' => 'required|string',
            'experience' => 'required|string',
            'price' => 'required|numeric|min:0',
            'phone' => 'required|string',
        ], [
            'title.required' => 'Название объявления обязательно для заполнения',
            'specialty.required' => 'Специальность или сфера обязательна для заполнения',
            'clients.required' => 'Выберите хотя бы одну категорию клиентов',
            'clients.min' => 'Выберите хотя бы одну категорию клиентов',
            'service_location.required' => 'Укажите где вы оказываете услуги',
            'service_location.min' => 'Укажите хотя бы одно место оказания услуг',
            'work_format.required' => 'Укажите формат работы',
            'experience.required' => 'Укажите опыт работы',
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
                'work_format' => $request->work_format,
                'service_provider' => is_array($request->service_provider) ? json_encode($request->service_provider) : '[]',
                'experience' => $request->experience,
                'price' => $request->price,
                'price_unit' => $request->price_unit ?: 'session',
                'is_starting_price' => is_array($request->is_starting_price) ? json_encode($request->is_starting_price) : '[]',
                'discount' => $request->discount ?: null,
                'gift' => $request->gift ?: null,
                'address' => $request->address ?: null,
                'travel_area' => $request->travel_area ?: null,
                'phone' => $request->phone,
                'contact_method' => $request->contact_method ?: 'messages',
                'status' => 'waiting_payment' // Статус для ожидания оплаты
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Объявление готово к публикации',
            'id' => $ad->id,
            'redirect' => route('payment.select-plan', ['ad' => $ad->id])
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
        $jsonFields = ['clients', 'service_location', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours'];
        
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
        $jsonFields = ['clients', 'service_location', 'service_provider', 'is_starting_price', 
                      'photos', 'video', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 
                      'custom_travel_areas', 'working_days', 'working_hours'];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $adData[$field] = json_decode($adData[$field], true) ?? [];
            }
        }

        return response()->json($adData);
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
            'contact_method' => 'required|string|in:any,calls,messages'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors());
        }

        $ad->update($validator->validated());

        return redirect()->route('profile.dashboard')->with('success', 'Объявление обновлено!');
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

        // Если это черновик, показываем форму редактирования
        if ($ad->status === 'draft') {
            return $this->edit($ad);
        }

        // Для опубликованных объявлений показываем страницу просмотра
        return Inertia::render('Ads/Show', [
            'ad' => $ad,
            'isOwner' => true
        ]);
    }
} 