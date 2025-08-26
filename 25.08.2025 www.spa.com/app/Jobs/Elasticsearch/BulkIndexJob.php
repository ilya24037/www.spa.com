<?php

namespace App\Jobs\Elasticsearch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Infrastructure\Search\Indexers\AdIndexer;
use App\Infrastructure\Search\Indexers\MasterIndexer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BulkIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The model type to index.
     *
     * @var string
     */
    protected string $modelType;

    /**
     * The model IDs to index.
     *
     * @var array
     */
    protected array $modelIds;

    /**
     * Create a new job instance.
     */
    public function __construct(string $modelType, array $modelIds)
    {
        $this->modelType = $modelType;
        $this->modelIds = $modelIds;
        $this->queue = config('elasticsearch.queue.queue', 'elasticsearch');
    }

    /**
     * Execute the job.
     */
    public function handle(AdIndexer $adIndexer, MasterIndexer $masterIndexer): void
    {
        try {
            switch ($this->modelType) {
                case 'ad':
                    $this->indexAds($adIndexer);
                    break;
                    
                case 'master':
                    $this->indexMasters($masterIndexer);
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Unknown model type: {$this->modelType}");
            }
            
            Log::info('Bulk indexing completed', [
                'model_type' => $this->modelType,
                'count' => count($this->modelIds)
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk indexing failed', [
                'model_type' => $this->modelType,
                'count' => count($this->modelIds),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Index ads in bulk
     */
    protected function indexAds(AdIndexer $indexer): void
    {
        $ads = \App\Domain\Ad\Models\Ad::whereIn('id', $this->modelIds)
            ->where('status', 'active')
            ->where('is_published', true)
            ->with(['user', 'media'])
            ->get();
            
        if ($ads->isNotEmpty()) {
            $indexer->bulkIndex($ads);
        }
    }

    /**
     * Index masters in bulk
     */
    protected function indexMasters(MasterIndexer $indexer): void
    {
        $masters = \App\Domain\Master\Models\MasterProfile::whereIn('id', $this->modelIds)
            ->where('is_active', true)
            ->with(['user', 'services', 'schedule', 'media'])
            ->get();
            
        if ($masters->isNotEmpty()) {
            $indexer->bulkIndex($masters);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk indexing job failed permanently', [
            'model_type' => $this->modelType,
            'count' => count($this->modelIds),
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['elasticsearch', 'bulk-indexing', $this->modelType];
    }
}