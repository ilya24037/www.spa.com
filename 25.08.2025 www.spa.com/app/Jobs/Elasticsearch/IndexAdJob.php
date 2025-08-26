<?php

namespace App\Jobs\Elasticsearch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Ad\Models\Ad;
use App\Infrastructure\Search\Indexers\AdIndexer;
use Illuminate\Support\Facades\Log;

class IndexAdJob implements ShouldQueue
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
    public $timeout = 30;

    /**
     * The ad instance.
     *
     * @var Ad
     */
    protected Ad $ad;

    /**
     * Create a new job instance.
     */
    public function __construct(Ad $ad)
    {
        $this->ad = $ad;
        $this->queue = config('elasticsearch.queue.queue', 'elasticsearch');
    }

    /**
     * Execute the job.
     */
    public function handle(AdIndexer $indexer): void
    {
        try {
            // Обновляем модель из БД для получения актуальных данных
            $this->ad->refresh();

            // Индексируем только активные и опубликованные объявления
            if ($this->ad->status === 'active' && $this->ad->is_published) {
                $indexer->index($this->ad);
                
                Log::info('Ad indexed in Elasticsearch', [
                    'ad_id' => $this->ad->id,
                    'title' => $this->ad->title
                ]);
            } else {
                // Удаляем из индекса неактивные объявления
                $indexer->delete($this->ad->id);
                
                Log::info('Ad removed from Elasticsearch index', [
                    'ad_id' => $this->ad->id,
                    'status' => $this->ad->status,
                    'is_published' => $this->ad->is_published
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to index ad in Elasticsearch', [
                'ad_id' => $this->ad->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Перебрасываем исключение для повторной попытки
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ad indexing job failed permanently', [
            'ad_id' => $this->ad->id,
            'error' => $exception->getMessage()
        ]);

        // Можно отправить уведомление администратору
        // или записать в таблицу failed_indexing_jobs
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['elasticsearch', 'indexing', 'ad:' . $this->ad->id];
    }
}