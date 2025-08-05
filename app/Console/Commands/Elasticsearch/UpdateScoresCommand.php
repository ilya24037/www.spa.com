<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;
use App\Infrastructure\Search\Indexers\AdIndexer;
use App\Infrastructure\Search\Indexers\MasterIndexer;

class UpdateScoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:update-scores 
                            {--index= : Specific index to update (ads, masters, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update boost scores for all documents in Elasticsearch';

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

        $this->info('Updating boost scores...');

        try {
            $startTime = microtime(true);

            switch ($index) {
                case 'ads':
                    $this->updateAdsScores();
                    break;
                    
                case 'masters':
                    $this->updateMastersScores();
                    break;
                    
                case 'all':
                    $this->updateAdsScores();
                    $this->updateMastersScores();
                    break;
                    
                default:
                    $this->error("Unknown index: {$index}");
                    return self::FAILURE;
            }

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✓ Scores updated in {$duration} seconds!");
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to update scores: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Update ads scores
     */
    protected function updateAdsScores(): void
    {
        $this->info('Updating ads boost scores...');
        $this->adIndexer->updateBoostScores();
        $this->info('✓ Ads scores updated');
    }

    /**
     * Update masters scores
     */
    protected function updateMastersScores(): void
    {
        $this->info('Updating masters boost scores...');
        $this->masterIndexer->updateBoostScores();
        $this->info('✓ Masters scores updated');
    }
}