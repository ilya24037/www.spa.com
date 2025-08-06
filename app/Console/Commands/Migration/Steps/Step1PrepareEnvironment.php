<?php

namespace App\Console\Commands\Migration\Steps;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Infrastructure\Feature\FeatureFlagService;

/**
 * Шаг 1: Подготовка окружения
 */
class Step1PrepareEnvironment
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function execute(Command $command, bool $dryRun = false): bool
    {
        $command->line('Создаем таблицы для миграции...');
        
        if (!$dryRun) {
            $this->createMigrationTables();
        }

        $command->line('Настраиваем feature flags...');
        
        if (!$dryRun) {
            $this->initializeFeatureFlags();
        }

        $command->info('Окружение подготовлено');
        return true;
    }

    public function rollback(Command $command, bool $dryRun = false): bool
    {
        if (!$dryRun) {
            if (Schema::hasTable('modular_migration_status')) {
                Schema::drop('modular_migration_status');
            }
            
            if (Schema::hasTable('legacy_call_logs')) {
                Schema::drop('legacy_call_logs');
            }
        }

        return true;
    }

    private function createMigrationTables(): void
    {
        // Создаем таблицу для отслеживания миграции
        if (!Schema::hasTable('modular_migration_status')) {
            Schema::create('modular_migration_status', function ($table) {
                $table->id();
                $table->string('step')->unique();
                $table->string('status');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // Создаем логи для отслеживания legacy вызовов
        if (!Schema::hasTable('legacy_call_logs')) {
            Schema::create('legacy_call_logs', function ($table) {
                $table->id();
                $table->string('service');
                $table->string('method');
                $table->json('parameters')->nullable();
                $table->string('caller')->nullable();
                $table->timestamp('called_at');
                $table->index(['service', 'method']);
                $table->index('called_at');
            });
        }
    }

    private function initializeFeatureFlags(): void
    {
        $this->featureFlags->setFlag('use_adapters', [
            'enabled' => true,
            'description' => 'Использовать адаптеры для миграции'
        ]);
        
        $this->featureFlags->setFlag('log_legacy_calls', [
            'enabled' => true,
            'description' => 'Логировать legacy вызовы'
        ]);
    }
}