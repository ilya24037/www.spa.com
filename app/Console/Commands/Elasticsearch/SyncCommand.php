<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;
use App\Infrastructure\Search\Indexers\AdIndexer;
use App\Infrastructure\Search\Indexers\MasterIndexer;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:sync 
                            {--index= : Specific index to sync (ads, masters, all)}
                            {--minutes=60 : Sync documents updated in last N minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync recent changes from database to Elasticsearch';

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
        $minutes = (int) $this->option('minutes');

        $this->info("Syncing documents updated in last {$minutes} minutes...");

        try {
            switch ($index) {
                case 'ads':
                    $this->syncAds($minutes);
                    break;
                    
                case 'masters':
                    $this->syncMasters($minutes);
                    break;
                    
                case 'all':
                    $this->syncAds($minutes);
                    $this->syncMasters($minutes);
                    break;
                    
                default:
                    $this->error("Unknown index: {$index}");
                    return self::FAILURE;
            }

            $this->info('✓ Sync completed successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to sync: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Sync ads
     */
    protected function syncAds(int $minutes): void
    {
        $this->info('Syncing ads...');
        $this->adIndexer->syncWithDatabase($minutes);
        $this->info('✓ Ads synced');
    }

    /**
     * Sync masters
     */
    protected function syncMasters(int $minutes): void
    {
        $this->info('Syncing masters...');
        $this->masterIndexer->syncWithDatabase($minutes);
        $this->info('✓ Masters synced');
    }
}