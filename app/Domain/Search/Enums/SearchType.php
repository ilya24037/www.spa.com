<?php

namespace App\Domain\Search\Enums;

/**
 * Типы поиска в системе
 */
enum SearchType: string
{
    case ADS = 'ads';                    // Поиск объявлений
    case MASTERS = 'masters';            // Поиск мастеров
    case SERVICES = 'services';          // Поиск услуг
    case GLOBAL = 'global';              // Глобальный поиск
    case RECOMMENDATIONS = 'recommendations'; // Рекомендации

    /**
     * Получить русское название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ADS => 'Объявления',
            self::MASTERS => 'Мастера',
            self::SERVICES => 'Услуги',
            self::GLOBAL => 'Везде',
            self::RECOMMENDATIONS => 'Рекомендации',
        };
    }

    /**
     * Получить иконку типа
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ADS => 'megaphone',
            self::MASTERS => 'users',
            self::SERVICES => 'briefcase',
            self::GLOBAL => 'search',
            self::RECOMMENDATIONS => 'star',
        };
    }

    /**
     * Получить минимальную длину поискового запроса
     */
    public function getMinQueryLength(): int
    {
        return match($this) {
            self::ADS => 2,
            self::MASTERS => 2,
            self::SERVICES => 2,
            self::GLOBAL => 3,
            self::RECOMMENDATIONS => 0, // Не требует запроса
        };
    }

    /**
     * Требует ли тип авторизации
     */
    public function requiresAuth(): bool
    {
        return match($this) {
            self::RECOMMENDATIONS => true,
            default => false,
        };
    }

    /**
     * Поддерживает ли тип фасетный поиск
     */
    public function supportsFacetedSearch(): bool
    {
        return match($this) {
            self::ADS, self::MASTERS, self::SERVICES => true,
            self::GLOBAL, self::RECOMMENDATIONS => false,
        };
    }

    /**
     * Поддерживает ли тип геопоиск
     */
    public function supportsGeoSearch(): bool
    {
        return match($this) {
            self::ADS, self::MASTERS => true,
            self::SERVICES, self::GLOBAL, self::RECOMMENDATIONS => false,
        };
    }

    /**
     * Получить доступные фильтры для типа
     */
    public function getAvailableFilters(): array
    {
        return match($this) {
            self::ADS => [
                'city', 'price_range', 'rating', 'experience', 
                'service_type', 'availability', 'ad_type', 
                'work_format', 'verified', 'has_photos', 'has_reviews'
            ],
            self::MASTERS => [
                'city', 'specialty', 'rating', 'experience', 
                'price_range', 'availability', 'verified', 
                'gender', 'age_range', 'languages', 'has_certificates'
            ],
            self::SERVICES => [
                'category', 'price_range', 'duration', 
                'popularity', 'rating'
            ],
            self::GLOBAL => ['type', 'city'],
            self::RECOMMENDATIONS => ['category', 'price_range'],
        };
    }

    /**
     * Получить доступные сортировки для типа
     */
    public function getAvailableSortOptions(): array
    {
        return match($this) {
            self::ADS => ['relevance', 'price_asc', 'price_desc', 'date', 'rating', 'popularity'],
            self::MASTERS => ['relevance', 'rating', 'experience', 'price_asc', 'price_desc', 'activity'],
            self::SERVICES => ['relevance', 'popularity', 'price_asc', 'price_desc', 'rating'],
            self::GLOBAL => ['relevance', 'type'],
            self::RECOMMENDATIONS => ['score', 'popularity'],
        };
    }

    /**
     * Получить URL для поиска
     */
    public function getSearchUrl(): string
    {
        return match($this) {
            self::ADS => '/search/ads',
            self::MASTERS => '/search/masters',
            self::SERVICES => '/search/services',
            self::GLOBAL => '/search',
            self::RECOMMENDATIONS => '/recommendations',
        };
    }

    /**
     * Получить индекс для Elasticsearch/Scout
     */
    public function getSearchIndex(): string
    {
        return match($this) {
            self::ADS => 'ads_index',
            self::MASTERS => 'masters_index',
            self::SERVICES => 'services_index',
            self::GLOBAL => 'global_index',
            self::RECOMMENDATIONS => 'recommendations_index',
        };
    }

    /**
     * Получить вес для глобального поиска
     */
    public function getGlobalSearchWeight(): float
    {
        return match($this) {
            self::ADS => 1.0,
            self::MASTERS => 0.9,
            self::SERVICES => 0.8,
            default => 0.5,
        };
    }

    /**
     * Можно ли экспортировать результаты
     */
    public function canExport(): bool
    {
        return match($this) {
            self::ADS, self::MASTERS, self::SERVICES => true,
            self::GLOBAL, self::RECOMMENDATIONS => false,
        };
    }

    /**
     * Получить максимальное количество результатов
     */
    public function getMaxResults(): int
    {
        return match($this) {
            self::GLOBAL => 50,  // Ограничиваем глобальный поиск
            self::RECOMMENDATIONS => 20,  // Ограничиваем рекомендации
            default => 1000,
        };
    }

    /**
     * Получить TTL кеша в секундах
     */
    public function getCacheTTL(): int
    {
        return match($this) {
            self::RECOMMENDATIONS => 1800,  // 30 минут
            self::GLOBAL => 600,            // 10 минут
            default => 300,                 // 5 минут
        };
    }
}