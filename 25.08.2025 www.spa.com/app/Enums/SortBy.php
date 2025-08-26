<?php

namespace App\Enums;

/**
 * Варианты сортировки для поиска
 */
enum SortBy: string
{
    case RELEVANCE = 'relevance';           // По релевантности
    case RATING = 'rating';                 // По рейтингу
    case PRICE_ASC = 'price_asc';          // По цене (возрастание)
    case PRICE_DESC = 'price_desc';        // По цене (убывание)
    case DATE_ASC = 'date_asc';            // По дате (старые первые)
    case DATE_DESC = 'date_desc';          // По дате (новые первые)
    case NAME_ASC = 'name_asc';            // По названию (А-Я)
    case NAME_DESC = 'name_desc';          // По названию (Я-А)
    case DISTANCE = 'distance';            // По расстоянию
    case POPULARITY = 'popularity';        // По популярности
    case REVIEWS = 'reviews';              // По количеству отзывов
    case EXPERIENCE = 'experience';        // По опыту работы
    case ACTIVITY = 'activity';            // По активности
    case VIEWS = 'views';                  // По просмотрам
    case DURATION = 'duration';            // По длительности
    case NOVELTY = 'novelty';             // По новизне

    /**
     * Получить читаемое название сортировки
     */
    public function getLabel(): string
    {
        return match($this) {
            self::RELEVANCE => 'По релевантности',
            self::RATING => 'По рейтингу',
            self::PRICE_ASC => 'Цена: по возрастанию',
            self::PRICE_DESC => 'Цена: по убыванию',
            self::DATE_ASC => 'Дата: старые первые',
            self::DATE_DESC => 'Дата: новые первые',
            self::NAME_ASC => 'Название: А-Я',
            self::NAME_DESC => 'Название: Я-А',
            self::DISTANCE => 'По расстоянию',
            self::POPULARITY => 'По популярности',
            self::REVIEWS => 'По отзывам',
            self::EXPERIENCE => 'По опыту',
            self::ACTIVITY => 'По активности',
            self::VIEWS => 'По просмотрам',
            self::DURATION => 'По длительности',
            self::NOVELTY => 'По новизне',
        };
    }

    /**
     * Получить описание сортировки
     */
    public function getDescription(): string
    {
        return match($this) {
            self::RELEVANCE => 'Сначала наиболее подходящие результаты',
            self::RATING => 'Сначала с высоким рейтингом',
            self::PRICE_ASC => 'Сначала самые дешевые',
            self::PRICE_DESC => 'Сначала самые дорогие',
            self::DATE_ASC => 'Сначала давно созданные',
            self::DATE_DESC => 'Сначала недавно созданные',
            self::NAME_ASC => 'В алфавитном порядке',
            self::NAME_DESC => 'В обратном алфавитном порядке',
            self::DISTANCE => 'Сначала ближайшие',
            self::POPULARITY => 'Сначала популярные',
            self::REVIEWS => 'Сначала с большим количеством отзывов',
            self::EXPERIENCE => 'Сначала опытные мастера',
            self::ACTIVITY => 'Сначала активные пользователи',
            self::VIEWS => 'Сначала часто просматриваемые',
            self::DURATION => 'Сначала короткие услуги',
            self::NOVELTY => 'Сначала новые результаты',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::RELEVANCE => '🎯',
            self::RATING => '⭐',
            self::PRICE_ASC => '💰⬆️',
            self::PRICE_DESC => '💰⬇️',
            self::DATE_ASC => '📅⬆️',
            self::DATE_DESC => '📅⬇️',
            self::NAME_ASC => '🔤⬆️',
            self::NAME_DESC => '🔤⬇️',
            self::DISTANCE => '📍',
            self::POPULARITY => '🔥',
            self::REVIEWS => '💬',
            self::EXPERIENCE => '👨‍💼',
            self::ACTIVITY => '⚡',
            self::VIEWS => '👁️',
            self::DURATION => '⏱️',
            self::NOVELTY => '✨',
        };
    }

    /**
     * Получить направление сортировки
     */
    public function getDirection(): string
    {
        return match($this) {
            self::PRICE_ASC, self::DATE_ASC, self::NAME_ASC => 'asc',
            self::PRICE_DESC, self::DATE_DESC, self::NAME_DESC => 'desc',
            default => 'desc', // По умолчанию убывание для большинства метрик
        };
    }

