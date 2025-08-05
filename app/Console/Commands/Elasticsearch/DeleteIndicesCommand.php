<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;
use App\Infrastructure\Search\ElasticsearchClient;

class DeleteIndicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:delete-indices 
                            {--index= : Specific index to delete (ads, masters, all)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Elasticsearch indices';

    protected ElasticsearchClient $client;

    /**
     * Create a new command instance.
     */
    public function __construct(ElasticsearchClient $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $index = $this->option('index') ?? 'all';
        $force = $this->option('force');

        $indices = match($index) {
            'ads' => ['ads'],
            'masters' => ['masters'],
            'all' => ['ads', 'masters'],
            default => null
        };

        if ($indices === null) {
            $this->error("Unknown index: {$index}");
            return self::FAILURE;
        }

        if (!$force) {
            $this->warn('⚠️  This will permanently delete the following indices:');
            foreach ($indices as $idx) {
                $this->warn("   - {$idx}");
            }
            
            if (!$this->confirm('Do you really want to delete these indices?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        try {
            foreach ($indices as $idx) {
                $this->info("Deleting {$idx} index...");
                
                if ($this->client->indexExists($idx)) {
                    $this->client->deleteIndex($idx);
                    $this->info("✓ {$idx} index deleted");
                } else {
                    $this->warn("Index {$idx} does not exist");
                }
            }

            $this->info('✓ Deletion completed!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to delete indices: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }
}