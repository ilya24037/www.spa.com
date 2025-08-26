<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Унифицированный сервис фильтров поиска
 * Консолидирует 6→1:
 * - SearchFilter.php
 * - SearchFilterFactory.php  
 * - SearchFilterValidator.php
 * - SearchFilterFormatterService.php
 * - SearchFilterSerializerService.php
 * - SearchFilterPersonalizationService.php
 * 
 * Принцип KISS: вся логика фильтров в одном месте
 */
class SearchFilterService
{
    /**
     * Создать фильтр из HTTP запроса
     */
    public function fromRequest(SearchType $searchType, ?Request $request = null): array
    {
        $request = $request ?? request();
        
        $availableFilters = $this->getAvailableFilters($searchType);
        $filters = [];
        
        foreach (array_keys($availableFilters) as $filterKey) {
            if ($request->has($filterKey)) {
                $value = $request->input($filterKey);
                
                // Валидация и очистка значения
                $cleanValue = $this->validateAndCleanFilterValue($filterKey, $value, $availableFilters[$filterKey]);
                
                if ($cleanValue !== null) {
                    $filters[$filterKey] = $cleanValue;
                }
            }
        }
        
        return $this->applyPersonalization($filters, $searchType, $request->user());
    }

    /**
     * Создать фильтр из массива
     */
    public function fromArray(SearchType $searchType, array $filters = []): array
    {
        $availableFilters = $this->getAvailableFilters($searchType);
        $cleanedFilters = [];
        
        foreach ($filters as $key => $value) {
            if (isset($availableFilters[$key])) {
                $cleanValue = $this->validateAndCleanFilterValue($key, $value, $availableFilters[$key]);
                
                if ($cleanValue !== null) {
                    $cleanedFilters[$key] = $cleanValue;
                }
            }
        }
        
        return $cleanedFilters;
    }

    /**
     * Создать фильтр из JSON
     */
    public function fromJson(string $json): array
    {
        try {
            $data = json_decode($json, true);
            
            if (!is_array($data)) {
                throw new \InvalidArgumentException('Invalid JSON format');
            }
            
            $searchType = SearchType::tryFrom($data['search_type'] ?? 'ads') ?? SearchType::ADS;
            
            return $this->fromArray($searchType, $data['filters'] ?? []);
            
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid filter JSON: ' . $e->getMessage());
        }
    }

