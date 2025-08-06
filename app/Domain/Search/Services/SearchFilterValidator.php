<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;

/**
 * Сервис валидации фильтров поиска
 */
class SearchFilterValidator
{
    /**
     * Валидировать массив фильтров
     */
    public function validateFilters(SearchType $searchType, array $filters): array
    {
        $validated = [];
        $availableFilters = array_keys($searchType->getAvailableFilters());
        
        foreach ($filters as $key => $value) {
            if (in_array($key, $availableFilters) && $this->isValidFilter($searchType, $key, $value)) {
                $validated[$key] = $this->normalizeFilterValue($key, $value);
            }
        }
        
        return $validated;
    }

    /**
     * Проверить валидность конкретного фильтра
     */
    public function isValidFilter(SearchType $searchType, string $key, $value): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }
        
        return match($key) {
            'price_range' => $this->validatePriceRange($value),
            'rating' => is_numeric($value) && $value >= 0 && $value <= 5,
            'experience' => is_numeric($value) && $value >= 0,
            'city' => is_string($value) && mb_strlen($value) >= 2,
            'specialty' => is_string($value) && mb_strlen($value) >= 2,
            'availability' => is_bool($value) || in_array($value, ['true', 'false', '1', '0']),
            'verified' => is_bool($value) || in_array($value, ['true', 'false', '1', '0']),
            'distance' => is_numeric($value) && $value >= 0,
            'sort_by' => is_string($value) && in_array($value, ['rating', 'price', 'distance', 'experience']),
            default => true,
        };
    }

    /**
     * Нормализовать значение фильтра
     */
    public function normalizeFilterValue(string $key, $value)
    {
        return match($key) {
            'availability', 'verified' => $this->normalizeBooleanValue($value),
            'rating', 'experience', 'distance' => (float) $value,
            'price_range' => $this->normalizePriceRange($value),
            'sort_by' => strtolower(trim($value)),
            default => $value,
        };
    }

    /**
     * Валидация ценового диапазона
     */
    public function validatePriceRange($value): bool
    {
        if (is_array($value) && count($value) === 2) {
            return is_numeric($value[0]) && is_numeric($value[1]) && $value[0] <= $value[1];
        }
        
        if (is_string($value) && str_contains($value, '-')) {
            $parts = explode('-', $value);
            return count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1]);
        }
        
        return false;
    }

    /**
     * Нормализация ценового диапазона
     */
    public function normalizePriceRange($value): array
    {
        if (is_array($value)) {
            return [(int) $value[0], (int) $value[1]];
        }
        
        $parts = explode('-', $value);
        return [(int) $parts[0], (int) $parts[1]];
    }

    /**
     * Нормализация булевого значения
     */
    private function normalizeBooleanValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array($value, ['true', '1', 1, 'yes'], true);
    }

    /**
     * Получить фильтры по умолчанию для типа поиска
     */
    public function getDefaultFilters(SearchType $searchType): array
    {
        return match($searchType) {
            SearchType::ADS => [
                'availability' => true,
            ],
            SearchType::MASTERS => [
                'rating' => 3.0,
            ],
            SearchType::SERVICES => [],
            default => [],
        };
    }

    /**
     * Получить правила валидации для типа поиска
     */
    public function getValidationRules(SearchType $searchType): array
    {
        return match($searchType) {
            SearchType::ADS => [
                'price_range' => 'array|size:2',
                'rating' => 'numeric|between:0,5',
                'city' => 'string|min:2|max:50',
                'availability' => 'boolean',
                'verified' => 'boolean',
            ],
            SearchType::MASTERS => [
                'experience' => 'numeric|min:0',
                'rating' => 'numeric|between:0,5',
                'specialty' => 'string|min:2|max:100',
                'city' => 'string|min:2|max:50',
                'verified' => 'boolean',
                'distance' => 'numeric|min:0|max:100',
            ],
            SearchType::SERVICES => [
                'price_range' => 'array|size:2',
                'city' => 'string|min:2|max:50',
                'availability' => 'boolean',
            ],
            default => [],
        };
    }
}