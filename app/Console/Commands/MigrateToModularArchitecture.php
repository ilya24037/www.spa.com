<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Commands\Migration\MigrationExecutorService;
use App\Console\Commands\Migration\MigrationStepsConfigService;

/**
 * Консольная команда миграции на модульную архитектуру - координатор
 */
class MigrateToModularArchitecture extends Command
{
    protected $signature = 'app:migrate-modular 
                            {--step= : Migration step to run}
                            {--rollback : Rollback the migration}
                            {--dry-run : Run without making changes}
                            {--force : Force migration without confirmation}';

    protected $description = 'Постепенная миграция на модульную архитектуру';

    public function __construct(
        private MigrationExecutorService $executor,
        private MigrationStepsConfigService $stepsConfig
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($this->option('rollback')) {
            $this->error('Откат миграции пока не реализован');
            return 1;
        }

        $step = $this->option('step');
        
        if ($step) {
            if (!$this->stepsConfig->stepExists($step)) {
                $this->error("Шаг '{$step}' не найден");
                return 1;
            }
            
            return $this->executor->executeSingleStep($step, $this, $dryRun);
        }

        return $this->executor->executeAllSteps($this, $dryRun, $force);
    }
}