<?php

namespace App\Console\Commands\Migration\Steps;

use Illuminate\Console\Command;

/**
 * Интерфейс для шагов миграции
 */
interface MigrationStepInterface
{
    /**
     * Выполнить шаг
     */
    public function execute(Command $command, bool $dryRun = false): bool;

    /**
     * Откатить шаг
     */
    public function rollback(Command $command, bool $dryRun = false): bool;
}