<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;

/**
 * Класс для работы с фильтрами поиска - координатор
 */
class SearchFilter
{
    protected array $filters = [];
    protected SearchType $searchType;

    private SearchFilterValidator $validator;
    private SearchFilterPersonalizationService $personalizationService;
    private SearchFilterFormatterService $formatterService;
    private SearchFilterSerializerService $serializerService;

    public function __construct(
        SearchType $searchType, 
        array $filters = [],
        ?SearchFilterValidator $validator = null,
        ?SearchFilterPersonalizationService $personalizationService = null,
        ?SearchFilterFormatterService $formatterService = null,
        ?SearchFilterSerializerService $serializerService = null
    ) {
        $this->searchType = $searchType;
        $this->validator = $validator ?? app(SearchFilterValidator::class);
        $this->personalizationService = $personalizationService ?? app(SearchFilterPersonalizationService::class);
        $this->formatterService = $formatterService ?? app(SearchFilterFormatterService::class);
        $this->serializerService = $serializerService ?? app(SearchFilterSerializerService::class);
        
        $this->filters = $this->validator->validateFilters($searchType, $filters);
    }

    /**
     * Получить тип поиска
     */
    public function getSearchType(): SearchType
    {
        return $this->searchType;
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
        $this->filters = array_merge($this->filters, $this->validator->validateFilters($this->searchType, $otherFilters));
        return $this;
    }

    /**
     * Получить URL параметры для фильтров
     */
    public function toUrlParams(): array
    {
        return $this->formatterService->toUrlParams($this->getActiveFilters());
    }

    /**
     * Получить строку запроса
     */
    public function toQueryString(): string
    {
        return $this->formatterService->toQueryString($this->getActiveFilters());
    }

    /**
     * Получить хэш фильтров для кэширования
     */
    public function getHash(): string
    {
        return $this->serializerService->getHash($this->getActiveFilters());
    }

    /**
     * Применить фильтры по умолчанию для типа поиска
     */
    public function applyDefaults(): self
    {
        $defaults = $this->validator->getDefaultFilters($this->searchType);
        
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
        
        $personalizedFilters = $this->personalizationService->getPersonalizedFilters($userId, $this->searchType);
        
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
        return $this->formatterService->getDescription($this->searchType, $this->getActiveFilters());
    }

    /**
     * Получить фильтры для breadcrumbs
     */
    public function getBreadcrumbs(): array
    {
        return $this->formatterService->getBreadcrumbs($this->searchType, $this->getActiveFilters());
    }

    /**
     * Экспорт в JSON
     */
    public function toJson(): string
    {
        return $this->serializerService->toJson($this->searchType, $this->getActiveFilters(), $this->getDescription());
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
     * Проверить валидность фильтра (делегируем в validator)
     */
    protected function isValidFilter(string $key, $value): bool
    {
        return $this->validator->isValidFilter($this->searchType, $key, $value);
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
        return $this->serializerService->toArray($this->searchType, $this->filters, $this->getActiveFilters(), $this->getActiveFiltersCount());
    }

    /**
     * Магический метод для преобразования в строку
     */
    public function __toString(): string
    {
        return $this->toQueryString();
    }
}