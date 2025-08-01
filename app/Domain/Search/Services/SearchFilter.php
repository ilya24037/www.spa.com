<?php

namespace App\Domain\Search\Services;

use App\Enums\SearchType;
use App\Enums\SortBy;

/**
 * Класс для работы с фильтрами поиска
 */
class SearchFilter
{
    protected array $filters = [];
    protected SearchType $searchType;

    public function __construct(SearchType $searchType, array $filters = [])
    {
        $this->searchType = $searchType;
        $this->filters = $this->validateFilters($filters);
    }

    /**
     * Создать из массива
     */
    public static function fromArray(SearchType $searchType, array $filters = []): self
    {
        return new self($searchType, $filters);
    }

    /**
     * Создать из запроса
     */
    public static function fromRequest(SearchType $searchType, ?\Illuminate\Http\Request $request = null): self
    {
        $request = $request ?? request();
        
        $filters = [];
        $availableFilters = $searchType->getAvailableFilters();
        
        foreach (array_keys($availableFilters) as $filterKey) {
            if ($request->has($filterKey)) {
                $filters[$filterKey] = $request->input($filterKey);
            }
        }
        
        return new self($searchType, $filters);
    }

    /**
     * Получить все фильтры
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Получить конкретный фильтр
     */
    public function get(string $key, $default = null)
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * Установить фильтр
     */
    public function set(string $key, $value): self
    {
        if ($this->isValidFilter($key, $value)) {
            $this->filters[$key] = $value;
        }
        
        return $this;
    }

    /**
     * Удалить фильтр
     */
    public function remove(string $key): self
    {
        unset($this->filters[$key]);
        return $this;
    }

    /**
     * Проверить наличие фильтра
     */
    public function has(string $key): bool
    {
        return isset($this->filters[$key]) && $this->filters[$key] !== null && $this->filters[$key] !== '';
    }

