<?php

namespace App\Infrastructure\Search;

use Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

/**
 * Менеджер документов Elasticsearch
 */
class ElasticsearchDocumentManager
{
    protected Client $client;
    protected string $indexPrefix;

    public function __construct(Client $client, string $indexPrefix)
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
    }

    /**
     * Индексировать документ
     */
    public function index(string $indexName, string $id, array $document): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'id' => $id,
                'body' => $document
            ];

            $response = $this->client->index($params);
            
            Log::debug("Indexed document", [
                'index' => $fullIndexName,
                'id' => $id
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to index document", [
                'index' => $fullIndexName,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Массовая индексация документов
     */
    public function bulkIndex(string $indexName, array $documents): array
    {
        $fullIndexName = $this->getIndexName($indexName);
        
        if (empty($documents)) {
            return ['errors' => false, 'items' => []];
        }

        try {
            $params = ['body' => []];

            foreach ($documents as $id => $document) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $fullIndexName,
                        '_id' => $id
                    ]
                ];
                $params['body'][] = $document;
            }

            $response = $this->client->bulk($params);
            
            if ($response['errors']) {
                Log::warning("Bulk indexing completed with errors", [
                    'index' => $fullIndexName,
                    'error_count' => $this->countBulkErrors($response)
                ]);
            } else {
                Log::info("Bulk indexed documents", [
                    'index' => $fullIndexName,
                    'count' => count($documents)
                ]);
            }
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to bulk index documents", [
                'index' => $fullIndexName,
                'count' => count($documents),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Удалить документ
     */
    public function delete(string $indexName, string $id): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'id' => $id
            ];

            $response = $this->client->delete($params);
            
            Log::debug("Deleted document", [
                'index' => $fullIndexName,
                'id' => $id
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            if ($e->getCode() === 404) {
                Log::debug("Document not found for deletion", [
                    'index' => $fullIndexName,
                    'id' => $id
                ]);
                return ['result' => 'not_found'];
            }
            
            Log::error("Failed to delete document", [
                'index' => $fullIndexName,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Поиск документов
     */
    public function search(string $indexName, array $query): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'body' => $query
            ];

            $response = $this->client->search($params);
            
            Log::debug("Search executed", [
                'index' => $fullIndexName,
                'hits' => $response['hits']['total']['value'] ?? 0
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Search failed", [
                'index' => $fullIndexName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Обновить документ
     */
    public function update(string $indexName, string $id, array $document): array
    {
        $fullIndexName = $this->getIndexName($indexName);

        try {
            $params = [
                'index' => $fullIndexName,
                'id' => $id,
                'body' => [
                    'doc' => $document
                ]
            ];

            $response = $this->client->update($params);
            
            Log::debug("Updated document", [
                'index' => $fullIndexName,
                'id' => $id
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to update document", [
                'index' => $fullIndexName,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Получить полное имя индекса с префиксом
     */
    protected function getIndexName(string $name): string
    {
        return $this->indexPrefix . $name;
    }

    /**
     * Подсчитать ошибки в bulk операции
     */
    protected function countBulkErrors(array $response): int
    {
        $errorCount = 0;
        
        foreach ($response['items'] as $item) {
            $operation = array_key_first($item);
            if (isset($item[$operation]['error'])) {
                $errorCount++;
            }
        }
        
        return $errorCount;
    }
}