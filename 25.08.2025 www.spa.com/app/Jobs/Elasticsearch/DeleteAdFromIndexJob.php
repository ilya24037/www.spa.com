<?php

namespace App\Jobs\Elasticsearch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Infrastructure\Search\Indexers\AdIndexer;
use Illuminate\Support\Facades\Log;

class DeleteAdFromIndexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The ad ID to delete.
     *
     * @var int
     */
    protected int $adId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $adId)
    {
        $this->adId = $adId;
        $this->queue = config('elasticsearch.queue.queue', 'elasticsearch');
    }

    /**
     * Execute the job.
     */
    public function handle(AdIndexer $indexer): void
    {
        try {
            $indexer->delete($this->adId);
            
            Log::info('Ad deleted from Elasticsearch index', [
                'ad_id' => $this->adId
            ]);
        } catch (\Exception $e) {
            // Если документ не найден, не считаем это ошибкой
            if ($e->getCode() === 404) {
                Log::debug('Ad not found in Elasticsearch index', [
                    'ad_id' => $this->adId
                ]);
                return;
            }
            
            Log::error('Failed to delete ad from Elasticsearch', [
                'ad_id' => $this->adId,
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
        Log::error('Ad deletion job failed permanently', [
            'ad_id' => $this->adId,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['elasticsearch', 'deletion', 'ad:' . $this->adId];
    }
}