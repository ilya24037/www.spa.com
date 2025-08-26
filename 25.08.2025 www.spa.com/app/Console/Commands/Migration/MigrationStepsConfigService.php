<?php

namespace App\Console\Commands\Migration;

/**
 * Сервис конфигурации шагов миграции
 */
class MigrationStepsConfigService
{
    /**
     * Получить все шаги миграции
     */
    public function getAllSteps(): array
    {
        return [
            '1_prepare_environment' => [
                'name' => 'Подготовка окружения',
                'description' => 'Создание необходимых таблиц и настроек',
                'class' => Step1PrepareEnvironment::class,
            ],
            '2_enable_adapters' => [
                'name' => 'Включение адаптеров',
                'description' => 'Активация адаптеров для постепенной миграции',
                'class' => Step2EnableAdapters::class,
            ],
            '3_migrate_booking_service' => [
                'name' => 'Миграция BookingService',
                'description' => 'Постепенный переход на новый BookingService',
                'class' => Step3MigrateBookingService::class,
            ],
            '4_migrate_master_service' => [
                'name' => 'Миграция MasterService',
                'description' => 'Переход на новый MasterService',
                'class' => Step4MigrateMasterService::class,
            ],
            '5_migrate_search_engine' => [
                'name' => 'Миграция SearchEngine',
                'description' => 'Переход на новый поисковый движок',
                'class' => Step5MigrateSearchEngine::class,
            ],
            '6_cleanup_legacy' => [
                'name' => 'Очистка legacy кода',
                'description' => 'Удаление старого кода после успешной миграции',
                'class' => Step6CleanupLegacy::class,
            ],
        ];
    }

    /**
     * Получить шаг по ключу
     */
    public function getStep(string $key): ?array
    {
        $steps = $this->getAllSteps();
        return $steps[$key] ?? null;
    }

    /**
     * Получить список ключей шагов
     */
    public function getStepKeys(): array
    {
        return array_keys($this->getAllSteps());
    }

    /**
     * Проверить существование шага
     */
    public function stepExists(string $key): bool
    {
        return isset($this->getAllSteps()[$key]);
    }
}