    /**
     * Получить поле для сортировки в БД
     */
    public function getDatabaseField(): string
    {
        return match($this) {
            self::RELEVANCE => '_score',
            self::RATING => 'rating',
            self::PRICE_ASC, self::PRICE_DESC => 'price',
            self::DATE_ASC, self::DATE_DESC => 'created_at',
            self::NAME_ASC, self::NAME_DESC => 'name',
            self::DISTANCE => 'distance',
            self::POPULARITY => 'popularity_score',
            self::REVIEWS => 'reviews_count',
            self::EXPERIENCE => 'experience_years',
            self::ACTIVITY => 'last_activity_at',
            self::VIEWS => 'views_count',
            self::DURATION => 'duration',
            self::NOVELTY => 'created_at',
        };
    }

    /**
     * Проверить применимость к типу поиска
     */
    public function isApplicableForSearchType(SearchType $searchType): bool
    {
        return match($this) {
            self::RELEVANCE => true, // Всегда применима
            
            self::RATING => in_array($searchType, [
                SearchType::ADS, 
                SearchType::MASTERS, 
                SearchType::RECOMMENDATIONS
            ]),
            
            self::PRICE_ASC, self::PRICE_DESC => in_array($searchType, [
                SearchType::ADS, 
                SearchType::MASTERS, 
                SearchType::SERVICES
            ]),
            
            self::DATE_ASC, self::DATE_DESC => true, // Всегда применима
            
            self::NAME_ASC, self::NAME_DESC => in_array($searchType, [
                SearchType::MASTERS, 
                SearchType::SERVICES
            ]),
            
            self::DISTANCE => in_array($searchType, [
                SearchType::ADS, 
                SearchType::MASTERS
            ]),
            
            self::POPULARITY => true, // Всегда применима
            
            self::REVIEWS => in_array($searchType, [
                SearchType::ADS, 
                SearchType::MASTERS
            ]),
            
            self::EXPERIENCE => $searchType === SearchType::MASTERS,
            
            self::ACTIVITY => in_array($searchType, [
                SearchType::MASTERS, 
                SearchType::GLOBAL
            ]),
            
            self::VIEWS => in_array($searchType, [
                SearchType::ADS, 
                SearchType::MASTERS, 
                SearchType::SERVICES
            ]),
            
            self::DURATION => $searchType === SearchType::SERVICES,
            
            self::NOVELTY => in_array($searchType, [
                SearchType::RECOMMENDATIONS, 
                SearchType::GLOBAL
            ]),
        };
    }

    /**
     * Требует ли геолокацию
     */
    public function requiresLocation(): bool
    {
        return $this === self::DISTANCE;
    }

    /**
     * Требует ли авторизации для персонализации
     */
    public function requiresAuth(): bool
    {
        return in_array($this, [
            self::RELEVANCE, // Для персонализированной релевантности
            self::NOVELTY,   // Для определения новизны относительно пользователя
        ]);
    }

    /**
     * Получить SQL выражение для сортировки
     */
    public function getSqlExpression(?string $tableAlias = null): string
    {
        $prefix = $tableAlias ? $tableAlias . '.' : '';
        
        return match($this) {
            self::RELEVANCE => 'COALESCE(relevance_score, 0) DESC, ' . $prefix . 'created_at DESC',
            self::RATING => 'COALESCE(' . $prefix . 'rating, 0) DESC',
            self::PRICE_ASC => $prefix . 'price ASC',
            self::PRICE_DESC => $prefix . 'price DESC',
            self::DATE_ASC => $prefix . 'created_at ASC',
            self::DATE_DESC => $prefix . 'created_at DESC',
            self::NAME_ASC => $prefix . 'name ASC',
            self::NAME_DESC => $prefix . 'name DESC',
            self::DISTANCE => 'distance ASC', // Вычисляется отдельно
            self::POPULARITY => 'COALESCE(' . $prefix . 'popularity_score, 0) DESC',
            self::REVIEWS => 'COALESCE(' . $prefix . 'reviews_count, 0) DESC',
            self::EXPERIENCE => 'COALESCE(' . $prefix . 'experience_years, 0) DESC',
            self::ACTIVITY => $prefix . 'last_activity_at DESC NULLS LAST',
            self::VIEWS => 'COALESCE(' . $prefix . 'views_count, 0) DESC',
            self::DURATION => $prefix . 'duration ASC',
            self::NOVELTY => $prefix . 'created_at DESC',
        };
    }

