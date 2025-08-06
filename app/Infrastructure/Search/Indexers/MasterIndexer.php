<?php

namespace App\Infrastructure\Search\Indexers;

use App\Domain\Master\Models\MasterProfile;
use App\Infrastructure\Search\ElasticsearchClient;
use App\Infrastructure\Search\Transformers\MasterDocumentTransformer;
use App\Infrastructure\Search\Calculators\MasterScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Индексатор мастеров для Elasticsearch
 * Упрощенная версия с вынесенной логикой в отдельные классы
 */
class MasterIndexer
{
    protected ElasticsearchClient $client;
    protected MasterDocumentTransformer $transformer;
    protected MasterScoreCalculator $scoreCalculator;
    protected string $indexName = 'masters';

    public function __construct(
        ElasticsearchClient $client,
        MasterDocumentTransformer $transformer,
        MasterScoreCalculator $scoreCalculator
    ) {
        $this->client = $client;
        $this->transformer = $transformer;
        $this->scoreCalculator = $scoreCalculator;
    }

    /**
     * Создать индекс для мастеров
     */
    public function createIndex(): void
    {
        $settings = $this->getIndexSettings();
        $mappings = $this->getIndexMappings();

        $this->client->createIndex($this->indexName, [
            'settings' => $settings,
            'mappings' => $mappings
        ]);

        Log::info('Master index created', ['index' => $this->indexName]);
    }

    /**
     * Индексировать одного мастера
     */
    public function index(MasterProfile $master): void
    {
        $document = $this->transformToDocument($master);
        
        $this->client->index($this->indexName, $master->id, $document);
        
        Log::debug('Master indexed', ['master_id' => $master->id]);
    }

    /**
     * Массовая индексация мастеров
     */
    public function bulkIndex(Collection $masters): void
    {
        $documents = [];
        
        foreach ($masters as $master) {
            $documents[] = [
                'index' => [
                    '_index' => $this->indexName,
                    '_id' => $master->id
                ]
            ];
            $documents[] = $this->transformToDocument($master);
        }
        
        if (!empty($documents)) {
            $this->client->bulk(['body' => $documents]);
            Log::info('Masters bulk indexed', ['count' => $masters->count()]);
        }
    }

    /**
     * Обновить мастера в индексе
     */
    public function update(MasterProfile $master, array $fields = []): void
    {
        if (empty($fields)) {
            // Полное обновление
            $this->index($master);
        } else {
            // Частичное обновление
            $document = array_intersect_key(
                $this->transformToDocument($master),
                array_flip($fields)
            );
            
            $this->client->update($this->indexName, $master->id, ['doc' => $document]);
        }
        
        Log::debug('Master updated in index', ['master_id' => $master->id]);
    }

    /**
     * Удалить мастера из индекса
     */
    public function delete(int $masterId): void
    {
        $this->client->delete($this->indexName, $masterId);
        Log::debug('Master deleted from index', ['master_id' => $masterId]);
    }

    /**
     * Переиндексировать всех мастеров
     */
    public function reindexAll(int $batchSize = 1000): void
    {
        Log::info('Starting master reindexing', ['batch_size' => $batchSize]);
        
        $processedCount = 0;
        
        MasterProfile::with(['user', 'services', 'media'])
            ->chunk($batchSize, function ($masters) use (&$processedCount) {
                $this->bulkIndex($masters);
                $processedCount += $masters->count();
                
                Log::info('Masters reindexed batch', [
                    'processed' => $processedCount,
                    'batch_size' => $masters->count()
                ]);
            });
        
        Log::info('Master reindexing completed', ['total_processed' => $processedCount]);
    }

    /**
     * Синхронизировать с базой данных
     */
    public function syncWithDatabase(int $lastMinutes = 60): void
    {
        $updatedMasters = MasterProfile::with(['user', 'services', 'media'])
            ->where('updated_at', '>=', now()->subMinutes($lastMinutes))
            ->get();
        
        foreach ($updatedMasters as $master) {
            $this->index($master);
        }
        
        Log::info('Masters synced with database', [
            'count' => $updatedMasters->count(),
            'last_minutes' => $lastMinutes
        ]);
    }

    /**
     * Преобразовать мастера в документ для индексации
     */
    protected function transformToDocument(MasterProfile $master): array
    {
        $document = $this->transformer->transform($master);
        
        // Добавить скоринги
        $document['profile_completeness'] = $this->scoreCalculator->calculateProfileCompleteness($master);
        $document['activity_score'] = $this->scoreCalculator->calculateActivityScore($master);
        $document['quality_score'] = $this->scoreCalculator->calculateQualityScore($master);
        $document['boost_score'] = $this->scoreCalculator->calculateBoostScore($master);
        $document['overall_score'] = $this->scoreCalculator->calculateOverallScore($master);
        
        return $document;
    }

    /**
     * Получить настройки индекса
     */
    private function getIndexSettings(): array
    {
        return [
            'number_of_shards' => 2,
            'number_of_replicas' => 1,
            'max_result_window' => 10000,
            'analysis' => [
                'analyzer' => [
                    'master_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'russian_stop',
                            'russian_stemmer',
                            'word_delimiter',
                            'synonym_filter'
                        ]
                    ],
                    'name_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'word_delimiter']
                    ]
                ],
                'filter' => [
                    'synonym_filter' => [
                        'type' => 'synonym',
                        'synonyms' => [
                            'массажист,массажистка => массажист',
                            'спа мастер,spa мастер => спа-мастер',
                            'косметолог,эстетист => косметолог',
                            'специалист,мастер,профессионал => специалист'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Получить маппинги индекса
     */
    private function getIndexMappings(): array
    {
        return [
            'properties' => [
                'id' => ['type' => 'integer'],
                'user_id' => ['type' => 'integer'],
                'name' => [
                    'type' => 'text',
                    'analyzer' => 'name_analyzer',
                    'fields' => [
                        'keyword' => ['type' => 'keyword']
                    ]
                ],
                'slug' => ['type' => 'keyword'],
                'about' => ['type' => 'text', 'analyzer' => 'master_analyzer'],
                'specialty' => ['type' => 'text', 'analyzer' => 'master_analyzer'],
                'specializations' => ['type' => 'keyword'],
                'city' => ['type' => 'keyword'],
                'district' => ['type' => 'keyword'],
                'location' => ['type' => 'geo_point'],
                'rating' => ['type' => 'float'],
                'reviews_count' => ['type' => 'integer'],
                'price_min' => ['type' => 'integer'],
                'price_max' => ['type' => 'integer'],
                'is_active' => ['type' => 'boolean'],
                'is_verified' => ['type' => 'boolean'],
                'is_premium' => ['type' => 'boolean'],
                'is_online' => ['type' => 'boolean'],
                'profile_completeness' => ['type' => 'integer'],
                'activity_score' => ['type' => 'integer'],
                'quality_score' => ['type' => 'integer'],
                'boost_score' => ['type' => 'integer'],
                'overall_score' => ['type' => 'integer'],
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date'],
            ]
        ];
    }
}