    /**
     * Сериализовать фильтры в JSON
     */
    public function toJson(array $filters, SearchType $searchType): string
    {
        return json_encode([
            'search_type' => $searchType->value,
            'filters' => $filters,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Сериализовать фильтры для URL
     */
    public function toQueryString(array $filters): string
    {
        $queryParams = [];
        
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $queryParams[$key] = implode(',', $value);
            } else {
                $queryParams[$key] = (string) $value;
            }
        }
        
        return http_build_query($queryParams);
    }

    /**
     * Получить доступные фильтры для типа поиска
     */
    public function getAvailableFilters(SearchType $searchType): array
    {
        $cacheKey = "available_filters:{$searchType->value}";
        
        return Cache::remember($cacheKey, 3600, function () use ($searchType) {
            return $this->buildAvailableFilters($searchType);
        });
    }

    /**
     * Валидировать фильтры
     */
    public function validate(array $filters, SearchType $searchType): array
    {
        $availableFilters = $this->getAvailableFilters($searchType);
        $rules = [];
        $customMessages = [];
        
        foreach ($filters as $key => $value) {
            if (isset($availableFilters[$key])) {
                $filterConfig = $availableFilters[$key];
                $rules[$key] = $filterConfig['validation'] ?? 'nullable';
                
                if (isset($filterConfig['message'])) {
                    $customMessages["{$key}.{$filterConfig['validation']}"] = $filterConfig['message'];
                }
            }
        }
        
        $validator = Validator::make($filters, $rules, $customMessages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Форматировать фильтры для отображения
     */
    public function formatForDisplay(array $filters, SearchType $searchType): array
    {
        $availableFilters = $this->getAvailableFilters($searchType);
        $formatted = [];
        
        foreach ($filters as $key => $value) {
            if (isset($availableFilters[$key]) && !empty($value)) {
                $filterConfig = $availableFilters[$key];
                
                $formatted[] = [
                    'key' => $key,
                    'label' => $filterConfig['label'] ?? ucfirst($key),
                    'value' => $this->formatFilterValue($value, $filterConfig),
                    'removable' => $filterConfig['removable'] ?? true
                ];
            }
        }
        
        return $formatted;
    }

    /**
     * Получить активные фильтры (с значениями)
     */
    public function getActiveFilters(array $filters): array
    {
        return array_filter($filters, function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }
            return $value !== null && $value !== '';
        });
    }

    /**
     * Применить фильтры по умолчанию
     */
    public function applyDefaults(array $filters, SearchType $searchType): array
    {
        $defaults = $this->getDefaultFilters($searchType);
        
        return array_merge($defaults, $filters);
    }

    /**
     * Персонализация фильтров под пользователя
     */
    public function applyPersonalization(array $filters, SearchType $searchType, $user = null): array
    {
        if (!$user) {
            return $filters;
        }
        
        // Применяем персонализацию на основе истории пользователя
        $personalizedFilters = $this->getPersonalizedFilters($user->id, $searchType);
        
        // Объединяем с текущими фильтрами (текущие имеют приоритет)
        return array_merge($personalizedFilters, $filters);
    }

    /**
     * Сохранить предпочтения фильтров пользователя
     */
    public function saveUserPreferences(int $userId, array $filters, SearchType $searchType): void
    {
        $cacheKey = "user_filter_preferences:{$userId}:{$searchType->value}";
        
        // Сохраняем в кеше на 30 дней
        Cache::put($cacheKey, $filters, 30 * 24 * 3600);
        
        // Также можно сохранить в БД для долгосрочного хранения
        // UserFilterPreference::updateOrCreate([
        //     'user_id' => $userId,
        //     'search_type' => $searchType->value
        // ], [
        //     'filters' => json_encode($filters)
        // ]);
    }

    /**
     * Получить сохраненные предпочтения фильтров
     */
    public function getUserPreferences(int $userId, SearchType $searchType): array
    {
        $cacheKey = "user_filter_preferences:{$userId}:{$searchType->value}";
        
        return Cache::get($cacheKey, []);
    }

    /**
     * Очистить предпочтения пользователя
     */
    public function clearUserPreferences(int $userId, ?SearchType $searchType = null): void
    {
        if ($searchType) {
            Cache::forget("user_filter_preferences:{$userId}:{$searchType->value}");
        } else {
            // Очищаем все типы поиска
            foreach (SearchType::cases() as $type) {
                Cache::forget("user_filter_preferences:{$userId}:{$type->value}");
            }
        }
    }

    /**
     * Получить статистику использования фильтров
     */
    public function getFilterStatistics(SearchType $searchType): array
    {
        $cacheKey = "filter_statistics:{$searchType->value}";
        
        return Cache::remember($cacheKey, 3600, function () use ($searchType) {
            // Здесь можно добавить реальную статистику из БД
            return [
                'most_used' => ['price_min', 'city', 'rating_min'],
                'rarely_used' => ['specific_services'],
                'average_filters_per_search' => 2.5
            ];
        });
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ===

    /**
     * Построить доступные фильтры для типа поиска
     */
    private function buildAvailableFilters(SearchType $searchType): array
    {
        switch ($searchType) {
            case SearchType::ADS:
                return [
                    'category' => [
                        'label' => 'Категория',
                        'type' => 'select',
                        'validation' => 'nullable|string',
                        'options' => $this->getCategoryOptions()
                    ],
                    'price_min' => [
                        'label' => 'Цена от',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:0'
                    ],
                    'price_max' => [
                        'label' => 'Цена до',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:0'
                    ],
                    'city' => [
                        'label' => 'Город',
                        'type' => 'select',
                        'validation' => 'nullable|string',
                        'options' => $this->getCityOptions()
                    ],
                    'rating_min' => [
                        'label' => 'Рейтинг от',
                        'type' => 'select',
                        'validation' => 'nullable|numeric|between:1,5',
                        'options' => [1, 2, 3, 4, 5]
                    ],
                    'age_min' => [
                        'label' => 'Возраст от',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:18|max:100'
                    ],
                    'age_max' => [
                        'label' => 'Возраст до',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:18|max:100'
                    ],
                    'services' => [
                        'label' => 'Услуги',
                        'type' => 'multiselect',
                        'validation' => 'nullable|array',
                        'options' => $this->getServiceOptions()
                    ]
                ];

            case SearchType::MASTERS:
                return [
                    'rating_min' => [
                        'label' => 'Рейтинг от',
                        'type' => 'select',
                        'validation' => 'nullable|numeric|between:1,5',
                        'options' => [1, 2, 3, 4, 5]
                    ],
                    'experience_min' => [
                        'label' => 'Опыт от (лет)',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:0'
                    ],
                    'city' => [
                        'label' => 'Город',
                        'type' => 'select',
                        'validation' => 'nullable|string',
                        'options' => $this->getCityOptions()
                    ],
                    'specialization' => [
                        'label' => 'Специализация',
                        'type' => 'text',
                        'validation' => 'nullable|string'
                    ],
                    'verified' => [
                        'label' => 'Только проверенные',
                        'type' => 'checkbox',
                        'validation' => 'nullable|boolean'
                    ]
                ];

            case SearchType::SERVICES:
                return [
                    'service_category' => [
                        'label' => 'Категория услуги',
                        'type' => 'select',
                        'validation' => 'nullable|string',
                        'options' => $this->getServiceCategoryOptions()
                    ],
                    'price_min' => [
                        'label' => 'Цена от',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:0'
                    ],
                    'price_max' => [
                        'label' => 'Цена до',
                        'type' => 'number',
                        'validation' => 'nullable|numeric|min:0'
                    ]
                ];

            default:
                return [];
        }
    }

    /**
     * Валидировать и очистить значение фильтра
     */
    private function validateAndCleanFilterValue(string $filterKey, $value, array $filterConfig)
    {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($filterConfig['type']) {
            case 'number':
                return is_numeric($value) ? (float) $value : null;

            case 'select':
                $options = $filterConfig['options'] ?? [];
                return in_array($value, $options) ? $value : null;

            case 'multiselect':
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
                return is_array($value) ? array_filter($value) : null;

            case 'checkbox':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            case 'text':
            default:
                return is_string($value) ? trim($value) : (string) $value;
        }
    }

    /**
     * Форматировать значение фильтра для отображения
     */
    private function formatFilterValue($value, array $filterConfig): string
    {
        switch ($filterConfig['type']) {
            case 'multiselect':
                return is_array($value) ? implode(', ', $value) : (string) $value;

            case 'checkbox':
                return $value ? 'Да' : 'Нет';

            case 'select':
                $options = $filterConfig['options'] ?? [];
                if (is_array($options) && isset($options[$value])) {
                    return $options[$value];
                }
                return (string) $value;

            default:
                return (string) $value;
        }
    }

    /**
     * Получить фильтры по умолчанию
     */
    private function getDefaultFilters(SearchType $searchType): array
    {
        switch ($searchType) {
            case SearchType::ADS:
                return [
                    'rating_min' => 3.0  // Показываем только с рейтингом 3+
                ];

            case SearchType::MASTERS:
                return [
                    'verified' => false
                ];

            default:
                return [];
        }
    }

    /**
     * Получить персонализированные фильтры
     */
    private function getPersonalizedFilters(int $userId, SearchType $searchType): array
    {
        // Получаем сохраненные предпочтения
        $preferences = $this->getUserPreferences($userId, $searchType);
        
        // Можно также добавить логику на основе истории поиска пользователя
        // Например, часто используемый город, ценовой диапазон и т.д.
        
        return $preferences;
    }

    /**
     * Получить опции категорий
     */
    private function getCategoryOptions(): array
    {
        return Cache::remember('category_options', 3600, function () {
            return [
                'massage' => 'Массаж',
                'beauty' => 'Красота',
                'fitness' => 'Фитнес',
                'wellness' => 'Велнес'
            ];
        });
    }

    /**
     * Получить опции городов
     */
    private function getCityOptions(): array
    {
        return Cache::remember('city_options', 3600, function () {
            return [
                'moscow' => 'Москва',
                'spb' => 'Санкт-Петербург',
                'novosibirsk' => 'Новосибирск',
                'ekaterinburg' => 'Екатеринбург'
            ];
        });
    }

    /**
     * Получить опции услуг
     */
    private function getServiceOptions(): array
    {
        return Cache::remember('service_options', 3600, function () {
            return [
                'classic_massage' => 'Классический массаж',
                'relax_massage' => 'Расслабляющий массаж',
                'sport_massage' => 'Спортивный массаж',
                'thai_massage' => 'Тайский массаж'
            ];
        });
    }

    /**
     * Получить опции категорий услуг
     */
    private function getServiceCategoryOptions(): array
    {
        return Cache::remember('service_category_options', 3600, function () {
            return [
                'massage' => 'Массаж',
                'spa' => 'СПА процедуры',
                'beauty' => 'Косметология'
            ];
        });
    }
}