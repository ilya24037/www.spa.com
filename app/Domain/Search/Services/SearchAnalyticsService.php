<?php

namespace App\Domain\Search\Services;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Enums\SortBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

/**
 * Унифицированный сервис аналитики и валидации поиска
 * Консолидирует 4→1:
 * - SearchAnalytics.php
 * - SearchValidator.php
 * - SearchSuggestionProvider.php
 * - SearchEngineManager.php (частично)
 * 
 * Принцип KISS: вся аналитика и валидация в одном месте
 */
class SearchAnalyticsService
{
    /**
     * Валидировать параметры поиска
     */
    public function validateSearchParams(
        string $query,
        SearchType $type,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        int $page = 1,
        int $perPage = 20
    ): void {
        
        $rules = [
            'query' => 'required|string|min:1|max:500',
            'page' => 'required|integer|min:1|max:1000',
            'perPage' => 'required|integer|min:1|max:100'
        ];
        
        $data = [
            'query' => $query,
            'page' => $page,
            'perPage' => $perPage
        ];
        
        $validator = Validator::make($data, $rules, [
            'query.required' => 'Поисковый запрос не может быть пустым',
            'query.min' => 'Поисковый запрос должен содержать хотя бы 1 символ',
            'query.max' => 'Поисковый запрос не может быть длиннее 500 символов',
            'page.min' => 'Номер страницы должен быть больше 0',
            'page.max' => 'Номер страницы не может быть больше 1000',
            'perPage.min' => 'Количество элементов на странице должно быть больше 0',
            'perPage.max' => 'Количество элементов на странице не может быть больше 100'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        // Дополнительная валидация по типу поиска
        $this->validateSearchType($type, $filters);
        
        // Валидация фильтров
        $this->validateFilters($filters, $type);
    }

    /**
     * Валидировать продвинутые критерии поиска
     */
    public function validateAdvancedCriteria(array $criteria): void
    {
        $rules = [
            'query' => 'nullable|string|max:500',
            'type' => 'nullable|string|in:ads,masters,services,global',
            'filters' => 'nullable|array',
            'sortBy' => 'nullable|string',
            'page' => 'nullable|integer|min:1|max:1000',
            'perPage' => 'nullable|integer|min:1|max:100',
            'location' => 'nullable|array',
            'location.lat' => 'required_with:location|numeric|between:-90,90',
            'location.lng' => 'required_with:location|numeric|between:-180,180',
            'location.radius' => 'nullable|numeric|min:0.1|max:1000'
        ];
        
        $validator = Validator::make($criteria, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Валидировать геопоиск
     */
    public function validateGeoSearch(array $location, float $radius, SearchType $type): void
    {
        $rules = [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180'
        ];
        
        $validator = Validator::make($location, $rules, [
            'lat.required' => 'Не указана широта',
            'lat.between' => 'Широта должна быть в диапазоне от -90 до 90',
            'lng.required' => 'Не указана долгота',
            'lng.between' => 'Долгота должна быть в диапазоне от -180 до 180'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        if ($radius <= 0 || $radius > 1000) {
            throw new \InvalidArgumentException('Радиус поиска должен быть от 0.1 до 1000 км');
        }
        
        if (!$type->supportsGeoSearch()) {
            throw new \InvalidArgumentException("Тип поиска {$type->value} не поддерживает геопоиск");
        }
    }

    /**
     * Логировать поисковый запрос
     */
    public function logSearchQuery(
        string $query,
        SearchType $type,
        array $filters = [],
        SortBy $sortBy = SortBy::RELEVANCE,
        ?int $userId = null
    ): void {
        
        try {
            $logData = [
                'query' => $query,
                'type' => $type->value,
                'filters' => $filters,
                'sort_by' => $sortBy->value,
                'user_id' => $userId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now(),
                'session_id' => session()->getId()
            ];
            
            // Логируем в файл
            Log::channel('search')->info('Search query', $logData);
            
            // Сохраняем в кеш для быстрого доступа к статистике
            $this->updateSearchStatistics($query, $type, $userId);
            
            // Обновляем популярные запросы
            $this->updatePopularQueries($query, $type);
            
        } catch (\Exception $e) {
            Log::error('Failed to log search query', [
                'error' => $e->getMessage(),
                'query' => $query,
                'type' => $type->value
            ]);
        }
    }

    /**
     * Логировать результаты поиска
     */
    public function logSearchResults(string $query, SearchType $type, int $resultCount): void
    {
        try {
            $logData = [
                'query' => $query,
                'type' => $type->value,
                'result_count' => $resultCount,
                'timestamp' => now()
            ];
            
            Log::channel('search')->info('Search results', $logData);
            
            // Обновляем статистику результативности
            $this->updateResultStatistics($query, $type, $resultCount);
            
        } catch (\Exception $e) {
            Log::error('Failed to log search results', [
                'error' => $e->getMessage(),
                'query' => $query,
                'result_count' => $resultCount
            ]);
        }
    }

    /**
     * Трекинг кликов по результатам поиска
     */
    public function trackSearchClick(string $query, SearchType $type, int $position, int $itemId): void
    {
        try {
            $cacheKey = "search_clicks:" . date('Y-m-d');
            
            $clickData = [
                'query' => $query,
                'type' => $type->value,
                'position' => $position,
                'item_id' => $itemId,
                'timestamp' => time()
            ];
            
            // Добавляем в дневную статистику кликов
            $dailyClicks = Cache::get($cacheKey, []);
            $dailyClicks[] = $clickData;
            Cache::put($cacheKey, $dailyClicks, 86400); // 24 часа
            
            // Обновляем CTR статистику
            $this->updateCtrStatistics($query, $type, $position);
            
            Log::channel('search')->info('Search click tracked', $clickData);
            
        } catch (\Exception $e) {
            Log::error('Failed to track search click', [
                'error' => $e->getMessage(),
                'query' => $query,
                'item_id' => $itemId
            ]);
        }
    }

    /**
     * Трекинг конверсий
     */
    public function trackSearchConversion(string $query, SearchType $type, int $itemId, float $value = 0): void
    {
        try {
            $cacheKey = "search_conversions:" . date('Y-m-d');
            
            $conversionData = [
                'query' => $query,
                'type' => $type->value,
                'item_id' => $itemId,
                'value' => $value,
                'timestamp' => time()
            ];
            
            $dailyConversions = Cache::get($cacheKey, []);
            $dailyConversions[] = $conversionData;
            Cache::put($cacheKey, $dailyConversions, 86400);
            
            // Обновляем конверсионную статистику
            $this->updateConversionStatistics($query, $type, $value);
            
            Log::channel('search')->info('Search conversion tracked', $conversionData);
            
        } catch (\Exception $e) {
            Log::error('Failed to track search conversion', [
                'error' => $e->getMessage(),
                'query' => $query,
                'item_id' => $itemId
            ]);
        }
    }

    /**
     * Получить автодополнение
     */
    public function getAutocomplete(string $query, SearchType $type, int $limit = 10): array
    {
        if (mb_strlen($query) < 2) {
            return [];
        }
        
        $cacheKey = "autocomplete:{$type->value}:" . mb_strtolower($query) . ":{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $type, $limit) {
            return $this->buildAutocomplete($query, $type, $limit);
        });
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularQueries(SearchType $type, int $limit = 10): array
    {
        $cacheKey = "popular_queries:{$type->value}:{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $limit) {
            return $this->buildPopularQueries($type, $limit);
        });
    }

    /**
     * Получить связанные запросы
     */
    public function getRelatedQueries(string $query, SearchType $type, int $limit = 5): array
    {
        $cacheKey = "related_queries:" . md5($query . $type->value) . ":{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($query, $type, $limit) {
            return $this->buildRelatedQueries($query, $type, $limit);
        });
    }

    /**
     * Получить поисковые подсказки
     */
    public function getSearchSuggestions(string $query, SearchType $type, array $context = []): array
    {
        $suggestions = [];
        
        // Автодополнение
        $suggestions['autocomplete'] = $this->getAutocomplete($query, $type, 5);
        
        // Популярные запросы если запрос короткий
        if (mb_strlen($query) < 3) {
            $suggestions['popular'] = $this->getPopularQueries($type, 5);
        }
        
        // Связанные запросы
        $suggestions['related'] = $this->getRelatedQueries($query, $type, 5);
        
        // Исправление опечаток
        $suggestions['corrections'] = $this->getSpellingSuggestions($query);
        
        // Персонализированные предложения
        if (isset($context['user_id'])) {
            $suggestions['personalized'] = $this->getPersonalizedSuggestions($context['user_id'], $type);
        }
        
        return array_filter($suggestions);
    }

    /**
     * Получить статистику поиска
     */
    public function getSearchStatistics(array $filters = []): array
    {
        $period = $filters['period'] ?? 'week'; // day, week, month, year
        $type = $filters['type'] ?? null;
        
        $cacheKey = "search_statistics:{$period}:" . ($type ?: 'all');
        
        return Cache::remember($cacheKey, 900, function () use ($period, $type) {
            return $this->buildSearchStatistics($period, $type);
        });
    }

    /**
     * Получить топ запросов без результатов
     */
    public function getZeroResultQueries(SearchType $type, int $limit = 20): array
    {
        $cacheKey = "zero_result_queries:{$type->value}:{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($type, $limit) {
            // Здесь нужен анализ логов поиска
            // Пока возвращаем заглушку
            return [
                'spa массаж ночью',
                'массаж в 3 часа ночи',
                'бесплатный массаж'
            ];
        });
    }

    /**
     * Анализировать эффективность поиска
     */
    public function analyzeSearchEffectiveness(): array
    {
        return [
            'ctr' => $this->calculateAverageCtr(),
            'conversion_rate' => $this->calculateConversionRate(),
            'zero_results_rate' => $this->calculateZeroResultsRate(),
            'average_results_per_query' => $this->calculateAverageResultsPerQuery(),
            'popular_exit_queries' => $this->getPopularExitQueries(),
            'search_refinement_rate' => $this->calculateSearchRefinementRate()
        ];
    }

    // === ПРИВАТНЫЕ МЕТОДЫ ===

    /**
     * Валидировать тип поиска
     */
    private function validateSearchType(SearchType $type, array $filters): void
    {
        // Проверяем поддерживаемые возможности
        $capabilities = $type->getCapabilities();
        
        foreach ($filters as $filterKey => $filterValue) {
            if (!in_array($filterKey, $capabilities['supported_filters'] ?? [])) {
                throw new \InvalidArgumentException("Фильтр '{$filterKey}' не поддерживается для типа поиска '{$type->value}'");
            }
        }
    }

    /**
     * Валидировать фильтры
     */
    private function validateFilters(array $filters, SearchType $type): void
    {
        $filterService = new SearchFilterService();
        $filterService->validate($filters, $type);
    }

    /**
     * Обновить статистику поиска
     */
    private function updateSearchStatistics(string $query, SearchType $type, ?int $userId): void
    {
        $cacheKey = "search_stats:" . date('Y-m-d');
        
        $stats = Cache::get($cacheKey, [
            'total_searches' => 0,
            'unique_queries' => [],
            'searches_by_type' => [],
            'searches_by_user' => []
        ]);
        
        $stats['total_searches']++;
        $stats['unique_queries'][md5($query)] = $query;
        $stats['searches_by_type'][$type->value] = ($stats['searches_by_type'][$type->value] ?? 0) + 1;
        
        if ($userId) {
            $stats['searches_by_user'][$userId] = ($stats['searches_by_user'][$userId] ?? 0) + 1;
        }
        
        Cache::put($cacheKey, $stats, 86400);
    }

    /**
     * Обновить популярные запросы
     */
    private function updatePopularQueries(string $query, SearchType $type): void
    {
        $cacheKey = "popular_queries_raw:{$type->value}";
        
        $queries = Cache::get($cacheKey, []);
        $normalizedQuery = mb_strtolower(trim($query));
        
        if (mb_strlen($normalizedQuery) >= 2) {
            $queries[$normalizedQuery] = ($queries[$normalizedQuery] ?? 0) + 1;
            
            // Оставляем только топ-100 для экономии памяти
            if (count($queries) > 100) {
                arsort($queries);
                $queries = array_slice($queries, 0, 100, true);
            }
            
            Cache::put($cacheKey, $queries, 86400);
        }
    }

    /**
     * Обновить статистику результативности
     */
    private function updateResultStatistics(string $query, SearchType $type, int $resultCount): void
    {
        $cacheKey = "result_stats:" . date('Y-m-d');
        
        $stats = Cache::get($cacheKey, [
            'total_queries' => 0,
            'zero_result_queries' => 0,
            'total_results' => 0
        ]);
        
        $stats['total_queries']++;
        $stats['total_results'] += $resultCount;
        
        if ($resultCount === 0) {
            $stats['zero_result_queries']++;
        }
        
        Cache::put($cacheKey, $stats, 86400);
    }

    /**
     * Обновить CTR статистику
     */
    private function updateCtrStatistics(string $query, SearchType $type, int $position): void
    {
        $cacheKey = "ctr_stats:" . date('Y-m-d');
        
        $stats = Cache::get($cacheKey, [
            'impressions' => 0,
            'clicks' => 0,
            'clicks_by_position' => []
        ]);
        
        $stats['impressions']++;
        $stats['clicks']++;
        $stats['clicks_by_position'][$position] = ($stats['clicks_by_position'][$position] ?? 0) + 1;
        
        Cache::put($cacheKey, $stats, 86400);
    }

    /**
     * Обновить конверсионную статистику
     */
    private function updateConversionStatistics(string $query, SearchType $type, float $value): void
    {
        $cacheKey = "conversion_stats:" . date('Y-m-d');
        
        $stats = Cache::get($cacheKey, [
            'conversions' => 0,
            'total_value' => 0
        ]);
        
        $stats['conversions']++;
        $stats['total_value'] += $value;
        
        Cache::put($cacheKey, $stats, 86400);
    }

    /**
     * Построить автодополнение
     */
    private function buildAutocomplete(string $query, SearchType $type, int $limit): array
    {
        $suggestions = [];
        
        // Получаем популярные запросы начинающиеся с введенного текста
        $popularQueries = $this->getPopularQueries($type, 50);
        
        foreach ($popularQueries as $popularQuery) {
            if (mb_stripos($popularQuery, $query) === 0) {
                $suggestions[] = $popularQuery;
                
                if (count($suggestions) >= $limit) {
                    break;
                }
            }
        }
        
        // Дополняем из базы данных если мало результатов
        if (count($suggestions) < $limit) {
            $dbSuggestions = $this->getDbAutocomplete($query, $type, $limit - count($suggestions));
            $suggestions = array_merge($suggestions, $dbSuggestions);
        }
        
        return array_unique($suggestions);
    }

    /**
     * Построить популярные запросы
     */
    private function buildPopularQueries(SearchType $type, int $limit): array
    {
        $cacheKey = "popular_queries_raw:{$type->value}";
        $queries = Cache::get($cacheKey, []);
        
        if (empty($queries)) {
            // Если кеш пуст, возвращаем дефолтные популярные запросы
            return $this->getDefaultPopularQueries($type);
        }
        
        arsort($queries);
        return array_slice(array_keys($queries), 0, $limit);
    }

    /**
     * Построить связанные запросы
     */
    private function buildRelatedQueries(string $query, SearchType $type, int $limit): array
    {
        // Простая логика на основе ключевых слов
        $keywords = explode(' ', mb_strtolower($query));
        $related = [];
        
        $popularQueries = $this->getPopularQueries($type, 50);
        
        foreach ($popularQueries as $popularQuery) {
            $popularKeywords = explode(' ', mb_strtolower($popularQuery));
            $intersection = array_intersect($keywords, $popularKeywords);
            
            // Если есть общие слова, но запрос не идентичен
            if (!empty($intersection) && $popularQuery !== $query) {
                $related[] = $popularQuery;
                
                if (count($related) >= $limit) {
                    break;
                }
            }
        }
        
        return $related;
    }

    /**
     * Получить предложения исправления опечаток
     */
    private function getSpellingSuggestions(string $query): array
    {
        // Простая логика исправления опечаток
        $corrections = [];
        
        // Популярные замены для русского языка
        $commonMistakes = [
            'масаж' => 'массаж',
            'релакс' => 'релаксация',
            'спа' => 'СПА'
        ];
        
        foreach ($commonMistakes as $mistake => $correction) {
            if (mb_stripos($query, $mistake) !== false) {
                $corrections[] = str_ireplace($mistake, $correction, $query);
            }
        }
        
        return array_unique($corrections);
    }

    /**
     * Получить персонализированные предложения
     */
    private function getPersonalizedSuggestions(int $userId, SearchType $type): array
    {
        $userHistoryKey = "user_search_history:{$userId}:{$type->value}";
        $userHistory = Cache::get($userHistoryKey, []);
        
        if (empty($userHistory)) {
            return [];
        }
        
        // Возвращаем последние уникальные запросы пользователя
        return array_slice(array_unique($userHistory), -5);
    }

    /**
     * Построить статистику поиска
     */
    private function buildSearchStatistics(string $period, ?string $type): array
    {
        $stats = [
            'period' => $period,
            'type' => $type,
            'total_searches' => 0,
            'unique_queries' => 0,
            'zero_result_rate' => 0,
            'average_ctr' => 0,
            'conversion_rate' => 0,
            'top_queries' => [],
            'search_trends' => []
        ];
        
        // Получаем данные за указанный период
        $dates = $this->getDatesByPeriod($period);
        
        foreach ($dates as $date) {
            $dailyStats = Cache::get("search_stats:{$date}", []);
            $stats['total_searches'] += $dailyStats['total_searches'] ?? 0;
            $stats['unique_queries'] += count($dailyStats['unique_queries'] ?? []);
        }
        
        return $stats;
    }

    /**
     * Получить автодополнение из БД
     */
    private function getDbAutocomplete(string $query, SearchType $type, int $limit): array
    {
        // Заглушка для поиска в БД
        return [];
    }

    /**
     * Получить дефолтные популярные запросы
     */
    private function getDefaultPopularQueries(SearchType $type): array
    {
        switch ($type) {
            case SearchType::ADS:
                return [
                    'массаж релакс',
                    'классический массаж',
                    'антицеллюлитный массаж',
                    'спортивный массаж',
                    'расслабляющий массаж'
                ];
                
            case SearchType::MASTERS:
                return [
                    'мастер массажа',
                    'опытный массажист',
                    'сертифицированный массажист'
                ];
                
            default:
                return [];
        }
    }

    /**
     * Вычислить средний CTR
     */
    private function calculateAverageCtr(): float
    {
        $ctrStats = Cache::get("ctr_stats:" . date('Y-m-d'), []);
        
        $impressions = $ctrStats['impressions'] ?? 0;
        $clicks = $ctrStats['clicks'] ?? 0;
        
        return $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
    }

    /**
     * Вычислить коэффициент конверсии
     */
    private function calculateConversionRate(): float
    {
        $conversionStats = Cache::get("conversion_stats:" . date('Y-m-d'), []);
        $searchStats = Cache::get("search_stats:" . date('Y-m-d'), []);
        
        $conversions = $conversionStats['conversions'] ?? 0;
        $totalSearches = $searchStats['total_searches'] ?? 0;
        
        return $totalSearches > 0 ? ($conversions / $totalSearches) * 100 : 0;
    }

    /**
     * Вычислить долю запросов без результатов
     */
    private function calculateZeroResultsRate(): float
    {
        $resultStats = Cache::get("result_stats:" . date('Y-m-d'), []);
        
        $zeroResults = $resultStats['zero_result_queries'] ?? 0;
        $totalQueries = $resultStats['total_queries'] ?? 0;
        
        return $totalQueries > 0 ? ($zeroResults / $totalQueries) * 100 : 0;
    }

    /**
     * Вычислить среднее количество результатов на запрос
     */
    private function calculateAverageResultsPerQuery(): float
    {
        $resultStats = Cache::get("result_stats:" . date('Y-m-d'), []);
        
        $totalResults = $resultStats['total_results'] ?? 0;
        $totalQueries = $resultStats['total_queries'] ?? 0;
        
        return $totalQueries > 0 ? $totalResults / $totalQueries : 0;
    }

    /**
     * Получить популярные запросы без результатов
     */
    private function getPopularExitQueries(): array
    {
        // Заглушка - здесь нужен анализ поведенческих данных
        return [
            'бесплатный массаж',
            'массаж ночью',
            'дешевый массаж'
        ];
    }

    /**
     * Вычислить долю уточняющих запросов
     */
    private function calculateSearchRefinementRate(): float
    {
        // Заглушка - здесь нужен анализ последовательных запросов пользователей
        return 15.5; // %
    }

    /**
     * Получить даты за период
     */
    private function getDatesByPeriod(string $period): array
    {
        $dates = [];
        $today = now()->format('Y-m-d');
        
        switch ($period) {
            case 'day':
                $dates = [$today];
                break;
                
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $dates[] = now()->subDays($i)->format('Y-m-d');
                }
                break;
                
            case 'month':
                for ($i = 29; $i >= 0; $i--) {
                    $dates[] = now()->subDays($i)->format('Y-m-d');
                }
                break;
        }
        
        return $dates;
    }
}