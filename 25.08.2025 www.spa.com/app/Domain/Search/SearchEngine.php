<?php

namespace App\Domain\Search;

use App\Domain\Search\Services\SearchService;
use App\Domain\Search\Services\SearchEngineInterface;
use App\Domain\Search\Services\DatabaseSearchEngine;
use App\Domain\Search\Services\ElasticsearchEngine;

/**
 * Обёртка для обратной совместимости со старыми тестами
 * @deprecated Используйте SearchService напрямую
 */
class SearchEngine
{
    private SearchService $searchService;
    private ?SearchEngineInterface $elasticEngine;
    private SearchEngineInterface $databaseEngine;

    public function __construct(
        ?SearchEngineInterface $elasticEngine = null,
        ?SearchEngineInterface $databaseEngine = null
    ) {
        $this->elasticEngine = $elasticEngine;
        $this->databaseEngine = $databaseEngine ?? app(DatabaseSearchEngine::class);
        $this->searchService = app(SearchService::class);
    }

    public function search($query, array $filters = [], array $options = [])
    {
        // Делегируем в SearchService
        return $this->searchService->search(
            $query instanceof \App\Domain\Search\DTOs\SearchQueryDTO ? $query->query : $query,
            $filters,
            $options['sortBy'] ?? \App\Domain\Search\Enums\SortBy::RELEVANCE,
            $options['page'] ?? 1,
            $options['perPage'] ?? 20,
            $options['location'] ?? null
        );
    }

    public function searchMasters($query, array $filters = [], array $options = [])
    {
        return $this->databaseEngine->searchMasters(
            $query,
            $filters,
            $options['sortBy'] ?? \App\Domain\Search\Enums\SortBy::RELEVANCE,
            $options['page'] ?? 1,
            $options['perPage'] ?? 20,
            $options['location'] ?? null
        );
    }

    public function searchServices($query, array $filters = [], array $options = [])
    {
        return $this->databaseEngine->searchServices(
            $query,
            $filters,
            $options['sortBy'] ?? \App\Domain\Search\Enums\SortBy::RELEVANCE,
            $options['page'] ?? 1,
            $options['perPage'] ?? 20
        );
    }

    public function searchAds($query, array $filters = [], array $options = [])
    {
        return $this->databaseEngine->searchAds(
            $query,
            $filters,
            $options['sortBy'] ?? \App\Domain\Search\Enums\SortBy::RELEVANCE,
            $options['page'] ?? 1,
            $options['perPage'] ?? 20,
            $options['location'] ?? null
        );
    }

    public function reindexAll(): void
    {
        // Метод-заглушка для тестов
        if ($this->elasticEngine && method_exists($this->elasticEngine, 'reindexAll')) {
            $this->elasticEngine->reindexAll();
        }
    }

    public function reindexModel(string $modelClass, $modelId = null): void
    {
        // Метод-заглушка для тестов
        if ($this->elasticEngine && method_exists($this->elasticEngine, 'reindexModel')) {
            $this->elasticEngine->reindexModel($modelClass, $modelId);
        }
    }

    public function deleteFromIndex(string $modelClass, $modelId): void
    {
        // Метод-заглушка для тестов
        if ($this->elasticEngine && method_exists($this->elasticEngine, 'deleteFromIndex')) {
            $this->elasticEngine->deleteFromIndex($modelClass, $modelId);
        }
    }

    public function validateQuery($query): bool
    {
        // Базовая валидация
        if (empty($query)) {
            return false;
        }
        
        if (is_object($query) && method_exists($query, 'validate')) {
            return $query->validate();
        }
        
        return is_string($query) && strlen(trim($query)) > 0;
    }

    public function setEngine(string $engine): self
    {
        // Метод-заглушка для тестов
        return $this;
    }

    public function getEngine(): ?SearchEngineInterface
    {
        return $this->elasticEngine ?? $this->databaseEngine;
    }
}