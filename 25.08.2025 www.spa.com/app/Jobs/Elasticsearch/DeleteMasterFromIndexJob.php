<?php

namespace App\Jobs\Elasticsearch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Infrastructure\Search\Indexers\MasterIndexer;
use Illuminate\Support\Facades\Log;

class DeleteMasterFromIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The master ID to delete.
     *
     * @var int
     */
    protected int $masterId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $masterId)
    {
        $this->masterId = $masterId;
        $this->queue = config('elasticsearch.queue.queue', 'elasticsearch');
    }

    /**
     * Execute the job.
     */
    public function handle(MasterIndexer $indexer): void
    {
        try {
            $indexer->delete($this->masterId);
            
            Log::info('Master deleted from Elasticsearch index', [
                'master_id' => $this->masterId
            ]);
        } catch (\Exception $e) {
            // Если документ не найден, не считаем это ошибкой
            if ($e->getCode() === 404) {
                Log::debug('Master not found in Elasticsearch index', [
                    'master_id' => $this->masterId
                ]);
                return;
            }
            
            Log::error('Failed to delete master from Elasticsearch', [
                'master_id' => $this->masterId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Master deletion job failed permanently', [
            'master_id' => $this->masterId,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['elasticsearch', 'deletion', 'master:' . $this->masterId];
    }
}