<?php

namespace App\Console\Commands\Migration\Steps;

use Illuminate\Console\Command;
use App\Console\Commands\Migration\LegacyFileManagerService;

/**
 * Шаг 2: Включение адаптеров
 */
class Step2EnableAdapters implements MigrationStepInterface
{
    public function __construct(
        private LegacyFileManagerService $fileManager
    ) {}

    public function execute(Command $command, bool $dryRun = false): bool
    {
        $command->line('Регистрируем AdapterServiceProvider...');
        
        if (!$dryRun) {
            $this->fileManager->updateServiceProviders();
        }

        $command->line('Публикуем конфигурацию...');
        
        if (!$dryRun) {
            $command->call('vendor:publish', [
                '--tag' => 'adapters-config',
                '--force' => true
            ]);
        }

        $command->info('Адаптеры включены');
        return true;
    }

    public function rollback(Command $command, bool $dryRun = false): bool
    {
        // Откат включения адаптеров
        return true;
    }
}