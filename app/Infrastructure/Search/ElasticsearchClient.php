<?php

namespace App\Infrastructure\Search;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Клиент для работы с Elasticsearch
 * 
 * Централизованная точка взаимодействия с Elasticsearch,
 * обеспечивающая абстракцию от конкретной реализации поискового движка
 */
class ElasticsearchClient
{
    protected Client $client;
    protected array $defaultSettings;
    protected string $indexPrefix;

    public function __construct()
    {
        // ВРЕМЕННО ОТКЛЮЧЕНО - Elasticsearch не установлен
        // $this->client = ClientBuilder::create()
        //     ->setHosts($this->getHosts())
        //     ->setRetries(Config::get('elasticsearch.retries', 2))
        //     ->setConnectionPool(Config::get('elasticsearch.connection_pool', '\Elasticsearch\ConnectionPool\StaticNoPingConnectionPool'))
        //     ->build();

        $this->indexPrefix = Config::get('elasticsearch.index_prefix', 'spa_');
        $this->defaultSettings = $this->getDefaultIndexSettings();
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
     * Обновить псевдонимы индекса
     */
    public function updateAliases(array $actions): array
    {
        try {
            $params = [
                'body' => [
                    'actions' => $actions
                ]
            ];

            $response = $this->client->indices()->updateAliases($params);
            
            Log::info("Updated aliases", ['actions' => count($actions)]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to update aliases", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Создать снимок индекса
     */
    public function createSnapshot(string $repository, string $snapshot, array $indices = []): array
    {
        try {
            $params = [
                'repository' => $repository,
                'snapshot' => $snapshot,
                'body' => [
                    'indices' => implode(',', array_map([$this, 'getIndexName'], $indices))
                ]
            ];

            $response = $this->client->snapshot()->create($params);
            
            Log::info("Created snapshot", [
                'repository' => $repository,
                'snapshot' => $snapshot
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error("Failed to create snapshot", [
                'repository' => $repository,
                'snapshot' => $snapshot,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
    protected function getIndexName(string $name): string
    {
        return $this->indexPrefix . $name;
    }

    /**
     * Получить хосты из конфигурации
     */
    protected function getHosts(): array
    {
        $hosts = Config::get('elasticsearch.hosts', ['localhost:9200']);
        
        return is_array($hosts) ? $hosts : [$hosts];
    }

    /**
     * Получить настройки индекса по умолчанию
     */
    protected function getDefaultIndexSettings(): array
    {
        return [
            'number_of_shards' => Config::get('elasticsearch.number_of_shards', 1),
            'number_of_replicas' => Config::get('elasticsearch.number_of_replicas', 0),
            'analysis' => [
                'analyzer' => [
                    'russian_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'russian_stop',
                            'russian_stemmer',
                            'word_delimiter'
                        ]
                    ],
                    'autocomplete_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'edge_ngram_tokenizer',
                        'filter' => [
                            'lowercase'
                        ]
                    ],
                    'autocomplete_search' => [
                        'type' => 'custom',
                        'tokenizer' => 'lowercase'
                    ]
                ],
                'tokenizer' => [
                    'edge_ngram_tokenizer' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 10,
                        'token_chars' => ['letter', 'digit']
                    ]
                ],
                'filter' => [
                    'russian_stop' => [
                        'type' => 'stop',
                        'stopwords' => '_russian_'
                    ],
                    'russian_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'russian'
                    ],
                    'word_delimiter' => [
                        'type' => 'word_delimiter',
                        'preserve_original' => true
                    ]
                ]
            ]
        ];
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

    /**
     * Проверить здоровье кластера
     */
    public function getClusterHealth(): array
    {
        try {
            return $this->client->cluster()->health();
        } catch (\Exception $e) {
            Log::error("Failed to get cluster health", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}