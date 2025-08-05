<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateAdRequest;
use App\Application\Http\Requests\UpdateAdRequest;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\DTOs\CreateAdDTO;
use App\Enums\AdStatus;
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
        'experience', 'features', 'additional_features',
        'description', 'price', 'price_unit', 'is_starting_price',
        'contacts_per_hour', 'express_price', 'price_per_hour', 'outcall_price', 
        'price_two_hours', 'price_night', 'min_duration', 'discount', 'gift', 'address', 'travel_area', 'geo',
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
            // Создаем DTO из валидированных данных запроса
            $validatedData = $request->validated();
            $validatedData['user_id'] = Auth::id(); // Добавляем user_id для DTO
            
            $dto = CreateAdDTO::fromRequest($validatedData);
            $ad = $this->adService->createFromDTO($dto);
            
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
    public function publish(CreateAdRequest $request)
    {
        try {
            $dto = CreateAdDTO::fromRequest($request->validated());
            $dto->user_id = auth()->id();
            
            $ad = $this->adService->createFromDTO($dto);
            $this->adService->publish($ad);

            return response()->json([
                'success' => true,
                'message' => 'Объявление готово к публикации',
                'id' => $ad->id,
                'redirect' => route('select-plan', ['ad' => $ad->id])
            ]);
            
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'errors' => ['validation' => [$e->getMessage()]],
                'message' => 'Заполните все обязательные поля'
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => [$e->getMessage()]],
                'message' => 'Ошибка при создании объявления'
            ], 500);
        }
    }

    /**
     * Показать форму редактирования
     */
    public function edit(Ad $ad)
    {
        $this->authorize('update', $ad);

        // Проверяем, что можно редактировать только черновики
        if ($ad->status !== AdStatus::DRAFT) {
            return redirect()->route('my-ads.index')
                ->with('error', 'Можно редактировать только черновики');
        }

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
     * Подготовить данные объявления для отображения
     */
    protected function prepareAdData(Ad $ad): array
    {
        $adData = $ad->toArray();
        

        
        $jsonFields = [
            'clients', 'service_location', 'outcall_locations', 'service_provider', 
            'is_starting_price', 'photos', 'video', 'features', 'pricing_data', 
            'services', 'schedule', 'geo'
        ];
        
        foreach ($jsonFields as $field) {
            if (isset($adData[$field]) && is_string($adData[$field])) {
                $adData[$field] = json_decode($adData[$field], true) ?? [];
            } elseif (!isset($adData[$field]) || $adData[$field] === null) {
                // Обеспечиваем что JSON поля всегда массивы/объекты, а не null
                $adData[$field] = [];
            }
        }
        
        // Обработка скалярных полей - обеспечиваем что они не null
        $scalarFields = [
            'price_per_hour', 'outcall_price', 'express_price', 'price_two_hours', 
            'price_night', 'min_duration', 'contacts_per_hour', 'age', 'height', 
            'weight', 'breast_size', 'hair_color', 'eye_color', 'appearance', 
            'nationality', 'work_format', 'experience', 'additional_features',
            'description', 'price', 'price_unit'
        ];
        
        foreach ($scalarFields as $field) {
            if (!isset($adData[$field]) || $adData[$field] === null) {
                $adData[$field] = '';
            }
        }
        
        // Обеспечиваем boolean поля
        $adData['has_girlfriend'] = (bool) ($adData['has_girlfriend'] ?? false);
        

        
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