    /**
     * Получить активные фильтры (не пустые)
     */
    public function getActiveFilters(): array
    {
        return array_filter($this->filters, function($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }

    /**
     * Проверить, есть ли активные фильтры
     */
    public function hasActiveFilters(): bool
    {
        return !empty($this->getActiveFilters());
    }

    /**
     * Получить количество активных фильтров
     */
    public function getActiveFiltersCount(): int
    {
        return count($this->getActiveFilters());
    }

    /**
     * Очистить все фильтры
     */
    public function clear(): self
    {
        $this->filters = [];
        return $this;
    }

    /**
     * Объединить с другими фильтрами
     */
    public function merge(array $otherFilters): self
    {
        $this->filters = array_merge($this->filters, $this->validateFilters($otherFilters));
        return $this;
    }

    /**
     * Получить URL параметры для фильтров
     */
    public function toUrlParams(): array
    {
        $params = [];
        
        foreach ($this->getActiveFilters() as $key => $value) {
            if (is_array($value)) {
                $params[$key] = implode(',', $value);
            } else {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }

    /**
     * Получить строку запроса
     */
    public function toQueryString(): string
    {
        return http_build_query($this->toUrlParams());
    }

    /**
     * Получить хэш фильтров для кэширования
     */
    public function getHash(): string
    {
        return md5(serialize($this->getActiveFilters()));
    }

    /**
     * Применить фильтры по умолчанию для типа поиска
     */
    public function applyDefaults(): self
    {
        $defaults = $this->getDefaultFilters();
        
        foreach ($defaults as $key => $value) {
            if (!$this->has($key)) {
                $this->set($key, $value);
            }
        }
        
        return $this;
    }

    /**
     * Получить персонализированные фильтры
     */
    public function applyPersonalization(?int $userId = null): self
    {
        $userId = $userId ?? auth()->id();
        
        if (!$userId) {
            return $this;
        }
        
        $personalizedFilters = $this->getPersonalizedFilters($userId);
        
        foreach ($personalizedFilters as $key => $value) {
            if (!$this->has($key)) {
                $this->set($key, $value);
            }
        }
        
        return $this;
    }

    /**
     * Получить читаемое описание фильтров
     */
    public function getDescription(): array
    {
        $descriptions = [];
        $availableFilters = $this->searchType->getAvailableFilters();
        
        foreach ($this->getActiveFilters() as $key => $value) {
            $filterName = $availableFilters[$key] ?? $key;
            $descriptions[] = $this->formatFilterDescription($filterName, $value);
        }
        
        return $descriptions;
    }

    /**
     * Получить фильтры для breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        
        foreach ($this->getDescription() as $description) {
            $breadcrumbs[] = [
                'text' => $description,
                'removable' => true,
            ];
        }
        
        return $breadcrumbs;
    }

    /**
     * Экспорт в JSON
     */
    public function toJson(): string
    {
        return json_encode([
            'search_type' => $this->searchType->value,
            'filters' => $this->getActiveFilters(),
            'description' => $this->getDescription(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Создать из JSON
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['search_type'])) {
            throw new \InvalidArgumentException('Invalid JSON format');
        }
        
        $searchType = SearchType::from($data['search_type']);
        return new self($searchType, $data['filters'] ?? []);
    }

    /**
     * Валидация фильтров
     */
    protected function validateFilters(array $filters): array
    {
        $validated = [];
        $availableFilters = array_keys($this->searchType->getAvailableFilters());
        
        foreach ($filters as $key => $value) {
            if (in_array($key, $availableFilters) && $this->isValidFilter($key, $value)) {
                $validated[$key] = $this->normalizeFilterValue($key, $value);
            }
        }
        
        return $validated;
    }

    /**
     * Проверить валидность фильтра
     */
    protected function isValidFilter(string $key, $value): bool
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
            default => true,
        };
    }

    /**
     * Нормализовать значение фильтра
     */
    protected function normalizeFilterValue(string $key, $value)
    {
        return match($key) {
            'availability', 'verified' => (bool) $value,
            'rating', 'experience' => (float) $value,
            'price_range' => $this->normalizePriceRange($value),
            default => $value,
        };
    }

    /**
     * Валидация ценового диапазона
     */
    protected function validatePriceRange($value): bool
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
    protected function normalizePriceRange($value): array
    {
        if (is_array($value)) {
            return [(int) $value[0], (int) $value[1]];
        }
        
        $parts = explode('-', $value);
        return [(int) $parts[0], (int) $parts[1]];
    }

    /**
     * Получить фильтры по умолчанию
     */
    protected function getDefaultFilters(): array
    {
        return match($this->searchType) {
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
     * Получить персонализированные фильтры
     */
    protected function getPersonalizedFilters(int $userId): array
    {
        // Здесь должна быть логика получения предпочтений пользователя
        // Пока возвращаем пустой массив
        return [];
    }

    /**
     * Форматировать описание фильтра
     */
    protected function formatFilterDescription(string $filterName, $value): string
    {
        if (is_array($value)) {
            if (count($value) === 2 && is_numeric($value[0]) && is_numeric($value[1])) {
                // Ценовой диапазон
                return "{$filterName}: от {$value[0]} до {$value[1]} руб.";
            } else {
                return "{$filterName}: " . implode(', ', $value);
            }
        }
        
        if (is_bool($value)) {
            return $value ? $filterName : "Не {$filterName}";
        }
        
        return "{$filterName}: {$value}";
    }

    /**
     * Клонировать фильтр
     */
    public function clone(): self
    {
        return new self($this->searchType, $this->filters);
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'search_type' => $this->searchType->value,
            'filters' => $this->filters,
            'active_filters' => $this->getActiveFilters(),
            'active_count' => $this->getActiveFiltersCount(),
        ];
    }

    /**
     * Магический метод для преобразования в строку
     */
    public function __toString(): string
    {
        return $this->toQueryString();
    }
}