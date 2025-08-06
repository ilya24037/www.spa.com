<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;

/**
 * Валидатор параметров поиска
 */
class SearchValidator
{
    /**
     * Валидация основных параметров поиска
     */
    public function validateSearchParams(
        string $query,
        SearchType $type,
        array $filters,
        SortBy $sortBy,
        int $page,
        int $perPage
    ): void {
        
        $this->validateQuery($query, $type);
        $this->validateSortBy($sortBy, $type);
        $this->validatePagination($page, $perPage);
        $this->validateFilters($filters, $type);
        $this->validateAuth($type);
    }

    /**
     * Валидация поискового запроса
     */
    public function validateQuery(string $query, SearchType $type): void
    {
        if (mb_strlen($query) < $type->getMinQueryLength()) {
            throw new \InvalidArgumentException(
                "Минимальная длина запроса для типа {$type->value}: {$type->getMinQueryLength()}"
            );
        }

        if (mb_strlen($query) > $type->getMaxQueryLength()) {
            throw new \InvalidArgumentException(
                "Максимальная длина запроса для типа {$type->value}: {$type->getMaxQueryLength()}"
            );
        }

        // Проверка на запрещенные символы
        if ($this->hasInvalidCharacters($query)) {
            throw new \InvalidArgumentException("Запрос содержит недопустимые символы");
        }

        // Проверка на SQL-инъекции (базовая)
        if ($this->hasSqlInjectionAttempts($query)) {
            throw new \InvalidArgumentException("Обнаружена попытка SQL-инъекции");
        }
    }

    /**
     * Валидация сортировки
     */
    public function validateSortBy(SortBy $sortBy, SearchType $type): void
    {
        if (!$sortBy->isApplicableForSearchType($type)) {
            throw new \InvalidArgumentException(
                "Сортировка {$sortBy->value} не применима для типа поиска {$type->value}"
            );
        }
    }

    /**
     * Валидация пагинации
     */
    public function validatePagination(int $page, int $perPage): void
    {
        if ($page < 1) {
            throw new \InvalidArgumentException("Номер страницы должен быть больше 0");
        }

        if ($page > 1000) {
            throw new \InvalidArgumentException("Номер страницы не может быть больше 1000");
        }
        
        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException("Количество элементов на странице должно быть от 1 до 100");
        }
    }

    /**
     * Валидация фильтров
     */
    public function validateFilters(array $filters, SearchType $type): void
    {
        $allowedFilters = $type->getAllowedFilters();
        
        foreach ($filters as $filterKey => $filterValue) {
            if (!in_array($filterKey, $allowedFilters)) {
                throw new \InvalidArgumentException("Фильтр '{$filterKey}' не поддерживается для типа поиска {$type->value}");
            }

            $this->validateFilterValue($filterKey, $filterValue, $type);
        }
    }

    /**
     * Валидация авторизации
     */
    public function validateAuth(SearchType $type): void
    {
        if ($type->requiresAuth() && !auth()->check()) {
            throw new \UnauthorizedException("Тип поиска {$type->value} требует авторизации");
        }
    }

    /**
     * Валидация геолокации
     */
    public function validateGeoSearch(array $location, float $radius, SearchType $type): void
    {
        if (!$type->supportsGeoSearch()) {
            throw new \InvalidArgumentException("Тип поиска {$type->value} не поддерживает геопоиск");
        }

        if (!isset($location['lat']) || !isset($location['lng'])) {
            throw new \InvalidArgumentException("Геолокация должна содержать lat и lng");
        }

        $lat = (float) $location['lat'];
        $lng = (float) $location['lng'];

        if ($lat < -90 || $lat > 90) {
            throw new \InvalidArgumentException("Широта должна быть между -90 и 90");
        }

        if ($lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException("Долгота должна быть между -180 и 180");
        }

        if ($radius <= 0 || $radius > 100) {
            throw new \InvalidArgumentException("Радиус должен быть между 0 и 100 км");
        }
    }

    /**
     * Валидация продвинутого поиска
     */
    public function validateAdvancedCriteria(array $criteria): void
    {
        $requiredFields = ['type'];
        
        foreach ($requiredFields as $field) {
            if (!isset($criteria[$field])) {
                throw new \InvalidArgumentException("Обязательное поле '{$field}' отсутствует в критериях поиска");
            }
        }

        $type = SearchType::tryFrom($criteria['type']);
        if (!$type) {
            throw new \InvalidArgumentException("Неизвестный тип поиска: {$criteria['type']}");
        }

        // Валидация специфичных для типа критериев
        if (isset($criteria['filters'])) {
            $this->validateFilters($criteria['filters'], $type);
        }
    }

    /**
     * Проверка на недопустимые символы
     */
    private function hasInvalidCharacters(string $query): bool
    {
        // Разрешены буквы, цифры, пробелы, дефисы, точки, запятые, кавычки
        return !preg_match('/^[\p{L}\p{N}\s\-\.\,\"\']+$/u', $query);
    }

    /**
     * Проверка на попытки SQL-инъекций
     */
    private function hasSqlInjectionAttempts(string $query): bool
    {
        $sqlPatterns = [
            '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b/i',
            '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',
            '/[\'";](\s*)(UNION|SELECT|INSERT|UPDATE|DELETE)/i',
            '/-{2}/',
            '/\/\*.*?\*\//',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Валидация значения фильтра
     */
    private function validateFilterValue(string $filterKey, $filterValue, SearchType $type): void
    {
        switch ($filterKey) {
            case 'price_min':
            case 'price_max':
                if (!is_numeric($filterValue) || $filterValue < 0) {
                    throw new \InvalidArgumentException("Значение фильтра '{$filterKey}' должно быть положительным числом");
                }
                break;

            case 'rating':
                if (!is_numeric($filterValue) || $filterValue < 1 || $filterValue > 5) {
                    throw new \InvalidArgumentException("Рейтинг должен быть между 1 и 5");
                }
                break;

            case 'date_from':
            case 'date_to':
                if (!$this->isValidDate($filterValue)) {
                    throw new \InvalidArgumentException("Неверный формат даты в фильтре '{$filterKey}'");
                }
                break;

            case 'location':
                if (is_array($filterValue)) {
                    $this->validateGeoSearch($filterValue, 50, $type);
                }
                break;
        }
    }

    /**
     * Проверка валидности даты
     */
    private function isValidDate($date): bool
    {
        if ($date instanceof \DateTime) {
            return true;
        }

        if (is_string($date)) {
            return strtotime($date) !== false;
        }

        return false;
    }

    /**
     * Получить список разрешенных символов для запроса
     */
    public function getAllowedCharacters(): string
    {
        return 'Буквы, цифры, пробелы, дефисы, точки, запятые, кавычки';
    }

    /**
     * Очистить запрос от недопустимых символов
     */
    public function sanitizeQuery(string $query): string
    {
        // Удаляем потенциально опасные символы
        $query = preg_replace('/[^\p{L}\p{N}\s\-\.\,\"\']/u', '', $query);
        
        // Удаляем лишние пробелы
        $query = preg_replace('/\s+/', ' ', $query);
        
        return trim($query);
    }
}