    /**
     * Получить вес для комбинированной сортировки
     */
    public function getWeight(): float
    {
        return match($this) {
            self::RELEVANCE => 1.0,
            self::RATING => 0.8,
            self::POPULARITY => 0.7,
            self::REVIEWS => 0.6,
            self::ACTIVITY => 0.5,
            self::VIEWS => 0.4,
            self::EXPERIENCE => 0.6,
            self::DISTANCE => 0.9, // Высокий вес для геолокации
            default => 0.3,
        };
    }

    /**
     * Можно ли комбинировать с другой сортировкой
     */
    public function canCombineWith(SortBy $other): bool
    {
        // Нельзя комбинировать разные направления одного поля
        if ($this->getDatabaseField() === $other->getDatabaseField()) {
            return false;
        }

        // Нельзя комбинировать противоположные сортировки
        $conflicting = [
            [self::PRICE_ASC, self::PRICE_DESC],
            [self::DATE_ASC, self::DATE_DESC],
            [self::NAME_ASC, self::NAME_DESC],
        ];

        foreach ($conflicting as $pair) {
            if (in_array($this, $pair) && in_array($other, $pair)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить приоритет сортировки (чем меньше, тем выше приоритет)
     */
    public function getPriority(): int
    {
        return match($this) {
            self::RELEVANCE => 1,
            self::DISTANCE => 2,
            self::RATING => 3,
            self::POPULARITY => 4,
            self::PRICE_ASC, self::PRICE_DESC => 5,
            self::REVIEWS => 6,
            self::EXPERIENCE => 7,
            self::ACTIVITY => 8,
            self::VIEWS => 9,
            self::DATE_ASC, self::DATE_DESC => 10,
            self::NAME_ASC, self::NAME_DESC => 11,
            self::DURATION => 12,
            self::NOVELTY => 13,
        };
    }

    /**
     * Все варианты для выборки
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $sort) {
            $options[$sort->value] = $sort->getLabel();
        }
        return $options;
    }

    /**
     * Получить варианты для конкретного типа поиска
     */
    public static function getOptionsForSearchType(SearchType $searchType): array
    {
        $options = [];
        foreach (self::cases() as $sort) {
            if ($sort->isApplicableForSearchType($searchType)) {
                $options[$sort->value] = $sort->getLabel();
            }
        }
        
        // Сортируем по приоритету
        uasort($options, function($a, $b) use ($searchType) {
            $sortA = self::from(array_search($a, $options));
            $sortB = self::from(array_search($b, $options));
            return $sortA->getPriority() <=> $sortB->getPriority();
        });
        
        return $options;
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
            SearchType::RECOMMENDATIONS => self::NOVELTY,
        };
    }

    /**
     * Создать из строки с fallback
     */
    public static function tryFrom(string $value): ?self
    {
        try {
            return self::from($value);
        } catch (\ValueError) {
            return null;
        }
    }

    /**
     * Получить популярные варианты сортировки
     */
    public static function getPopular(): array
    {
        return [
            self::RELEVANCE,
            self::RATING,
            self::PRICE_ASC,
            self::PRICE_DESC,
            self::DISTANCE,
            self::DATE_DESC,
        ];
    }

    /**
     * Получить комбинацию сортировок для улучшения релевантности
     */
    public static function getSmartCombination(SearchType $searchType, ?array $filters = []): array
    {
        $primary = self::getDefaultForSearchType($searchType);
        $combination = [$primary];

        // Добавляем вторичную сортировку в зависимости от контекста
        match($searchType) {
            SearchType::ADS => $combination[] = self::RATING,
            SearchType::MASTERS => $combination[] = self::REVIEWS,
            SearchType::SERVICES => $combination[] = self::RATING,
            default => null,
        };

        // Если есть геофильтры, добавляем сортировку по расстоянию
        if (!empty($filters['location']) && $primary !== self::DISTANCE) {
            array_unshift($combination, self::DISTANCE);
        }

        return array_unique($combination);
    }
}