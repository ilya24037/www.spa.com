<?php

namespace App\Console\Commands\Migration\Steps;

use Illuminate\Console\Command;
use App\Infrastructure\Feature\FeatureFlagService;

/**
 * Шаг 3: Миграция BookingService
 */
class Step3MigrateBookingService
{
    public function __construct(
        private FeatureFlagService $featureFlags
    ) {}

    public function execute(Command $command, bool $dryRun = false): bool
    {
        $command->line('Начинаем миграцию BookingService...');
        
        // Фаза 1: 10% пользователей
        $this->executePhase1($command, $dryRun);
        
        // Фаза 2: 50% пользователей (с подтверждением)
        if ($this->shouldContinueToPhase2($command, $dryRun)) {
            $this->executePhase2($command, $dryRun);
        } else {
            return false;
        }
        
        // Фаза 3: 100% пользователей
        $this->executePhase3($command, $dryRun);

        $command->info('BookingService мигрирован');
        return true;
    }

    public function rollback(Command $command, bool $dryRun = false): bool
    {
        if (!$dryRun) {
            $this->featureFlags->disable('use_modern_booking_service');
        }
        return true;
    }

    private function executePhase1(Command $command, bool $dryRun): void
    {
        $command->line('Фаза 1: Включаем для 10% пользователей');
        
        if (!$dryRun) {
            $this->featureFlags->setFlag('use_modern_booking_service', [
                'enabled' => true,
                'percentage' => 10,
                'description' => 'Новый BookingService - фаза 1'
            ]);
        }
    }

    private function shouldContinueToPhase2(Command $command, bool $dryRun): bool
    {
        $command->line('Ждем 24 часа для мониторинга...');
        
        if (!$dryRun && !$command->option('force')) {
            $command->warn('Проверьте метрики и логи ошибок перед продолжением');
            return $command->confirm('Продолжить с фазой 2?');
        }
        
        return true;
    }

    private function executePhase2(Command $command, bool $dryRun): void
    {
        $command->line('Фаза 2: Увеличиваем до 50% пользователей');
        
        if (!$dryRun) {
            $this->featureFlags->setPercentage('use_modern_booking_service', 50);
        }
    }

    private function executePhase3(Command $command, bool $dryRun): void
    {
        $command->line('Фаза 3: Включаем для всех пользователей');
        
        if (!$dryRun) {
            $this->featureFlags->setPercentage('use_modern_booking_service', 100);
        }
    }
}