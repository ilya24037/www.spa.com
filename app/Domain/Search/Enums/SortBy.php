<?php

namespace App\Domain\Search\Enums;

/**
 * Варианты сортировки результатов поиска
 */
enum SortBy: string
{
    case RELEVANCE = 'relevance';        // По релевантности
    case PRICE_ASC = 'price_asc';        // По цене (возрастание)
    case PRICE_DESC = 'price_desc';      // По цене (убывание)
    case DATE = 'date';                  // По дате (новые первыми)
    case RATING = 'rating';              // По рейтингу
    case POPULARITY = 'popularity';      // По популярности
    case DISTANCE = 'distance';          // По расстоянию
    case EXPERIENCE = 'experience';      // По опыту
    case ACTIVITY = 'activity';          // По активности
    case SCORE = 'score';                // По баллам (для рекомендаций)

    /**
     * Получить русское название сортировки
     */
    public function getLabel(): string
    {
        return match($this) {
            self::RELEVANCE => 'По релевантности',
            self::PRICE_ASC => 'Сначала дешевле',
            self::PRICE_DESC => 'Сначала дороже',
            self::DATE => 'Сначала новые',
            self::RATING => 'По рейтингу',
            self::POPULARITY => 'По популярности',
            self::DISTANCE => 'Сначала ближе',
            self::EXPERIENCE => 'По опыту',
            self::ACTIVITY => 'По активности',
            self::SCORE => 'По соответствию',
        };
    }

    /**
     * Получить короткое название
     */
    public function getShortLabel(): string
    {
        return match($this) {
            self::RELEVANCE => 'Релевантность',
            self::PRICE_ASC => 'Цена ↑',
            self::PRICE_DESC => 'Цена ↓',
            self::DATE => 'Новые',
            self::RATING => 'Рейтинг',
            self::POPULARITY => 'Популярные',
            self::DISTANCE => 'Расстояние',
            self::EXPERIENCE => 'Опыт',
            self::ACTIVITY => 'Активность',
            self::SCORE => 'Соответствие',
        };
    }

    /**
     * Получить иконку сортировки
     */
    public function getIcon(): string
    {
        return match($this) {
            self::RELEVANCE => 'sparkles',
            self::PRICE_ASC => 'arrow-trending-down',
            self::PRICE_DESC => 'arrow-trending-up',
            self::DATE => 'calendar',
            self::RATING => 'star',
            self::POPULARITY => 'fire',
            self::DISTANCE => 'map-pin',
            self::EXPERIENCE => 'academic-cap',
            self::ACTIVITY => 'activity',
            self::SCORE => 'chart-bar',
        };
    }

    /**
     * Проверить применимость сортировки для типа поиска
     */
    public function isApplicableForSearchType(SearchType $searchType): bool
    {
        $applicableTypes = match($this) {
            self::RELEVANCE => [SearchType::ADS, SearchType::MASTERS, SearchType::SERVICES, SearchType::GLOBAL],
            self::PRICE_ASC, self::PRICE_DESC => [SearchType::ADS, SearchType::MASTERS, SearchType::SERVICES],
            self::DATE => [SearchType::ADS, SearchType::GLOBAL],
            self::RATING => [SearchType::ADS, SearchType::MASTERS, SearchType::SERVICES],
            self::POPULARITY => [SearchType::ADS, SearchType::MASTERS, SearchType::SERVICES],
            self::DISTANCE => [SearchType::ADS, SearchType::MASTERS],
            self::EXPERIENCE => [SearchType::MASTERS],
            self::ACTIVITY => [SearchType::MASTERS],
            self::SCORE => [SearchType::RECOMMENDATIONS],
        };

        return in_array($searchType, $applicableTypes);
    }

    /**
     * Получить SQL выражение для сортировки
     */
    public function getSqlOrderBy(string $tableAlias = ''): array
    {
        $prefix = $tableAlias ? $tableAlias . '.' : '';

        return match($this) {
            self::RELEVANCE => ['relevance_score' => 'desc'],
            self::PRICE_ASC => [$prefix . 'price' => 'asc'],
            self::PRICE_DESC => [$prefix . 'price' => 'desc'],
            self::DATE => [$prefix . 'created_at' => 'desc'],
            self::RATING => [$prefix . 'rating' => 'desc', $prefix . 'reviews_count' => 'desc'],
            self::POPULARITY => [$prefix . 'views_count' => 'desc', $prefix . 'bookings_count' => 'desc'],
            self::DISTANCE => ['distance' => 'asc'],
            self::EXPERIENCE => [$prefix . 'experience_years' => 'desc'],
            self::ACTIVITY => [$prefix . 'last_activity_at' => 'desc'],
            self::SCORE => ['recommendation_score' => 'desc'],
        };
    }

    /**
     * Требует ли сортировка дополнительных вычислений
     */
    public function requiresComputation(): bool
    {
        return match($this) {
            self::RELEVANCE, self::DISTANCE, self::SCORE => true,
            default => false,
        };
    }

    /**
     * Получить направление сортировки
     */
    public function getDirection(): string
    {
        return match($this) {
            self::PRICE_ASC, self::DISTANCE => 'asc',
            default => 'desc',
        };
    }

    /**
     * Можно ли инвертировать сортировку
     */
    public function canInvert(): bool
    {
        return match($this) {
            self::RELEVANCE, self::SCORE => false,
            default => true,
        };
    }

    /**
     * Получить инвертированную сортировку
     */
    public function invert(): ?self
    {
        if (!$this->canInvert()) {
            return null;
        }

        return match($this) {
            self::PRICE_ASC => self::PRICE_DESC,
            self::PRICE_DESC => self::PRICE_ASC,
            default => $this,
        };
    }

    /**
     * Получить сортировку по умолчанию для типа поиска
     */
    public static function getDefaultForSearchType(SearchType $searchType): self
    {
        return match($searchType) {
            SearchType::ADS => self::RELEVANCE,
            SearchType::MASTERS => self::RATING,
            SearchType::SERVICES => self::POPULARITY,
            SearchType::GLOBAL => self::RELEVANCE,
            SearchType::RECOMMENDATIONS => self::SCORE,
        };
    }

    /**
     * Получить все доступные сортировки для типа поиска
     */
    public static function getAvailableForSearchType(SearchType $searchType): array
    {
        return array_filter(
            self::cases(),
            fn($sortBy) => $sortBy->isApplicableForSearchType($searchType)
        );
    }

    /**
     * Получить приоритет сортировки (для UI)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::RELEVANCE => 1,
            self::RATING => 2,
            self::PRICE_ASC, self::PRICE_DESC => 3,
            self::DATE => 4,
            self::POPULARITY => 5,
            self::DISTANCE => 6,
            self::EXPERIENCE => 7,
            self::ACTIVITY => 8,
            self::SCORE => 9,
        };
    }

    /**
     * Требует ли сортировка геоданные
     */
    public function requiresGeoData(): bool
    {
        return $this === self::DISTANCE;
    }

    /**
     * Можно ли кешировать результаты с этой сортировкой
     */
    public function isCacheable(): bool
    {
        return match($this) {
            self::DISTANCE, self::ACTIVITY => false, // Зависят от текущего местоположения/времени
            default => true,
        };
    }
}