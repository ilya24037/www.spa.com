<?php

namespace App\Domain\Service\Services;

use App\Domain\Master\Services\MasterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для создания объявлений
 * Инкапсулирует логику создания мастеров и их услуг
 */
class AdCreationService
{
    public function __construct(
        private MasterService $masterService,
        private ServiceCategoryProvider $categoryProvider
    ) {}

    /**
     * Валидация данных для создания объявления
     */
    public function getValidationRules(): array
    {
        return [
            'category' => 'required|string|in:massage,erotic,strip,escort',
            'display_name' => 'required|string|max:255',
            'description' => 'required|string|min:50',
            'age' => 'nullable|integer|min:18|max:65',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'city' => 'required|string',
            'district' => 'nullable|string',
            'address' => 'nullable|string',
            'salon_name' => 'nullable|string|max:255',
            'phone' => 'required|string',
            'whatsapp' => 'nullable|string',
            'telegram' => 'nullable|string',
            'price_from' => 'required|integer|min:500',
            'price_to' => 'nullable|integer|gt:price_from',
            'show_phone' => 'boolean',
            'services' => 'required|array|min:1',
            'services.*.name' => 'required|string|max:255',
            'services.*.price' => 'required|integer|min:100',
            'services.*.duration' => 'required|integer|min:15|max:480',
            'services.*.description' => 'nullable|string|max:500',
            'services.*.category_id' => 'nullable|integer',
            'work_zones' => 'nullable|array',
            'work_zones.*' => 'string|max:100',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_adult_content' => 'boolean'
        ];
    }

    /**
     * Создать объявление из данных запроса
     */
    public function createFromRequest(Request $request): array
    {
        try {
            $validated = $request->validate($this->getValidationRules());
            
            // Подготавливаем данные для создания мастера
            $masterData = $this->prepareMasterData($validated, $request);
            
            // Создаем полный профиль мастера через сервис
            $profile = $this->masterService->createFullProfile($masterData);
            
            Log::info('Создано объявление', ['master_id' => $profile->id, 'user_id' => $request->user()->id]);
            
            return [
                'success' => true,
                'master' => $profile,
                'message' => 'Объявление успешно создано!'
            ];

        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Ошибка валидации данных'
            ];

        } catch (\Exception $e) {
            Log::error('Ошибка создания объявления', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            
            return [
                'success' => false,
                'message' => 'Произошла ошибка при создании объявления'
            ];
        }
    }

    /**
     * Подготовить данные мастера
     */
    private function prepareMasterData(array $validated, Request $request): array
    {
        return [
            'user' => $request->user(),
            'display_name' => $validated['display_name'],
            'description' => $validated['description'],
            'age' => $validated['age'] ?? null,
            'experience_years' => $validated['experience_years'] ?? null,
            'city' => $validated['city'],
            'district' => $validated['district'] ?? null,
            'address' => $validated['address'] ?? null,
            'salon_name' => $validated['salon_name'] ?? null,
            'phone' => $validated['phone'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            'telegram' => $validated['telegram'] ?? null,
            'price_from' => $validated['price_from'],
            'price_to' => $validated['price_to'] ?? null,
            'show_phone' => $validated['show_phone'] ?? false,
            'category_type' => $validated['category'],
            'is_adult_content' => $validated['is_adult_content'] ?? false,
            'services' => $validated['services'] ?? [],
            'work_zones' => $validated['work_zones'] ?? [],
            'photos' => $request->file('photos') ?? [],
            // Поля модерации - новые объявления создаются неопубликованными
            'is_published' => false,
            'moderated_at' => null
        ];
    }

    /**
     * Получить хлебные крошки для страницы
     */
    public function getBreadcrumbs(?string $category = null): array
    {
        $breadcrumbs = [
            ['name' => 'Главная', 'url' => '/'],
            ['name' => 'Разместить объявление', 'url' => '/additem']
        ];

        if ($category) {
            $categoryName = $this->categoryProvider->getCategoryName($category);
            $breadcrumbs[] = ['name' => $categoryName, 'url' => null];
        }

        return $breadcrumbs;
    }
}