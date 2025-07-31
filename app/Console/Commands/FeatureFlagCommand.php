<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FeatureFlagService;

class FeatureFlagCommand extends Command
{
    protected $signature = 'feature:flag 
                            {action : Action to perform (list|enable|disable|set|stats)}
                            {feature? : Feature name}
                            {--percentage= : Set percentage of users}
                            {--users= : Comma-separated user IDs}
                            {--roles= : Comma-separated roles}
                            {--days=7 : Days for statistics}';

    protected $description = 'Управление feature flags';

    private FeatureFlagService $featureFlags;

    public function __construct(FeatureFlagService $featureFlags)
    {
        parent::__construct();
        $this->featureFlags = $featureFlags;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $feature = $this->argument('feature');

        switch ($action) {
            case 'list':
                $this->listFlags();
                break;
                
            case 'enable':
                $this->enableFlag($feature);
                break;
                
            case 'disable':
                $this->disableFlag($feature);
                break;
                
            case 'set':
                $this->setFlag($feature);
                break;
                
            case 'stats':
                $this->showStats($feature);
                break;
                
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    private function listFlags()
    {
        $flags = $this->featureFlags->getAllFlags();
        
        if (empty($flags)) {
            $this->info('No feature flags found.');
            return;
        }

        $rows = [];
        foreach ($flags as $name => $config) {
            $status = $config['enabled'] ?? false ? '✅' : '❌';
            $percentage = isset($config['percentage']) ? $config['percentage'] . '%' : '-';
            $environments = isset($config['environments']) 
                ? implode(', ', $config['environments']) 
                : 'all';
            
            $rows[] = [
                $name,
                $status,
                $percentage,
                $environments,
                $config['description'] ?? '-'
            ];
        }

        $this->table(
            ['Feature', 'Enabled', 'Percentage', 'Environments', 'Description'],
            $rows
        );
    }

    private function enableFlag(?string $feature)
    {
        if (!$feature) {
            $this->error('Feature name is required');
            return;
        }

        $this->featureFlags->enable($feature);
        $this->info("Feature '{$feature}' has been enabled");
    }

    private function disableFlag(?string $feature)
    {
        if (!$feature) {
            $this->error('Feature name is required');
            return;
        }

        $this->featureFlags->disable($feature);
        $this->info("Feature '{$feature}' has been disabled");
    }

    private function setFlag(?string $feature)
    {
        if (!$feature) {
            $this->error('Feature name is required');
            return;
        }

        $config = $this->featureFlags->getFlag($feature) ?? ['enabled' => true];

        // Установка процента
        if ($this->option('percentage') !== null) {
            $percentage = (int) $this->option('percentage');
            $config['percentage'] = $percentage;
            $this->info("Set percentage to {$percentage}%");
        }

        // Установка пользователей
        if ($this->option('users')) {
            $users = array_map('intval', explode(',', $this->option('users')));
            $config['users'] = $users;
            $this->info('Set allowed users: ' . implode(', ', $users));
        }

        // Установка ролей
        if ($this->option('roles')) {
            $roles = explode(',', $this->option('roles'));
            $config['roles'] = array_map('trim', $roles);
            $this->info('Set allowed roles: ' . implode(', ', $config['roles']));
        }

        $this->featureFlags->setFlag($feature, $config);
        $this->info("Feature '{$feature}' configuration updated");
    }

    private function showStats(?string $feature)
    {
        if (!$feature) {
            $this->error('Feature name is required');
            return;
        }

        $days = (int) $this->option('days');
        $stats = $this->featureFlags->getUsageStats($feature, $days);

        $this->info("Statistics for '{$feature}' (last {$days} days):");
        $this->newLine();

        // Сводка
        $summary = $stats['summary'];
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Checks', number_format($summary['total_checks'])],
                ['Enabled Count', number_format($summary['enabled_count'])],
                ['Unique Users', number_format($summary['unique_users'])],
                ['Adoption Rate', $summary['adoption_rate'] . '%'],
            ]
        );

        // График по дням
        if (!empty($stats['daily_stats'])) {
            $this->newLine();
            $this->info('Daily usage:');
            
            $dailyRows = [];
            foreach ($stats['daily_stats'] as $day) {
                $adoptionRate = $day->total_checks > 0 
                    ? round(($day->enabled_count / $day->total_checks) * 100, 1)
                    : 0;
                
                $dailyRows[] = [
                    $day->date,
                    number_format($day->total_checks),
                    number_format($day->enabled_count),
                    number_format($day->unique_users),
                    $adoptionRate . '%'
                ];
            }
            
            $this->table(
                ['Date', 'Checks', 'Enabled', 'Users', 'Rate'],
                $dailyRows
            );
        }
    }
}