<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;

/**
 * Сервис форматирования и описания поисковых фильтров
 */
class SearchFilterFormatterService
{
    /**
     * Получить читаемое описание фильтров
     */
    public function getDescription(SearchType $searchType, array $activeFilters): array
    {
        $descriptions = [];
        $availableFilters = $searchType->getAvailableFilters();
        
        foreach ($activeFilters as $key => $value) {
            $filterName = $availableFilters[$key] ?? $this->getFilterDisplayName($key);
            $descriptions[] = $this->formatFilterDescription($key, $filterName, $value);
        }
        
        return $descriptions;
    }

    /**
     * Получить фильтры для breadcrumbs
     */
    public function getBreadcrumbs(SearchType $searchType, array $activeFilters): array
    {
        $breadcrumbs = [];
        $descriptions = $this->getDescription($searchType, $activeFilters);
        
        foreach ($descriptions as $index => $description) {
            $breadcrumbs[] = [
                'text' => $description,
                'removable' => true,
                'filter_key' => array_keys($activeFilters)[$index] ?? null,
            ];
        }
        
        return $breadcrumbs;
    }

    /**
     * Получить URL параметры для фильтров
     */
    public function toUrlParams(array $activeFilters): array
    {
        $params = [];
        
        foreach ($activeFilters as $key => $value) {
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
    public function toQueryString(array $activeFilters): string
    {
        return http_build_query($this->toUrlParams($activeFilters));
    }

    /**
     * Получить краткое описание активных фильтров
     */
    public function getShortDescription(SearchType $searchType, array $activeFilters): string
    {
        if (empty($activeFilters)) {
            return 'Все ' . $this->getSearchTypeLabel($searchType);
        }

        $descriptions = $this->getDescription($searchType, $activeFilters);
        
        if (count($descriptions) === 1) {
            return $descriptions[0];
        }
        
        if (count($descriptions) <= 3) {
            return implode(', ', $descriptions);
        }
        
        return implode(', ', array_slice($descriptions, 0, 2)) . ' и еще ' . (count($descriptions) - 2) . ' фильтр(ов)';
    }

    /**
     * Получить подробное описание для SEO
     */
    public function getSeoDescription(SearchType $searchType, array $activeFilters): string
    {
        $baseDescription = $this->getSearchTypeLabel($searchType);
        $filterDescriptions = $this->getDescription($searchType, $activeFilters);
        
        if (empty($filterDescriptions)) {
            return "Найдите лучших {$baseDescription} в вашем городе";
        }
        
        $filtersText = implode(', ', array_slice($filterDescriptions, 0, 3));
        return "Найдите {$baseDescription} по критериям: {$filtersText}";
    }

    /**
     * Форматировать описание конкретного фильтра
     */
    private function formatFilterDescription(string $key, string $filterName, $value): string
    {
        return match($key) {
            'price_range' => $this->formatPriceRange($filterName, $value),
            'rating' => $this->formatRating($filterName, $value),
            'city' => "Город: {$value}",
            'experience' => "Опыт от {$value} лет",
            'specialty' => "Специализация: {$value}",
            'availability' => $value ? 'Доступные сейчас' : 'Все',
            'verified' => $value ? 'Только проверенные' : 'Все',
            'distance' => "В радиусе {$value} км",
            'sort_by' => $this->formatSortBy($value),
            default => $this->formatGenericFilter($filterName, $value),
        };
    }

    /**
     * Форматировать ценовой диапазон
     */
    private function formatPriceRange(string $filterName, $value): string
    {
        if (is_array($value) && count($value) === 2) {
            return "Цена: от {$value[0]} до {$value[1]} руб.";
        }
        
        return "{$filterName}: {$value}";
    }

    /**
     * Форматировать рейтинг
     */
    private function formatRating(string $filterName, $value): string
    {
        return "Рейтинг от {$value} звезд";
    }

    /**
     * Форматировать сортировку
     */
    private function formatSortBy($value): string
    {
        return match($value) {
            'rating' => 'Сортировка по рейтингу',
            'price' => 'Сортировка по цене',
            'distance' => 'Сортировка по расстоянию',
            'experience' => 'Сортировка по опыту',
            'popularity' => 'Сортировка по популярности',
            default => "Сортировка: {$value}",
        };
    }

    /**
     * Форматировать общий фильтр
     */
    private function formatGenericFilter(string $filterName, $value): string
    {
        if (is_array($value)) {
            return "{$filterName}: " . implode(', ', $value);
        }
        
        if (is_bool($value)) {
            return $value ? $filterName : "Не {$filterName}";
        }
        
        return "{$filterName}: {$value}";
    }

    /**
     * Получить отображаемое имя фильтра
     */
    private function getFilterDisplayName(string $key): string
    {
        return match($key) {
            'price_range' => 'Цена',
            'rating' => 'Рейтинг',
            'city' => 'Город',
            'experience' => 'Опыт',
            'specialty' => 'Специализация',
            'availability' => 'Доступность',
            'verified' => 'Проверенные',
            'distance' => 'Расстояние',
            'sort_by' => 'Сортировка',
            default => ucfirst($key),
        };
    }

    /**
     * Получить лейбл типа поиска
     */
    private function getSearchTypeLabel(SearchType $searchType): string
    {
        return match($searchType) {
            SearchType::ADS => 'объявления',
            SearchType::MASTERS => 'мастеров',
            SearchType::SERVICES => 'услуги',
            default => 'результаты',
        };
    }
}