<?php

namespace App\Http\Controllers;

use App\Models\ExtendedServiceCategory;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExtendedServiceController extends Controller
{
    /**
     * Получить все категории услуг сгруппированные по типам
     */
    public function getCategories(): JsonResponse
    {
        $categories = ExtendedServiceCategory::where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $groupedCategories = $categories->groupBy('type');

        return response()->json([
            'success' => true,
            'data' => $groupedCategories,
            'types' => ExtendedServiceCategory::getTypes()
        ]);
    }

    /**
     * Получить категории по типу
     */
    public function getCategoriesByType(string $type): JsonResponse
    {
        $categories = ExtendedServiceCategory::getByType($type);

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Прикрепить расширенные услуги к основной услуге
     */
    public function attachToService(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'extended_services' => 'required|array',
            'extended_services.*.category_id' => 'required|exists:extended_service_categories,id',
            'extended_services.*.custom_cost' => 'nullable|numeric|min:0',
            'extended_services.*.custom_restrictions' => 'nullable|array',
        ]);

        // Удаляем старые связи
        $service->extendedCategories()->detach();

        // Добавляем новые связи
        foreach ($validated['extended_services'] as $extendedService) {
            $pivotData = [];
            
            if (isset($extendedService['custom_cost']) && $extendedService['custom_cost'] !== null) {
                $pivotData['custom_additional_cost'] = $extendedService['custom_cost'];
            }
            
            if (isset($extendedService['custom_restrictions'])) {
                $pivotData['custom_restrictions'] = json_encode($extendedService['custom_restrictions']);
            }

            $service->extendedCategories()->attach(
                $extendedService['category_id'],
                $pivotData
            );
        }

        // Обновляем флаги услуги
        $hasAdultServices = $service->extendedCategories()
            ->where('is_adult_only', true)
            ->exists();

        $service->update([
            'is_adult_only' => $hasAdultServices,
            'category_type' => $this->determineCategoryType($service)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Расширенные услуги успешно обновлены'
        ]);
    }

    /**
     * Получить расширенные услуги для конкретной услуги
     */
    public function getServiceExtended(Service $service): JsonResponse
    {
        $extendedCategories = $service->extendedCategories()
            ->with(['services' => function($query) use ($service) {
                $query->where('service_id', $service->id);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $extendedCategories
        ]);
    }

    /**
     * Рассчитать итоговую стоимость услуги с учетом дополнительных услуг
     */
    public function calculateTotalCost(Request $request, Service $service): JsonResponse
    {
        $validated = $request->validate([
            'extended_services' => 'array',
            'extended_services.*' => 'exists:extended_service_categories,id'
        ]);

        $basePrice = $service->price;
        $totalAdditionalCost = 0;
        $selectedServices = [];

        if (isset($validated['extended_services'])) {
            $extendedCategories = ExtendedServiceCategory::whereIn('id', $validated['extended_services'])
                ->get();

            foreach ($extendedCategories as $category) {
                // Проверяем есть ли персональная цена для этой услуги
                $pivot = $service->extendedCategories()
                    ->where('extended_category_id', $category->id)
                    ->first();

                $additionalCost = $pivot && $pivot->pivot->custom_additional_cost !== null
                    ? $pivot->pivot->custom_additional_cost
                    : $category->base_additional_cost;

                $totalAdditionalCost += $additionalCost;

                $selectedServices[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'cost' => $additionalCost,
                    'is_adult_only' => $category->is_adult_only,
                    'min_age' => $category->min_age
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'base_price' => $basePrice,
                'additional_cost' => $totalAdditionalCost,
                'total_price' => $basePrice + $totalAdditionalCost,
                'selected_services' => $selectedServices,
                'has_adult_content' => collect($selectedServices)->contains('is_adult_only', true),
                'min_client_age' => collect($selectedServices)->max('min_age') ?: 16
            ]
        ]);
    }

    /**
     * Получить популярные комбинации услуг
     */
    public function getPopularCombinations(): JsonResponse
    {
        // Здесь можно реализовать логику получения популярных комбинаций
        // на основе статистики заказов
        $popularCombinations = [
            [
                'name' => 'Классический пакет',
                'services' => [1, 4], // ID расширенных категорий
                'discount' => 10 // процент скидки
            ],
            [
                'name' => 'Премиум пакет',
                'services' => [1, 4, 5],
                'discount' => 15
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $popularCombinations
        ]);
    }

    /**
     * Определить тип категории на основе прикрепленных расширенных услуг
     */
    private function determineCategoryType(Service $service): string
    {
        $extendedCategories = $service->extendedCategories;

        if ($extendedCategories->contains('type', 'sex')) {
            return 'erotic';
        }

        if ($extendedCategories->contains('type', 'bdsm')) {
            return 'bdsm';
        }

        if ($extendedCategories->contains('type', 'additional')) {
            return 'premium';
        }

        return 'massage';
    }

    /**
     * Валидация возрастных ограничений
     */
    public function validateAgeRestrictions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_age' => 'required|integer|min:16|max:99',
            'extended_services' => 'required|array',
            'extended_services.*' => 'exists:extended_service_categories,id'
        ]);

        $violations = [];
        $categories = ExtendedServiceCategory::whereIn('id', $validated['extended_services'])->get();

        foreach ($categories as $category) {
            if (!$category->checkAgeRestriction($validated['client_age'])) {
                $violations[] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'required_age' => $category->min_age,
                    'client_age' => $validated['client_age']
                ];
            }
        }

        return response()->json([
            'success' => empty($violations),
            'violations' => $violations,
            'message' => empty($violations) 
                ? 'Все возрастные требования выполнены'
                : 'Обнаружены нарушения возрастных ограничений'
        ]);
    }
}