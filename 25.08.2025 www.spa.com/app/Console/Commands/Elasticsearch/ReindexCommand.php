<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;
use App\Infrastructure\Search\Indexers\AdIndexer;
use App\Infrastructure\Search\Indexers\MasterIndexer;

class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:reindex 
                            {--index= : Specific index to reindex (ads, masters, all)}
                            {--batch-size=1000 : Number of documents to index at once}
                            {--where= : Additional where conditions (JSON format)}
                            {--fresh : Delete and recreate index before reindexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all documents in Elasticsearch';

    protected AdIndexer $adIndexer;
    protected MasterIndexer $masterIndexer;

    /**
     * Create a new command instance.
     */
    public function __construct(
        AdIndexer $adIndexer,
        MasterIndexer $masterIndexer
    ) {
        parent::__construct();
        
        $this->adIndexer = $adIndexer;
        $this->masterIndexer = $masterIndexer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $index = $this->option('index') ?? 'all';
        $batchSize = (int) $this->option('batch-size');
        $whereConditions = $this->option('where');
        $fresh = $this->option('fresh');

        $this->info('Starting reindexing process...');
        $this->info("Batch size: {$batchSize}");

        if ($whereConditions) {
            $conditions = json_decode($whereConditions, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON format for where conditions');
                return self::FAILURE;
            }
            $this->info('With conditions: ' . $whereConditions);
        }

        try {
            // Recreate indices if --fresh option is used
            if ($fresh) {
                $this->call('elasticsearch:create-indices', [
                    '--index' => $index,
                    '--force' => true
                ]);
            }

            $startTime = microtime(true);

            switch ($index) {
                case 'ads':
                    $this->reindexAds($batchSize, $conditions ?? []);
                    break;
                    
                case 'masters':
                    $this->reindexMasters($batchSize, $conditions ?? []);
                    break;
                    
                case 'all':
                    $this->reindexAds($batchSize, $conditions ?? []);
                    $this->reindexMasters($batchSize, $conditions ?? []);
                    break;
                    
                default:
                    $this->error("Unknown index: {$index}");
                    return self::FAILURE;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✓ Reindexing completed in {$duration} seconds!");
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to reindex: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Reindex ads
     */
    protected function reindexAds(int $batchSize, array $conditions): void
    {
        $this->info('Reindexing ads...');
        
        if (empty($conditions)) {
            $this->adIndexer->reindexAll($batchSize);
        } else {
            $this->adIndexer->reindexWhere($conditions, $batchSize);
        }
        
        $this->info('✓ Ads reindexed');
    }

    /**
     * Reindex masters
     */
    protected function reindexMasters(int $batchSize, array $conditions): void
    {
        $this->info('Reindexing masters...');
        
        if (empty($conditions)) {
            $this->masterIndexer->reindexAll($batchSize);
        } else {
            $this->masterIndexer->reindexWhere($conditions, $batchSize);
        }
        
        $this->info('✓ Masters reindexed');
    }
}