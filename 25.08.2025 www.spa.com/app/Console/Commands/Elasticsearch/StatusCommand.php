<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;
use App\Infrastructure\Search\ElasticsearchClient;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:status 
                            {--index= : Specific index to check (ads, masters, all)}
                            {--health : Show cluster health}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show Elasticsearch cluster and indices status';

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
        $showHealth = $this->option('health');

        try {
            if ($showHealth) {
                $this->showClusterHealth();
            }

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

            $this->showIndicesStatus($indices);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to get status: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }

    /**
     * Show cluster health
     */
    protected function showClusterHealth(): void
    {
        $this->info('Elasticsearch Cluster Health');
        $this->info('══════════════════════════════');

        $health = $this->client->getClusterHealth();

        $statusColor = match($health['status']) {
            'green' => 'info',
            'yellow' => 'comment',
            'red' => 'error',
            default => 'info'
        };

        $this->line("Status: <{$statusColor}>{$health['status']}</{$statusColor}>");
        $this->line("Cluster Name: {$health['cluster_name']}");
        $this->line("Number of Nodes: {$health['number_of_nodes']}");
        $this->line("Active Shards: {$health['active_shards']}");
        $this->line("Relocating Shards: {$health['relocating_shards']}");
        $this->line("Initializing Shards: {$health['initializing_shards']}");
        $this->line("Unassigned Shards: {$health['unassigned_shards']}");
        $this->newLine();
    }

    /**
     * Show indices status
     */
    protected function showIndicesStatus(array $indices): void
    {
        $this->info('Indices Status');
        $this->info('══════════════════════════════');

        $headers = ['Index', 'Exists', 'Documents', 'Size', 'Primary Size'];
        $rows = [];

        foreach ($indices as $idx) {
            $fullIndexName = config('elasticsearch.index_prefix') . $idx;
            
            if ($this->client->indexExists($idx)) {
                $stats = $this->client->getIndexStats($idx);
                $indexStats = $stats['indices'][$fullIndexName] ?? [];
                
                $rows[] = [
                    $idx,
                    '<info>✓</info>',
                    number_format($indexStats['primaries']['docs']['count'] ?? 0),
                    $this->formatBytes($indexStats['total']['store']['size_in_bytes'] ?? 0),
                    $this->formatBytes($indexStats['primaries']['store']['size_in_bytes'] ?? 0),
                ];
            } else {
                $rows[] = [
                    $idx,
                    '<error>✗</error>',
                    '-',
                    '-',
                    '-',
                ];
            }
        }

        $this->table($headers, $rows);
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}