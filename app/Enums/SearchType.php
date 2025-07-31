<?php

namespace App\Enums;

/**
 * Типы поиска в системе
 */
enum SearchType: string
{
    case ADS = 'ads';               // Поиск объявлений
    case MASTERS = 'masters';       // Поиск мастеров
    case SERVICES = 'services';     // Поиск услуг
    case GLOBAL = 'global';         // Глобальный поиск
    case RECOMMENDATIONS = 'recommendations'; // Рекомендации

    /**
     * Получить читаемое название типа
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
     * Получить описание типа поиска
     */
    public function getDescription(): string
    {
        return match($this) {
            self::ADS => 'Поиск среди активных объявлений мастеров',
            self::MASTERS => 'Поиск мастеров по имени, специализации и услугам',
            self::SERVICES => 'Поиск услуг по названию и категориям',
            self::GLOBAL => 'Поиск по всем разделам сайта',
            self::RECOMMENDATIONS => 'Персонализированные рекомендации',
        ];
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ADS => '📋',
            self::MASTERS => '👨‍💼',
            self::SERVICES => '⚡',
            self::GLOBAL => '🔍',
            self::RECOMMENDATIONS => '✨',
        ];
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::ADS => '#3B82F6',        // blue
            self::MASTERS => '#10B981',    // green
            self::SERVICES => '#F59E0B',   // amber
            self::GLOBAL => '#8B5CF6',     // violet
            self::RECOMMENDATIONS => '#EC4899', // pink
        };
    }

    /**
     * Получить основную модель для поиска
     */
    public function getModel(): string
    {
        return match($this) {
            self::ADS => 'App\\Models\\Ad',
            self::MASTERS => 'App\\Models\\User',
            self::SERVICES => 'App\\Models\\Service',
            self::GLOBAL => 'mixed',
            self::RECOMMENDATIONS => 'mixed',
        };
    }

    /**
     * Получить поля для поиска
     */
    public function getSearchFields(): array
    {
        return match($this) {
            self::ADS => [
                'title' => 'Заголовок',
                'description' => 'Описание',
                'specialty' => 'Специализация',
                'additional_features' => 'Дополнительные услуги',
                'city' => 'Город',
            ],
            self::MASTERS => [
                'name' => 'Имя',
                'specialty' => 'Специализация',
                'description' => 'Описание',
                'city' => 'Город',
                'services' => 'Услуги',
            ],
            self::SERVICES => [
                'name' => 'Название',
                'description' => 'Описание',
                'category' => 'Категория',
                'tags' => 'Теги',
            ],
            self::GLOBAL => [
                'query' => 'Общий поиск',
            ],
            self::RECOMMENDATIONS => [
                'preferences' => 'Предпочтения',
                'history' => 'История',
            ],
        };
    }

    /**
     * Получить доступные фильтры
     */
    public function getAvailableFilters(): array
    {
        return match($this) {
            self::ADS => [
                'city' => 'Город',
                'price_range' => 'Цена',
                'rating' => 'Рейтинг',
                'experience' => 'Опыт работы',
                'service_type' => 'Тип услуги',
                'availability' => 'Доступность',
                'ad_type' => 'Тип объявления',
                'work_format' => 'Формат работы',
            ],
            self::MASTERS => [
                'city' => 'Город',
                'specialty' => 'Специализация',
                'rating' => 'Рейтинг',
                'experience' => 'Опыт',
                'price_range' => 'Цена',
                'availability' => 'Доступность',
                'verified' => 'Проверенные',
                'distance' => 'Расстояние',
            ],
            self::SERVICES => [
                'category' => 'Категория',
                'price_range' => 'Цена',
                'duration' => 'Продолжительность',
                'popularity' => 'Популярность',
            ],
            self::GLOBAL => [
                'type' => 'Тип контента',
                'date' => 'Дата',
                'relevance' => 'Релевантность',
            ],
            self::RECOMMENDATIONS => [
                'type' => 'Тип рекомендаций',
                'novelty' => 'Новизна',
                'relevance' => 'Персонализация',
            ],
        ];
    }

    /**
     * Получить варианты сортировки
     */
    public function getSortOptions(): array
    {
        return match($this) {
            self::ADS => [
                'relevance' => 'По релевантности',
                'rating' => 'По рейтингу',
                'price_asc' => 'По цене (возрастание)',
                'price_desc' => 'По цене (убывание)',
                'created_at' => 'По дате создания',
                'views' => 'По популярности',
                'distance' => 'По расстоянию',
            ],
            self::MASTERS => [
                'relevance' => 'По релевантности',
                'rating' => 'По рейтингу',
                'experience' => 'По опыту',
                'reviews' => 'По количеству отзывов',
                'price_asc' => 'По цене (возрастание)',
                'price_desc' => 'По цене (убывание)',
                'distance' => 'По расстоянию',
                'activity' => 'По активности',
            ],
            self::SERVICES => [
                'relevance' => 'По релевантности',
                'name' => 'По названию',
                'price_asc' => 'По цене (возрастание)',
                'price_desc' => 'По цене (убывание)',
                'popularity' => 'По популярности',
                'duration' => 'По длительности',
            ],
            self::GLOBAL => [
                'relevance' => 'По релевантности',
                'date' => 'По дате',
                'popularity' => 'По популярности',
            ],
            self::RECOMMENDATIONS => [
                'relevance' => 'По персонализации',
                'novelty' => 'По новизне',
                'rating' => 'По рейтингу',
            ],
        ];
    }

    /**
     * Получить лимит результатов по умолчанию
     */
    public function getDefaultLimit(): int
    {
        return match($this) {
            self::ADS => 20,
            self::MASTERS => 15,
            self::SERVICES => 30,
            self::GLOBAL => 10,
            self::RECOMMENDATIONS => 12,
        };
    }

    /**
     * Поддерживает ли геопоиск
     */
    public function supportsGeoSearch(): bool
    {
        return match($this) {
            self::ADS, self::MASTERS => true,
            default => false,
        };
    }

    /**
     * Поддерживает ли фасетный поиск
     */
    public function supportsFacetedSearch(): bool
    {
        return match($this) {
            self::ADS, self::MASTERS, self::SERVICES => true,
            default => false,
        };
    }

    /**
     * Требует ли авторизации для персонализации
     */
    public function requiresAuth(): bool
    {
        return match($this) {
            self::RECOMMENDATIONS => true,
            default => false,
        };
    }

    /**
     * Поддерживает ли автодополнение
     */
    public function supportsAutocomplete(): bool
    {
        return match($this) {
            self::GLOBAL => false,
            default => true,
        };
    }

    /**
     * Получить ключ для кэширования
     */
    public function getCacheKey(string $query, array $filters = []): string
    {
        $filtersHash = md5(serialize($filters));
        return "search:{$this->value}:" . md5($query) . ":{$filtersHash}";
    }

    /**
     * Получить время кэширования (в минутах)
     */
    public function getCacheTTL(): int
    {
        return match($this) {
            self::ADS => 15,           // 15 минут
            self::MASTERS => 30,       // 30 минут
            self::SERVICES => 60,      // 1 час
            self::GLOBAL => 10,        // 10 минут
            self::RECOMMENDATIONS => 5, // 5 минут
        };
    }

    /**
     * Получить минимальную длину запроса
     */
    public function getMinQueryLength(): int
    {
        return match($this) {
            self::GLOBAL => 1,
            self::RECOMMENDATIONS => 0,
            default => 2,
        };
    }

    /**
     * Получить приоритет типа поиска
     */
    public function getPriority(): int
    {
        return match($this) {
            self::ADS => 1,            // Самый высокий приоритет
            self::MASTERS => 2,
            self::SERVICES => 3,
            self::RECOMMENDATIONS => 4,
            self::GLOBAL => 5,         // Самый низкий приоритет
        };
    }

    /**
     * Все типы для выборки
     */
    public static function options(): array
    {
        $types = [];
        foreach (self::cases() as $type) {
            $types[$type->value] = $type->getLabel();
        }
        return $types;
    }

    /**
     * Получить популярные типы поиска
     */
    public static function getPopularTypes(): array
    {
        return [self::ADS, self::MASTERS, self::SERVICES];
    }

    /**
     * Получить тип по умолчанию
     */
    public static function default(): self
    {
        return self::ADS;
    }

    /**
     * Определить тип поиска по контексту
     */
    public static function detectFromContext(string $route = null, array $params = []): self
    {
        if (!$route) {
            return self::default();
        }

        return match(true) {
            str_contains($route, 'ads') => self::ADS,
            str_contains($route, 'masters') => self::MASTERS,
            str_contains($route, 'services') => self::SERVICES,
            str_contains($route, 'search') => self::GLOBAL,
            default => self::default(),
        };
    }
}