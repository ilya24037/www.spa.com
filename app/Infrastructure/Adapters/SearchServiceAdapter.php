<?php

namespace App\Infrastructure\Adapters;

use App\Services\SearchService as LegacySearchService;
use App\Domain\Search\SearchEngine as ModernSearchEngine;
use App\Domain\Search\DTOs\SearchQueryDTO;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Адаптер для миграции с legacy SearchService на модульный SearchEngine
 */
class SearchServiceAdapter
{
    private ?LegacySearchService $legacyService;
    private ModernSearchEngine $modernEngine;
    private bool $useModern;

    public function __construct(
        ?LegacySearchService $legacyService,
        ModernSearchEngine $modernEngine
    ) {
        $this->legacyService = $legacyService;
        $this->modernEngine = $modernEngine;
        $this->useModern = config('features.use_modern_search', false);
    }

    /**
     * Глобальный поиск (старый метод)
     */
    public function search(string $query, array $options = []): array
    {
        if ($this->useModern) {
            try {
                $dto = $this->convertToSearchDTO($query, $options);
                $results = $this->modernEngine->search($dto);
                
                // Конвертируем результаты в старый формат
                return $this->convertToLegacyFormat($results);
            } catch (Throwable $e) {
                Log::error('Modern search failed, falling back to legacy', [
                    'error' => $e->getMessage(),
                    'query' => $query
                ]);
                
                if ($this->legacyService) {
                    return $this->legacyService->search($query, $options);
                }
                
                throw $e;
            }
        }

        if ($this->legacyService) {
            return $this->legacyService->search($query, $options);
        }

        // Если legacy сервис не доступен, используем modern
        $dto = $this->convertToSearchDTO($query, $options);
        $results = $this->modernEngine->search($dto);
        return $this->convertToLegacyFormat($results);
    }

    /**
     * Поиск мастеров (старый метод)
     */
    public function searchMasters(string $query, array $filters = []): array
    {
        if ($this->useModern) {
            try {
                $results = $this->modernEngine->searchByType('masters', $query, $filters);
                return $this->formatMasterResults($results);
            } catch (Throwable $e) {
                Log::error('Modern master search failed', [
                    'error' => $e->getMessage(),
                    'query' => $query
                ]);
                
                if ($this->legacyService) {
                    return $this->legacyService->searchMasters($query, $filters);
                }
                
                throw $e;
            }
        }

        if ($this->legacyService) {
            return $this->legacyService->searchMasters($query, $filters);
        }

        $results = $this->modernEngine->searchByType('masters', $query, $filters);
        return $this->formatMasterResults($results);
    }

    /**
     * Поиск услуг (старый метод)
     */
    public function searchServices(string $query, array $filters = []): array
    {
        if ($this->useModern) {
            try {
                $results = $this->modernEngine->searchByType('services', $query, $filters);
                return $this->formatServiceResults($results);
            } catch (Throwable $e) {
                Log::error('Modern service search failed', [
                    'error' => $e->getMessage(),
                    'query' => $query
                ]);
                
                if ($this->legacyService) {
                    return $this->legacyService->searchServices($query, $filters);
                }
                
                throw $e;
            }
        }

        if ($this->legacyService) {
            return $this->legacyService->searchServices($query, $filters);
        }

        $results = $this->modernEngine->searchByType('services', $query, $filters);
        return $this->formatServiceResults($results);
    }

    /**
     * Получить подсказки (автодополнение)
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        if ($this->useModern) {
            try {
                return $this->modernEngine->suggest($query, $limit);
            } catch (Throwable $e) {
                Log::error('Modern suggestions failed', [
                    'error' => $e->getMessage(),
                    'query' => $query
                ]);
                
                if ($this->legacyService) {
                    return $this->legacyService->getSuggestions($query, $limit);
                }
                
                return [];
            }
        }

        if ($this->legacyService) {
            return $this->legacyService->getSuggestions($query, $limit);
        }

        return $this->modernEngine->suggest($query, $limit);
    }

    /**
     * Получить популярные запросы
     */
    public function getPopularSearches(int $limit = 10): array
    {
        if ($this->useModern) {
            try {
                return $this->modernEngine->getPopularSearches($limit);
            } catch (Throwable $e) {
                Log::error('Modern popular searches failed', [
                    'error' => $e->getMessage()
                ]);
                
                if ($this->legacyService) {
                    return $this->legacyService->getPopularSearches($limit);
                }
                
                return [];
            }
        }

        if ($this->legacyService) {
            return $this->legacyService->getPopularSearches($limit);
        }

        return $this->modernEngine->getPopularSearches($limit);
    }

