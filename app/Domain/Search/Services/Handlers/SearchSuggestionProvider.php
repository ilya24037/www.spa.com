<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Repositories\SearchRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Провайдер поисковых подсказок и автодополнения
 */
class SearchSuggestionProvider
{
    public function __construct(
        private SearchRepository $repository
    ) {}

    /**
     * Получить автодополнение
     */
    public function getAutocomplete(
        string $query,
        SearchType $type = SearchType::ADS,
        int $limit = 10
    ): array {
        return $this->repository->getAutocomplete($query, $type, $limit);
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularQueries(SearchType $type = SearchType::ADS, int $limit = 10): array
    {
        return $this->repository->getPopularQueries($type, $limit);
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query, SearchType $type = SearchType::ADS): array
    {
        $cacheKey = "related_queries:{$type->value}:" . md5($query);
        
        return Cache::remember($cacheKey, 1800, function () use ($query, $type) {
            return $this->repository->getRelatedQueries($query, $type);
        });
    }

    /**
     * Получить поисковые подсказки
     */
    public function getSearchSuggestions(
        string $query,
        SearchType $type = SearchType::ADS,
        array $context = []
    ): array {
        
        $suggestions = [];

        // Автодополнение
        $autocomplete = $this->getAutocomplete($query, $type, 5);
        $suggestions['autocomplete'] = $autocomplete;

        // Связанные запросы
        if (mb_strlen($query) >= 3) {
            $suggestions['related'] = $this->getRelatedQueries($query, $type);
        }

        // Популярные запросы в категории
        $suggestions['popular'] = $this->getPopularQueries($type, 5);

        // Исправления опечаток
        $suggestions['corrections'] = $this->getSpellingSuggestions($query);

        // Контекстные подсказки
        if (!empty($context)) {
            $suggestions['contextual'] = $this->getContextualSuggestions($query, $type, $context);
        }

        return $suggestions;
    }

    /**
     * Получить подсказки по исправлению опечаток
     */
    public function getSpellingSuggestions(string $query): array
    {
        $cacheKey = "spelling_suggestions:" . md5($query);
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            $suggestions = [];
            
            // Простые замены частых опечаток
            $commonTypos = $this->getCommonTypos();
            
            foreach ($commonTypos as $typo => $correction) {
                if (stripos($query, $typo) !== false) {
                    $suggestions[] = str_ireplace($typo, $correction, $query);
                }
            }

            // Поиск похожих слов в базе данных
            $dbSuggestions = $this->repository->findSimilarQueries($query);
            $suggestions = array_merge($suggestions, $dbSuggestions);

            return array_unique($suggestions);
        });
    }

    /**
     * Получить контекстные подсказки
     */
    public function getContextualSuggestions(string $query, SearchType $type, array $context): array
    {
        $suggestions = [];

        // Подсказки на основе местоположения
        if (isset($context['location'])) {
            $locationSuggestions = $this->getLocationBasedSuggestions($query, $context['location']);
            $suggestions = array_merge($suggestions, $locationSuggestions);
        }

        // Подсказки на основе истории пользователя
        if (isset($context['user_id'])) {
            $historySuggestions = $this->getUserHistoryBasedSuggestions($context['user_id'], $type);
            $suggestions = array_merge($suggestions, $historySuggestions);
        }

        // Подсказки на основе времени
        $timeSuggestions = $this->getTimeBasedSuggestions($query, $type);
        $suggestions = array_merge($suggestions, $timeSuggestions);

        return array_unique($suggestions);
    }

    /**
     * Получить подсказки на основе местоположения
     */
    private function getLocationBasedSuggestions(string $query, array $location): array
    {
        return $this->repository->getLocationBasedSuggestions($query, $location);
    }

    /**
     * Получить подсказки на основе истории пользователя
     */
    private function getUserHistoryBasedSuggestions(int $userId, SearchType $type): array
    {
        $cacheKey = "user_suggestions:{$userId}:{$type->value}";
        
        return Cache::remember($cacheKey, 1800, function () use ($userId, $type) {
            return $this->repository->getUserBasedSuggestions($userId, $type);
        });
    }

    /**
     * Получить подсказки на основе времени
     */
    private function getTimeBasedSuggestions(string $query, SearchType $type): array
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        
        return $this->repository->getTimeBasedSuggestions($query, $type, $hour, $dayOfWeek);
    }

    /**
     * Получить частые опечатки
     */
    private function getCommonTypos(): array
    {
        return [
            // Общие опечатки
            'масаж' => 'массаж',
            'масажист' => 'массажист',
            'релакс' => 'релакс',
            'спа' => 'спа',
            'антицелюлитный' => 'антицеллюлитный',
            'класический' => 'классический',
            'мануальный' => 'мануальный',
            'рефлексо' => 'рефлексо',
            'лимфо' => 'лимфо',
            
            // Английские слова
            'massage' => 'массаж',
            'spa' => 'спа',
            'relax' => 'релакс',
            'wellness' => 'велнес',
        ];
    }

    /**
     * Получить трендовые подсказки
     */
    public function getTrendingSuggestions(SearchType $type, int $limit = 10): array
    {
        $cacheKey = "trending_suggestions:{$type->value}:{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($type, $limit) {
            return $this->repository->getTrendingQueries($type, $limit);
        });
    }

    /**
     * Получить сезонные подсказки
     */
    public function getSeasonalSuggestions(SearchType $type): array
    {
        $month = now()->month;
        $cacheKey = "seasonal_suggestions:{$type->value}:{$month}";
        
        return Cache::remember($cacheKey, 86400, function () use ($type, $month) {
            return $this->repository->getSeasonalSuggestions($type, $month);
        });
    }

    /**
     * Обновить популярность запроса
     */
    public function incrementQueryPopularity(string $query, SearchType $type): void
    {
        $this->repository->incrementQueryCount($query, $type);
        
        // Инвалидируем связанные кеши
        Cache::forget("popular_queries:{$type->value}");
        Cache::forget("trending_suggestions:{$type->value}");
    }

    /**
     * Получить персонализированные подсказки
     */
    public function getPersonalizedSuggestions(int $userId, SearchType $type, int $limit = 10): array
    {
        $cacheKey = "personalized_suggestions:{$userId}:{$type->value}:{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($userId, $type, $limit) {
            // Получаем историю поиска пользователя
            $userHistory = $this->repository->getUserSearchHistory($userId, $type, 50);
            
            // Анализируем паттерны
            $patterns = $this->analyzeUserSearchPatterns($userHistory);
            
            // Генерируем подсказки на основе паттернов
            return $this->generateSuggestionsFromPatterns($patterns, $type, $limit);
        });
    }

    /**
     * Анализировать паттерны поиска пользователя
     */
    private function analyzeUserSearchPatterns(array $history): array
    {
        $patterns = [
            'common_words' => [],
            'categories' => [],
            'time_preferences' => [],
            'location_preferences' => [],
        ];

        foreach ($history as $search) {
            // Анализ общих слов
            $words = explode(' ', mb_strtolower($search['query']));
            foreach ($words as $word) {
                if (mb_strlen($word) > 2) {
                    $patterns['common_words'][$word] = ($patterns['common_words'][$word] ?? 0) + 1;
                }
            }

            // Анализ категорий, времени, локации
            if (isset($search['category'])) {
                $patterns['categories'][$search['category']] = ($patterns['categories'][$search['category']] ?? 0) + 1;
            }
        }

        return $patterns;
    }

    /**
     * Генерировать подсказки на основе паттернов
     */
    private function generateSuggestionsFromPatterns(array $patterns, SearchType $type, int $limit): array
    {
        $suggestions = [];

        // Подсказки на основе частых слов
        if (!empty($patterns['common_words'])) {
            arsort($patterns['common_words']);
            $topWords = array_slice(array_keys($patterns['common_words']), 0, 5);
            
            foreach ($topWords as $word) {
                $relatedQueries = $this->repository->getQueriesContainingWord($word, $type, 3);
                $suggestions = array_merge($suggestions, $relatedQueries);
            }
        }

        return array_slice(array_unique($suggestions), 0, $limit);
    }
}