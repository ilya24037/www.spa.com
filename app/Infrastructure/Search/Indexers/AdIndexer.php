<?php

namespace App\Infrastructure\Search\Indexers;

use App\Domain\Ad\Models\Ad;
use App\Infrastructure\Search\ElasticsearchClient;
use App\Infrastructure\Search\Indexers\AdDocumentTransformer;
use App\Infrastructure\Search\Indexers\AdIndexMappings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Упрощенный индексатор объявлений - делегирует трансформацию и маппинги
 */
class AdIndexer
{
    protected ElasticsearchClient $client;
    protected AdDocumentTransformer $transformer;
    protected AdIndexMappings $mappings;
    protected string $indexName = 'ads';
    
    public function __construct(
        ElasticsearchClient $client,
        AdDocumentTransformer $transformer,
        AdIndexMappings $mappings
    ) {
        $this->client = $client;
        $this->transformer = $transformer;
        $this->mappings = $mappings;
    }

    /**
     * Создать индекс для объявлений
     */
    public function createIndex(): void
    {
        $settings = $this->mappings->getSettings();
        $mappings = $this->mappings->getMappings();

        $this->client->createIndex($this->indexName, $settings, $mappings);
        
        Log::info("Created Elasticsearch index for ads");
    }

    /**
     * Индексировать одно объявление
     */
    public function index(Ad $ad): void
    {
        $document = $this->transformer->transformToDocument($ad);
        
        $this->client->index($this->indexName, $ad->id, $document);
        
        Log::debug("Indexed ad", ['id' => $ad->id]);
    }

    /**
     * Массовая индексация объявлений
     */
    public function bulkIndex(Collection $ads): void
    {
        if ($ads->isEmpty()) {
            return;
        }

        $documents = [];
        
        foreach ($ads as $ad) {
            $documents[$ad->id] = $this->transformer->transformToDocument($ad);
        }

        $response = $this->client->bulkIndex($this->indexName, $documents);
        
        if ($response['errors']) {
            Log::warning("Some ads failed to index", [
                'total' => $ads->count(),
                'errors' => $this->extractBulkErrors($response)
            ]);
        } else {
            Log::info("Bulk indexed ads", ['count' => $ads->count()]);
        }
    }

    /**
     * Обновить объявление в индексе
     */
    public function update(Ad $ad, array $fields = []): void
    {
        if (empty($fields)) {
            // Полное обновление документа
            $this->index($ad);
            return;
        }

        // Частичное обновление
        $document = [];
        $fullDocument = $this->transformer->transformToDocument($ad);
        
        foreach ($fields as $field) {
            if (isset($fullDocument[$field])) {
                $document[$field] = $fullDocument[$field];
            }
        }

        $this->client->update($this->indexName, $ad->id, $document);
        
        Log::debug("Updated ad", ['id' => $ad->id, 'fields' => $fields]);
    }

    /**
     * Удалить объявление из индекса
     */
    public function delete(int $adId): void
    {
        $this->client->delete($this->indexName, (string)$adId);
        
        Log::debug("Deleted ad from index", ['id' => $adId]);
    }

    /**
     * Переиндексировать все объявления
     */
    public function reindexAll(int $batchSize = 1000): void
    {
        Log::info("Starting full reindexing of ads");
        
        $processedCount = 0;
        
        Ad::query()
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->chunk($batchSize, function ($ads) use (&$processedCount) {
                $this->bulkIndex($ads);
                $processedCount += $ads->count();
                
                Log::info("Reindexing progress", ['processed' => $processedCount]);
            });
            
        // Обновляем индекс для применения изменений
        $this->client->refresh($this->indexName);
        
        Log::info("Completed full reindexing", ['total' => $processedCount]);
    }

    /**
     * Переиндексировать объявления по условию
     */
    public function reindexWhere(array $conditions, int $batchSize = 1000): void
    {
        $query = Ad::query();
        
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        
        $processedCount = 0;
        
        $query->with(['user', 'media'])
            ->chunk($batchSize, function ($ads) use (&$processedCount) {
                $this->bulkIndex($ads);
                $processedCount += $ads->count();
            });
            
        Log::info("Reindexed ads by condition", [
            'conditions' => $conditions,
            'total' => $processedCount
        ]);
    }

    /**
     * Синхронизировать с базой данных
     */
    public function syncWithDatabase(int $lastMinutes = 60): void
    {
        $since = now()->subMinutes($lastMinutes);
        
        // Обновленные объявления
        $updated = Ad::query()
            ->where('updated_at', '>=', $since)
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->get();
            
        if ($updated->isNotEmpty()) {
            $this->bulkIndex($updated);
            Log::info("Synced updated ads", ['count' => $updated->count()]);
        }
        
        // Удаленные или деактивированные объявления
        $deleted = Ad::query()
            ->where('updated_at', '>=', $since)
            ->where(function ($query) {
                $query->where('status', '!=', 'active')
                    ->orWhere('is_published', false);
            })
            ->pluck('id');
            
        foreach ($deleted as $id) {
            try {
                $this->delete($id);
            } catch (\Exception $e) {
                // Игнорируем ошибки удаления несуществующих документов
            }
        }
        
        if ($deleted->isNotEmpty()) {
            Log::info("Removed inactive ads from index", ['count' => $deleted->count()]);
        }
    }

    /**
     * Обновить boost score для всех объявлений
     */
    public function updateBoostScores(): void
    {
        Log::info("Updating boost scores for all ads");
        
        Ad::query()
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->chunk(1000, function ($ads) {
                foreach ($ads as $ad) {
                    $document = $this->transformer->transformToDocument($ad);
                    $boostScore = $document['boost_score'];
                    
                    $this->client->update($this->indexName, $ad->id, [
                        'boost_score' => $boostScore
                    ]);
                }
            });
            
        Log::info("Completed updating boost scores");
    }

    /**
     * Извлечь ошибки из bulk ответа
     */
    protected function extractBulkErrors(array $response): array
    {
        $errors = [];
        
        foreach ($response['items'] as $item) {
            if (isset($item['index']['error'])) {
                $errors[] = [
                    'id' => $item['index']['_id'],
                    'error' => $item['index']['error']['reason'] ?? 'Unknown error'
                ];
            }
        }
        
        return $errors;
    }
}