<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;
use App\Infrastructure\Search\ElasticsearchClient;
use App\Infrastructure\Search\Indexers\AdIndexer;
use App\Infrastructure\Search\Indexers\MasterIndexer;

class CreateIndicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:create-indices 
                            {--index= : Specific index to create (ads, masters, all)}
                            {--force : Force recreate indices if they exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Elasticsearch indices with proper mappings';

    protected ElasticsearchClient $client;
    protected AdIndexer $adIndexer;
    protected MasterIndexer $masterIndexer;

    /**
     * Create a new command instance.
     */
    public function __construct(
        ElasticsearchClient $client,
        AdIndexer $adIndexer,
        MasterIndexer $masterIndexer
    ) {
        parent::__construct();
        
        $this->client = $client;
        $this->adIndexer = $adIndexer;
        $this->masterIndexer = $masterIndexer;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $index = $this->option('index') ?? 'all';
        $force = $this->option('force');

        $this->info('Starting Elasticsearch indices creation...');

        try {
            switch ($index) {
                case 'ads':
                    $this->createAdsIndex($force);
                    break;
                    
                case 'masters':
                    $this->createMastersIndex($force);
                    break;
                    
                case 'all':
                    $this->createAdsIndex($force);
                    $this->createMastersIndex($force);
                    break;
                    
                default:
                    $this->error("Unknown index: {$index}");
                    return self::FAILURE;
            }

            $this->info('✓ Indices created successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create indices: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Create ads index
     */
    protected function createAdsIndex(bool $force): void
    {
        $this->info('Creating ads index...');

        if ($this->client->indexExists('ads')) {
            if (!$force) {
                $this->warn('Ads index already exists. Use --force to recreate.');
                return;
            }
            
            $this->warn('Deleting existing ads index...');
            $this->client->deleteIndex('ads');
        }

        $this->adIndexer->createIndex();
        $this->info('✓ Ads index created');
    }

    /**
     * Create masters index
     */
    protected function createMastersIndex(bool $force): void
    {
        $this->info('Creating masters index...');

        if ($this->client->indexExists('masters')) {
            if (!$force) {
                $this->warn('Masters index already exists. Use --force to recreate.');
                return;
            }
            
            $this->warn('Deleting existing masters index...');
            $this->client->deleteIndex('masters');
        }

        $this->masterIndexer->createIndex();
        $this->info('✓ Masters index created');
    }
}