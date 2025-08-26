<?php

namespace App\Jobs\Elasticsearch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Domain\Master\Models\MasterProfile;
use App\Infrastructure\Search\Indexers\MasterIndexer;
use Illuminate\Support\Facades\Log;

class IndexMasterJob implements ShouldQueue
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
     * The master profile instance.
     *
     * @var MasterProfile
     */
    protected MasterProfile $master;

    /**
     * Create a new job instance.
     */
    public function __construct(MasterProfile $master)
    {
        $this->master = $master;
        $this->queue = config('elasticsearch.queue.queue', 'elasticsearch');
    }

    /**
     * Execute the job.
     */
    public function handle(MasterIndexer $indexer): void
    {
        try {
            // Обновляем модель из БД для получения актуальных данных
            $this->master->refresh();

            // Индексируем только активных мастеров
            if ($this->master->is_active) {
                $indexer->index($this->master);
                
                Log::info('Master indexed in Elasticsearch', [
                    'master_id' => $this->master->id,
                    'user_id' => $this->master->user_id,
                    'name' => $this->master->user->name ?? 'Unknown'
                ]);
            } else {
                // Удаляем из индекса неактивных мастеров
                $indexer->delete($this->master->id);
                
                Log::info('Master removed from Elasticsearch index', [
                    'master_id' => $this->master->id,
                    'is_active' => $this->master->is_active
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to index master in Elasticsearch', [
                'master_id' => $this->master->id,
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
        Log::error('Master indexing job failed permanently', [
            'master_id' => $this->master->id,
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
        return ['elasticsearch', 'indexing', 'master:' . $this->master->id];
    }
}