<?php

namespace App\Console\Commands\Migration;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Сервис выполнения миграционных шагов
 */
class MigrationExecutorService
{
    public function __construct(
        private MigrationStepsConfigService $stepsConfig,
        private MigrationStatusService $statusService
    ) {}

    /**
     * Выполнить все шаги
     */
    public function executeAllSteps(Command $command, bool $dryRun = false, bool $force = false): int
    {
        $command->info('Начинаем миграцию на модульную архитектуру...');
        
        if (!$force && !$command->confirm('Это действие изменит архитектуру приложения. Продолжить?')) {
            return 1;
        }

        $completedSteps = $this->statusService->getCompletedSteps($dryRun);
        $steps = $this->stepsConfig->getAllSteps();
        
        foreach ($steps as $key => $stepConfig) {
            if (in_array($key, $completedSteps)) {
                $command->line("Шаг '{$stepConfig['name']}' уже выполнен. Пропускаем.");
                continue;
            }

            $result = $this->executeStep($key, $stepConfig, $command, $dryRun);
            
            if ($result !== 0) {
                return $result;
            }
        }

        $command->newLine();
        $command->info('🎉 Миграция завершена успешно!');
        
        return 0;
    }

    /**
     * Выполнить конкретный шаг
     */
    public function executeSingleStep(string $stepKey, Command $command, bool $dryRun = false): int
    {
        $stepConfig = $this->stepsConfig->getStep($stepKey);
        
        if (!$stepConfig) {
            $command->error("Шаг '{$stepKey}' не найден");
            return 1;
        }

        return $this->executeStep($stepKey, $stepConfig, $command, $dryRun);
    }

    /**
     * Выполнить шаг
     */
    private function executeStep(string $key, array $stepConfig, Command $command, bool $dryRun): int
    {
        $command->info("\n📋 Выполняем: {$stepConfig['name']}");
        $command->line($stepConfig['description']);
        
        try {
            $stepInstance = app($stepConfig['class']);
            $result = $stepInstance->execute($command, $dryRun);
            
            if ($result === false) {
                $command->error("Ошибка при выполнении шага '{$stepConfig['name']}'");
                $this->statusService->markStepFailed($key, 'Step execution returned false', $dryRun);
                return 1;
            }
            
            if (!$dryRun) {
                $this->statusService->markStepCompleted($key);
            }
            
            $command->info("✅ Шаг '{$stepConfig['name']}' выполнен успешно!");
            return 0;
            
        } catch (\Exception $e) {
            $command->error("❌ Ошибка: " . $e->getMessage());
            
            Log::error('Migration step failed', [
                'step' => $key,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->statusService->markStepFailed($key, $e->getMessage(), $dryRun);
            return 1;
        }
    }
}