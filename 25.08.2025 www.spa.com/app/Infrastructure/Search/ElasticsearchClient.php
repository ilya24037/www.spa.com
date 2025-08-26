<?php

namespace App\Infrastructure\Search;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use App\Infrastructure\Search\ElasticsearchIndexManager;
use App\Infrastructure\Search\ElasticsearchDocumentManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный клиент Elasticsearch - делегирует работу специализированным менеджерам
 */
class ElasticsearchClient
{
    protected Client $client;
    protected ElasticsearchIndexManager $indexManager;
    protected ElasticsearchDocumentManager $documentManager;
    protected string $indexPrefix;
    protected array $defaultSettings;

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
        
        // Создаём специализированные менеджеры
        // $this->indexManager = new ElasticsearchIndexManager($this->client, $this->indexPrefix, $this->defaultSettings);
        // $this->documentManager = new ElasticsearchDocumentManager($this->client, $this->indexPrefix);
    }

    // === ИНДЕКСЫ ===

    /**
     * Создать индекс с настройками
     */
    public function createIndex(string $indexName, array $settings = [], array $mappings = []): array
    {
        return $this->indexManager->createIndex($indexName, $settings, $mappings);
    }

    /**
     * Удалить индекс
     */
    public function deleteIndex(string $indexName): array
    {
        return $this->indexManager->deleteIndex($indexName);
    }

    /**
     * Проверить существование индекса
     */
    public function indexExists(string $indexName): bool
    {
        return $this->indexManager->indexExists($indexName);
    }

    /**
     * Обновить mapping индекса
     */
    public function putMapping(string $indexName, array $mappings): array
    {
        return $this->indexManager->putMapping($indexName, $mappings);
    }

    /**
     * Обновить настройки индекса
     */
    public function putSettings(string $indexName, array $settings): array
    {
        return $this->indexManager->putSettings($indexName, $settings);
    }

    /**
     * Получить статистику индекса
     */
    public function getIndexStats(string $indexName): array
    {
        return $this->indexManager->getIndexStats($indexName);
    }

    /**
     * Очистить кэш индекса
     */
    public function clearCache(string $indexName): array
    {
        return $this->indexManager->clearCache($indexName);
    }

    /**
     * Обновить индекс
     */
    public function refresh(string $indexName): array
    {
        return $this->indexManager->refresh($indexName);
    }

    /**
     * Анализировать текст
     */
    public function analyze(string $indexName, string $text, string $analyzer): array
    {
        return $this->indexManager->analyze($indexName, $text, $analyzer);
    }

    // === ДОКУМЕНТЫ ===

    /**
     * Индексировать документ
     */
    public function index(string $indexName, string $id, array $document): array
    {
        return $this->documentManager->index($indexName, $id, $document);
    }

    /**
     * Массовая индексация документов
     */
    public function bulkIndex(string $indexName, array $documents): array
    {
        return $this->documentManager->bulkIndex($indexName, $documents);
    }

    /**
     * Удалить документ
     */
    public function delete(string $indexName, string $id): array
    {
        return $this->documentManager->delete($indexName, $id);
    }

    /**
     * Поиск документов
     */
    public function search(string $indexName, array $query): array
    {
        return $this->documentManager->search($indexName, $query);
    }

    /**
     * Обновить документ
     */
    public function update(string $indexName, string $id, array $document): array
    {
        return $this->documentManager->update($indexName, $id, $document);
    }

    // === КЛАСТЕР И ДОПОЛНИТЕЛЬНЫЕ ОПЕРАЦИИ ===

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
                    'indices' => implode(',', array_map(function($index) {
                        return $this->indexPrefix . $index;
                    }, $indices))
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

    // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===

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
}