<?php

namespace App\Application\Http\Controllers\Ad;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdRequest;
use App\Http\Requests\UpdateAdRequest;
use App\Services\AdService;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/**
 * Основной контроллер для управления объявлениями
 * Отвечает за создание, редактирование и публикацию
 */
class AdController extends Controller
{
    private AdService $adService;
    
    /**
     * Разрешенные поля для массового присвоения
     */
    protected const ALLOWED_FIELDS = [
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
    public function store(CreateAdRequest $request)
    {
        try {
            $ad = $this->adService->create($request->validated(), Auth::user());
            
            // Пытаемся сразу опубликовать
            $this->adService->publish($ad);
            
            return redirect()->route('dashboard')
                ->with('success', 'Объявление успешно создано и опубликовано!');
            
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('dashboard')
                ->with('warning', 'Объявление сохранено как черновик. ' . $e->getMessage());
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при создании объявления: ' . $e->getMessage()]);
        }
    }

    /**
     * Опубликовать объявление
     */
    public function publish(Request $request)
    {
        $validator = $this->validateForPublication($request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Заполните все обязательные поля'
            ], 422);
        }

        $ad = $this->createOrUpdateAd($request);

        return response()->json([
            'success' => true,
            'message' => 'Объявление готово к публикации',
            'id' => $ad->id,
            'redirect' => route('select-plan', ['ad' => $ad->id])
        ]);
    }

    /**
     * Показать форму редактирования
     */
    public function edit(Ad $ad)
    {
        $this->authorize('update', $ad);

        $adData = $this->prepareAdData($ad);

        return Inertia::render('EditAd', [
            'ad' => $adData
        ]);
    }

    /**
     * Обновить объявление
     */
    public function update(UpdateAdRequest $request, Ad $ad)
    {
        $this->authorize('update', $ad);

        try {
            $ad = $this->adService->update($ad, $request->validated());
            
            return redirect()->route('profile.items.active')
                ->with('success', 'Объявление успешно обновлено!');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при обновлении: ' . $e->getMessage()]);
        }
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
     * Валидация для публикации
     */
    protected function validateForPublication(Request $request)
    {
        return Validator::make($request->only(self::ALLOWED_FIELDS), [
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'specialty' => 'required|string',
            'clients' => 'required|array|min:1',
            'service_location' => 'required|array|min:1',
            'work_format' => 'required|string',
            'experience' => 'required|string',
            'description' => 'required|string|min:50',
            'price' => 'required|numeric|min:0',
            'phone' => 'required|string',
        ], [
            'title.required' => 'Название объявления обязательно',
            'specialty.required' => 'Специальность обязательна',
            'clients.required' => 'Выберите категорию клиентов',
            'service_location.required' => 'Укажите место оказания услуг',
            'work_format.required' => 'Укажите формат работы',
            'experience.required' => 'Укажите опыт работы',
            'description.required' => 'Описание обязательно',
            'description.min' => 'Описание должно содержать не менее 50 символов',
            'price.required' => 'Укажите стоимость услуги',
            'phone.required' => 'Укажите телефон для связи',
        ]);
    }

    /**
     * Создать или обновить объявление
     */
    protected function createOrUpdateAd(Request $request)
    {
        $data = $this->prepareAdDataForSave($request);
        
        return Ad::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'id' => $request->id ?? null
            ],
            $data
        );
    }

    /**
     * Подготовить данные для сохранения
     */
    protected function prepareAdDataForSave(Request $request): array
    {
        return [
            'category' => $request->category,
            'title' => $request->title,
            'description' => $request->description ?: '',
            'specialty' => $request->specialty,
            'clients' => $this->encodeJson($request->clients, []),
            'service_location' => $this->encodeJson($request->service_location, []),
            'outcall_locations' => $this->encodeJson($request->outcall_locations, []),
            'taxi_option' => $request->taxi_option,
            'work_format' => $request->work_format,
            'service_provider' => $this->encodeJson($request->service_provider, []),
            'experience' => $request->experience,
            'education_level' => $request->education_level,
            'features' => $this->encodeJson($request->features, []),
            'additional_features' => $request->additional_features,
            'price' => $request->price,
            'price_unit' => $request->price_unit ?: 'session',
            'is_starting_price' => $this->encodeJson($request->is_starting_price, []),
            'pricing_data' => $this->encodeJson($request->pricing_data),
            'contacts_per_hour' => $request->contacts_per_hour,
            'discount' => $request->discount,
            'gift' => $request->gift,
            'services' => $this->encodeJson($request->services, (object)[]),
            'services_additional_info' => $request->services_additional_info,
            'address' => $request->address,
            'travel_area' => $request->travel_area,
            'phone' => $request->phone,
            'contact_method' => $request->contact_method ?: 'messages',
            'age' => $request->age,
            'height' => $request->height,
            'weight' => $request->weight,
            'breast_size' => $request->breast_size,
            'hair_color' => $request->hair_color,
            'eye_color' => $request->eye_color,
            'appearance' => $request->appearance,
            'nationality' => $request->nationality,
            'has_girlfriend' => $request->boolean('has_girlfriend', false),
            'photos' => $this->encodeJson($request->photos, []),
            'video' => $request->video_id ? json_encode(['id' => $request->video_id]) : null,
            'show_photos_in_gallery' => $request->boolean('show_photos_in_gallery', true),
            'allow_download_photos' => $request->boolean('allow_download_photos', false),
            'watermark_photos' => $request->boolean('watermark_photos', true),
            'status' => 'waiting_payment'
        ];
    }

    /**
     * Подготовить данные объявления для отображения
     */
    protected function prepareAdData(Ad $ad): array
    {
        $adData = $ad->toArray();
        
        $jsonFields = [
            'clients', 'service_location', 'outcall_locations', 'service_provider', 
            'is_starting_price', 'photos', 'video', 'features', 'pricing_data', 
            'services', 'schedule'
        ];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $adData[$field] = json_decode($adData[$field], true) ?? [];
            }
        }
        
        return $adData;
    }

    /**
     * Кодировать данные в JSON
     */
    protected function encodeJson($data, $default = null): ?string
    {
        if (empty($data)) {
            return $default !== null ? json_encode($default) : null;
        }
        
        return is_array($data) ? json_encode($data) : json_encode([]);
    }
}