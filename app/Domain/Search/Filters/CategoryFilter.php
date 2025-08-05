<?php

namespace App\Domain\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Фильтр по категориям (услуги, специальности)
 * Соответствует DDD архитектуре - размещен в Domain\Search\Filters
 */
class CategoryFilter implements FilterInterface
{
    protected array $categoryIds = [];
    protected array $categoryTypes = [];
    protected ?string $categoryField = 'category_id';
    protected ?string $categoryTypeField = 'category_type';
    protected bool $includeSubcategories = false;
    protected bool $matchAll = false; // true = AND, false = OR

    public function __construct(array $params = [])
    {
        $this->categoryIds = $this->normalizeIds($params['category_ids'] ?? $params['categories'] ?? []);
        $this->categoryTypes = $this->normalizeTypes($params['category_types'] ?? []);
        $this->categoryField = $params['category_field'] ?? 'category_id';
        $this->categoryTypeField = $params['category_type_field'] ?? 'category_type';
        $this->includeSubcategories = (bool) ($params['include_subcategories'] ?? false);
        $this->matchAll = (bool) ($params['match_all'] ?? false);
    }

    /**
     * Применить фильтр к запросу
     */
    public function apply(Builder $query): Builder
    {
        if (!$this->isActive()) {
            return $query;
        }

        // Фильтр по ID категорий
        if (!empty($this->categoryIds)) {
            $this->applyCategoryIdsFilter($query);
        }

        // Фильтр по типам категорий
        if (!empty($this->categoryTypes)) {
            $this->applyCategoryTypesFilter($query);
        }

        return $query;
    }

    /**
     * Применить фильтр по ID категорий
     */
    protected function applyCategoryIdsFilter(Builder $query): void
    {
        $categoryIds = $this->categoryIds;

        // Если нужно включить подкатегории
        if ($this->includeSubcategories) {
            $categoryIds = $this->expandWithSubcategories($categoryIds);
        }

        if ($this->matchAll) {
            // AND логика - должны быть все категории
            foreach ($categoryIds as $categoryId) {
                $query->whereHas('categories', function($q) use ($categoryId) {
                    $q->where($this->categoryField, $categoryId);
                });
            }
        } else {
            // OR логика - хотя бы одна категория
            $query->where(function($q) use ($categoryIds) {
                $q->whereIn($this->categoryField, $categoryIds)
                  ->orWhereHas('categories', function($q) use ($categoryIds) {
                      $q->whereIn('id', $categoryIds);
                  })
                  ->orWhereHas('services', function($q) use ($categoryIds) {
                      $q->whereIn('category_id', $categoryIds);
                  });
            });
        }
    }

    /**
     * Применить фильтр по типам категорий
     */
    protected function applyCategoryTypesFilter(Builder $query): void
    {
        if ($this->matchAll) {
            foreach ($this->categoryTypes as $type) {
                $query->where($this->categoryTypeField, $type);
            }
        } else {
            $query->whereIn($this->categoryTypeField, $this->categoryTypes);
        }
    }

    /**
     * Расширить список категорий подкатегориями
     */
    protected function expandWithSubcategories(array $categoryIds): array
    {
        // Здесь должна быть логика получения подкатегорий
        // Пока возвращаем исходный массив
        // TODO: Реализовать через CategoryRepository
        return $categoryIds;
    }

    /**
     * Получить активные параметры фильтра
     */
    public function getActiveParams(): array
    {
        $params = [];

        if (!empty($this->categoryIds)) {
            $params['category_ids'] = $this->categoryIds;
        }

        if (!empty($this->categoryTypes)) {
            $params['category_types'] = $this->categoryTypes;
        }

        if ($this->includeSubcategories) {
            $params['include_subcategories'] = true;
        }

        if ($this->matchAll) {
            $params['match_all'] = true;
        }

        if ($this->categoryField !== 'category_id') {
            $params['category_field'] = $this->categoryField;
        }

        if ($this->categoryTypeField !== 'category_type') {
            $params['category_type_field'] = $this->categoryTypeField;
        }

        return $params;
    }

    /**
     * Проверить, активен ли фильтр
     */
    public function isActive(): bool
    {
        return !empty($this->categoryIds) || !empty($this->categoryTypes);
    }

    /**
     * Получить описание фильтра
     */
    public function getDescription(): string
    {
        $parts = [];

        if (!empty($this->categoryIds)) {
            $count = count($this->categoryIds);
            $parts[] = "Категории: выбрано {$count}";
        }

        if (!empty($this->categoryTypes)) {
            $types = implode(', ', array_map(function($type) {
                return $this->getTypeLabel($type);
            }, $this->categoryTypes));
            $parts[] = "Типы: {$types}";
        }

        if ($this->includeSubcategories && !empty($this->categoryIds)) {
            $parts[] = "(включая подкатегории)";
        }

        return implode(' ', $parts);
    }

    /**
     * Клонировать фильтр
     */
    public function clone(): self
    {
        return new self($this->getActiveParams());
    }

    /**
     * Сбросить фильтр
     */
    public function reset(): self
    {
        $this->categoryIds = [];
        $this->categoryTypes = [];
        $this->includeSubcategories = false;
        $this->matchAll = false;

        return $this;
    }

    /**
     * Установить категории
     */
    public function setCategoryIds(array $categoryIds): self
    {
        $this->categoryIds = $this->normalizeIds($categoryIds);
        return $this;
    }

    /**
     * Добавить категорию
     */
    public function addCategoryId(int $categoryId): self
    {
        if (!in_array($categoryId, $this->categoryIds)) {
            $this->categoryIds[] = $categoryId;
        }
        return $this;
    }

    /**
     * Удалить категорию
     */
    public function removeCategoryId(int $categoryId): self
    {
        $this->categoryIds = array_values(array_diff($this->categoryIds, [$categoryId]));
        return $this;
    }

    /**
     * Установить типы категорий
     */
    public function setCategoryTypes(array $types): self
    {
        $this->categoryTypes = $this->normalizeTypes($types);
        return $this;
    }

    /**
     * Включить подкатегории
     */
    public function withSubcategories(bool $include = true): self
    {
        $this->includeSubcategories = $include;
        return $this;
    }

    /**
     * Установить режим совпадения
     */
    public function setMatchAll(bool $matchAll): self
    {
        $this->matchAll = $matchAll;
        return $this;
    }

    /**
     * Нормализовать ID категорий
     */
    protected function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return array_values(array_filter(array_map('intval', $ids)));
    }

    /**
     * Нормализовать типы категорий
     */
    protected function normalizeTypes($types): array
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        return array_values(array_filter(array_map('strval', $types)));
    }

    /**
     * Получить метку для типа
     */
    protected function getTypeLabel(string $type): string
    {
        return match($type) {
            'massage' => 'Массаж',
            'spa' => 'СПА',
            'beauty' => 'Красота',
            'wellness' => 'Велнес',
            'therapy' => 'Терапия',
            default => ucfirst($type),
        };
    }

    /**
     * Проверить наличие категории
     */
    public function hasCategory(int $categoryId): bool
    {
        return in_array($categoryId, $this->categoryIds);
    }

    /**
     * Проверить наличие типа
     */
    public function hasType(string $type): bool
    {
        return in_array($type, $this->categoryTypes);
    }

    /**
     * Получить количество выбранных категорий
     */
    public function getCategoryCount(): int
    {
        return count($this->categoryIds);
    }
}