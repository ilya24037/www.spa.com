<?php

namespace App\Console\Commands\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Сервис управления статусом миграции
 */
class MigrationStatusService
{
    /**
     * Получить завершенные шаги
     */
    public function getCompletedSteps(bool $dryRun = false): array
    {
        if ($dryRun || !Schema::hasTable('modular_migration_status')) {
            return [];
        }

        return DB::table('modular_migration_status')
            ->where('status', 'completed')
            ->pluck('step')
            ->toArray();
    }

    /**
     * Отметить шаг как завершенный
     */
    public function markStepCompleted(string $step, bool $dryRun = false): void
    {
        if ($dryRun || !Schema::hasTable('modular_migration_status')) {
            return;
        }

        DB::table('modular_migration_status')->updateOrInsert(
            ['step' => $step],
            [
                'status' => 'completed',
                'metadata' => json_encode([
                    'completed_at' => now()->toDateTimeString(),
                    'user' => auth()->user()->name ?? 'console'
                ]),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Отметить шаг как неудавшийся
     */
    public function markStepFailed(string $step, string $error, bool $dryRun = false): void
    {
        if ($dryRun || !Schema::hasTable('modular_migration_status')) {
            return;
        }

        DB::table('modular_migration_status')->updateOrInsert(
            ['step' => $step],
            [
                'status' => 'failed',
                'metadata' => json_encode([
                    'failed_at' => now()->toDateTimeString(),
                    'error' => $error,
                    'user' => auth()->user()->name ?? 'console'
                ]),
                'updated_at' => now()
            ]
        );
    }

    /**
     * Получить статус конкретного шага
     */
    public function getStepStatus(string $step): ?string
    {
        if (!Schema::hasTable('modular_migration_status')) {
            return null;
        }

        return DB::table('modular_migration_status')
            ->where('step', $step)
            ->value('status');
    }

    /**
     * Получить все статусы миграции
     */
    public function getAllStatuses(): array
    {
        if (!Schema::hasTable('modular_migration_status')) {
            return [];
        }

        return DB::table('modular_migration_status')
            ->select('step', 'status', 'metadata', 'updated_at')
            ->get()
            ->keyBy('step')
            ->toArray();
    }
}