    /**
     * Индексировать модель
     */
    public function indexModel($model): bool
    {
        if ($this->useModern) {
            try {
                return $this->modernEngine->index($model);
            } catch (Throwable $e) {
                Log::error('Modern indexing failed', [
                    'error' => $e->getMessage(),
                    'model' => get_class($model),
                    'id' => $model->getKey()
                ]);
                
                if ($this->legacyService && method_exists($this->legacyService, 'indexModel')) {
                    return $this->legacyService->indexModel($model);
                }
                
                return false;
            }
        }

        if ($this->legacyService && method_exists($this->legacyService, 'indexModel')) {
            return $this->legacyService->indexModel($model);
        }

        return $this->modernEngine->index($model);
    }

    /**
     * Удалить из индекса
     */
    public function removeFromIndex($model): bool
    {
        if ($this->useModern) {
            try {
                return $this->modernEngine->remove($model);
            } catch (Throwable $e) {
                Log::error('Modern index removal failed', [
                    'error' => $e->getMessage(),
                    'model' => get_class($model),
                    'id' => $model->getKey()
                ]);
                
                if ($this->legacyService && method_exists($this->legacyService, 'removeFromIndex')) {
                    return $this->legacyService->removeFromIndex($model);
                }
                
                return false;
            }
        }

        if ($this->legacyService && method_exists($this->legacyService, 'removeFromIndex')) {
            return $this->legacyService->removeFromIndex($model);
        }

        return $this->modernEngine->remove($model);
    }

    /**
     * Конвертировать параметры в SearchQueryDTO
     */
    private function convertToSearchDTO(string $query, array $options): SearchQueryDTO
    {
        return new SearchQueryDTO(
            query: $query,
            types: $options['types'] ?? ['masters', 'services'],
            filters: $options['filters'] ?? [],
            limit: $options['limit'] ?? 20,
            offset: $options['offset'] ?? 0,
            sort: $options['sort'] ?? 'relevance',
            order: $options['order'] ?? 'desc'
        );
    }

    /**
     * Конвертировать результаты в старый формат
     */
    private function convertToLegacyFormat(array $results): array
    {
        $legacyResults = [
            'masters' => [],
            'services' => [],
            'total' => 0
        ];

        foreach ($results as $type => $items) {
            if ($type === 'masters' || $type === 'services') {
                $legacyResults[$type] = array_map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'url' => $item->url,
                        'score' => $item->score,
                        'highlight' => $item->highlight ?? null
                    ];
                }, $items);
                
                $legacyResults['total'] += count($items);
            }
        }

        return $legacyResults;
    }

    /**
     * Форматировать результаты мастеров
     */
    private function formatMasterResults(array $results): array
    {
        return array_map(function ($result) {
            return [
                'id' => $result->id,
                'name' => $result->title,
                'description' => $result->description,
                'url' => $result->url,
                'rating' => $result->metadata['rating'] ?? 0,
                'reviews_count' => $result->metadata['reviews_count'] ?? 0,
                'city' => $result->metadata['city'] ?? null,
                'services' => $result->metadata['services'] ?? [],
                'photo' => $result->metadata['photo'] ?? null
            ];
        }, $results);
    }

    /**
     * Форматировать результаты услуг
     */
    private function formatServiceResults(array $results): array
    {
        return array_map(function ($result) {
            return [
                'id' => $result->id,
                'name' => $result->title,
                'description' => $result->description,
                'category' => $result->metadata['category'] ?? null,
                'price_from' => $result->metadata['price_from'] ?? null,
                'duration' => $result->metadata['duration'] ?? null,
                'masters_count' => $result->metadata['masters_count'] ?? 0
            ];
        }, $results);
    }

    /**
     * Проксирование неопределенных методов
     */
    public function __call($method, $arguments)
    {
        Log::warning("SearchServiceAdapter: Undefined method called: {$method}");
        
        if ($this->legacyService && method_exists($this->legacyService, $method)) {
            return $this->legacyService->$method(...$arguments);
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist");
    }
}