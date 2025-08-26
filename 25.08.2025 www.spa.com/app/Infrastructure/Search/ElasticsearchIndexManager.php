<?php

namespace App\Infrastructure\Search;

use Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

/**
 * Менеджер индексов Elasticsearch
 */
class ElasticsearchIndexManager
{
    protected Client $client;
    protected string $indexPrefix;
    protected array $defaultSettings;

    public function __construct(Client $client, string $indexPrefix, array $defaultSettings)
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
        $this->defaultSettings = $defaultSettings;
    }

    /**
     * Создать индекс с настройками
     */
    public function createIndex(string $indexName, array $settings = [], array $mappings = []): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            // Проверяем существование индекса
            if ($this->indexExists($indexName)) {
                Log::info("Index {$fullIndexName} already exists");
                return ['acknowledged' => true, 'index' => $fullIndexName];
            }

            $params = [
                'index' => $fullIndexName,
                'body' => [
                    'settings' => array_merge($this->defaultSettings, $settings),
                    'mappings' => $mappings
                ]
            ];

            $response = $this->client->indices()->create($params);
            
            Log::info("Created index: {$fullIndexName}");
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to create index {$fullIndexName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Удалить индекс
     */
    public function deleteIndex(string $indexName): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            if (!$this->indexExists($indexName)) {
                return ['acknowledged' => true];
            }

            $response = $this->client->indices()->delete(['index' => $fullIndexName]);
            
            Log::info("Deleted index: {$fullIndexName}");
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to delete index {$fullIndexName}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Проверить существование индекса
     */
    public function indexExists(string $indexName): bool
    {
        $fullIndexName = $this->getIndexName($indexName);
        
        return $this->client->indices()->exists(['index' => $fullIndexName]);
    }

    /**
     * Обновить mapping индекса
     */
    public function putMapping(string $indexName, array $mappings): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'body' => $mappings
            ];

            $response = $this->client->indices()->putMapping($params);
            
            Log::info("Updated mapping for index: {$fullIndexName}");
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to update mapping", [
                'index' => $fullIndexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обновить настройки индекса
     */
    public function putSettings(string $indexName, array $settings): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'body' => [
                    'settings' => $settings
                ]
            ];

            $response = $this->client->indices()->putSettings($params);
            
            Log::info("Updated settings for index: {$fullIndexName}");
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to update settings", [
                'index' => $fullIndexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получить статистику индекса
     */
    public function getIndexStats(string $indexName): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            return $this->client->indices()->stats(['index' => $fullIndexName]);
        } catch (\Exception $e) {
            Log::error("Failed to get index stats", [
                'index' => $fullIndexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Очистить кэш индекса
     */
    public function clearCache(string $indexName): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            return $this->client->indices()->clearCache(['index' => $fullIndexName]);
        } catch (\Exception $e) {
            Log::error("Failed to clear cache", [
                'index' => $fullIndexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обновить индекс
     */
    public function refresh(string $indexName): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            return $this->client->indices()->refresh(['index' => $fullIndexName]);
        } catch (\Exception $e) {
            Log::error("Failed to refresh index", [
                'index' => $fullIndexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получить полное имя индекса с префиксом
     */
    public function getIndexName(string $name): string
    {
        return $this->indexPrefix . $name;
    }

    /**
     * Анализировать текст
     */
    public function analyze(string $indexName, string $text, string $analyzer): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'body' => [
                    'analyzer' => $analyzer,
                    'text' => $text
                ]
            ];

            return $this->client->indices()->analyze($params);
            
        } catch (\Exception $e) {
            Log::error("Failed to analyze text", [
                'index' => $fullIndexName,
                'analyzer' => $analyzer,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}