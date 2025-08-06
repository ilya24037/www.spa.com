<?php

namespace App\Domain\Search\Services\Handlers;

use App\Domain\Search\Enums\SearchType;
use App\Domain\Search\Engines\AdSearchEngine;
use App\Domain\Search\Engines\MasterSearchEngine;
use App\Domain\Search\Services\ServiceSearchEngine;
use App\Domain\Search\Services\GlobalSearchEngine;
use App\Domain\Search\Services\RecommendationEngine;
use App\Domain\Search\Services\SearchEngineInterface;
use App\Domain\Search\Services\ElasticsearchSearchEngine;
use App\Infrastructure\Search\ElasticsearchClient;

/**
 * Менеджер движков поиска
 */
class SearchEngineManager
{
    protected array $engines = [];

    public function __construct(
        protected AdSearchEngine $adEngine,
        protected MasterSearchEngine $masterEngine,
        protected ServiceSearchEngine $serviceEngine,
        protected GlobalSearchEngine $globalEngine,
        protected RecommendationEngine $recommendationEngine,
        protected ?ElasticsearchClient $elasticsearchClient = null
    ) {
        $this->initializeEngines();
    }

    /**
     * Инициализация движков поиска
     */
    protected function initializeEngines(): void
    {
        // Проверяем, включен ли Elasticsearch
        $useElasticsearch = config('elasticsearch.enabled', false) && $this->elasticsearchClient !== null;
        
        if ($useElasticsearch) {
            // Используем Elasticsearch для основных типов поиска
            $this->engines = [
                SearchType::ADS->value => new ElasticsearchSearchEngine($this->elasticsearchClient, SearchType::ADS),
                SearchType::MASTERS->value => new ElasticsearchSearchEngine($this->elasticsearchClient, SearchType::MASTERS),
                SearchType::SERVICES->value => $this->serviceEngine, // Пока используем SQL
                SearchType::GLOBAL->value => $this->globalEngine,
                SearchType::RECOMMENDATIONS->value => $this->recommendationEngine,
            ];
        } else {
            // Fallback на SQL движки
            $this->engines = [
                SearchType::ADS->value => $this->adEngine,
                SearchType::MASTERS->value => $this->masterEngine,
                SearchType::SERVICES->value => $this->serviceEngine,
                SearchType::GLOBAL->value => $this->globalEngine,
                SearchType::RECOMMENDATIONS->value => $this->recommendationEngine,
            ];
        }
    }

    /**
     * Получить движок для типа поиска
     */
    public function getEngine(SearchType $type): SearchEngineInterface
    {
        if (!isset($this->engines[$type->value])) {
            throw new \InvalidArgumentException("Движок для типа поиска {$type->value} не найден");
        }
        
        return $this->engines[$type->value];
    }

    /**
     * Получить все доступные движки
     */
    public function getAllEngines(): array
    {
        return $this->engines;
    }

    /**
     * Проверить доступность движка
     */
    public function hasEngine(SearchType $type): bool
    {
        return isset($this->engines[$type->value]);
    }

    /**
     * Добавить пользовательский движок
     */
    public function addEngine(SearchType $type, SearchEngineInterface $engine): void
    {
        $this->engines[$type->value] = $engine;
    }

    /**
     * Удалить движок
     */
    public function removeEngine(SearchType $type): void
    {
        unset($this->engines[$type->value]);
    }

    /**
     * Получить информацию о движках
     */
    public function getEngineInfo(): array
    {
        $info = [];
        
        foreach ($this->engines as $typeValue => $engine) {
            $info[$typeValue] = [
                'class' => get_class($engine),
                'supports_facets' => method_exists($engine, 'facetedSearch'),
                'supports_geo' => method_exists($engine, 'geoSearch'),
                'supports_intelligent' => method_exists($engine, 'intelligentSearch'),
                'supports_similar' => method_exists($engine, 'findSimilar'),
                'supports_export' => method_exists($engine, 'exportResults'),
            ];
        }
        
        return $info;
    }

    /**
     * Переключить на Elasticsearch
     */
    public function enableElasticsearch(): void
    {
        if ($this->elasticsearchClient) {
            $this->engines[SearchType::ADS->value] = new ElasticsearchSearchEngine($this->elasticsearchClient, SearchType::ADS);
            $this->engines[SearchType::MASTERS->value] = new ElasticsearchSearchEngine($this->elasticsearchClient, SearchType::MASTERS);
        }
    }

    /**
     * Переключить на SQL движки
     */
    public function enableSqlEngines(): void
    {
        $this->engines[SearchType::ADS->value] = $this->adEngine;
        $this->engines[SearchType::MASTERS->value] = $this->masterEngine;
    }

    /**
     * Проверить работоспособность движков
     */
    public function healthCheck(): array
    {
        $results = [];
        
        foreach ($this->engines as $typeValue => $engine) {
            try {
                // Попробуем выполнить простой поиск
                if (method_exists($engine, 'quickSearch')) {
                    $engine->quickSearch('test', 1);
                }
                $results[$typeValue] = 'ok';
            } catch (\Exception $e) {
                $results[$typeValue] = 'error: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
}