<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Domain\Notification\Services\TemplateImportExportService;
use App\Domain\Notification\Services\TemplateStatsService;
use App\Domain\Notification\Services\TemplateDefaultsService;
use App\Domain\Notification\Services\TemplateBackupService;
use App\Domain\Notification\Services\TemplateValidationService;
use App\Domain\Notification\Services\TemplateMaintenanceService;

/**
 * Главный обработчик управления шаблонами - координатор
 */
class TemplateManagementHandler
{
    public function __construct(
        private TemplateImportExportService $importExportService,
        private TemplateStatsService $statsService,
        private TemplateDefaultsService $defaultsService,
        private TemplateBackupService $backupService,
        private TemplateValidationService $validationService,
        private TemplateMaintenanceService $maintenanceService
    ) {}

    // === ИМПОРТ/ЭКСПОРТ ===

    /**
     * Экспорт шаблона
     */
    public function export(NotificationTemplate $template): array
    {
        return $this->importExportService->export($template);
    }

    /**
     * Импорт шаблона
     */
    public function import(array $data): NotificationTemplate
    {
        return $this->importExportService->import($data);
    }

    /**
     * Массовый экспорт шаблонов
     */
    public function batchExport(array $templateIds): array
    {
        return $this->importExportService->batchExport($templateIds);
    }

    /**
     * Массовый импорт шаблонов
     */
    public function batchImport(array $templatesData): array
    {
        return $this->importExportService->batchImport($templatesData);
    }

    /**
     * Синхронизация с файловой системой
     */
    public function syncWithFileSystem(string $templatesPath): array
    {
        return $this->importExportService->syncWithFileSystem($templatesPath);
    }

    // === СТАТИСТИКА ===

    /**
     * Получить статистику использования шаблонов
     */
    public function getUsageStats(NotificationTemplate $template = null): array
    {
        return $this->statsService->getUsageStats($template);
    }

    // === ШАБЛОНЫ ПО УМОЛЧАНИЮ ===

    /**
     * Создать шаблоны по умолчанию
     */
    public function createDefaultTemplates(): array
    {
        return $this->defaultsService->createDefaultTemplates();
    }

    // === РЕЗЕРВНОЕ КОПИРОВАНИЕ ===

    /**
     * Создать резервную копию всех шаблонов
     */
    public function createBackup(): array
    {
        return $this->backupService->createBackup();
    }

    /**
     * Восстановить из резервной копии
     */
    public function restoreFromBackup(string $backupFile): array
    {
        return $this->backupService->restoreFromBackup($backupFile);
    }

    // === ВАЛИДАЦИЯ ===

    /**
     * Валидировать структуру данных импорта
     */
    public function validateImportData(array $data): array
    {
        return $this->validationService->validateImportData($data);
    }

    // === ОБСЛУЖИВАНИЕ ===

    /**
     * Очистка неиспользуемых шаблонов
     */
    public function cleanupUnusedTemplates(int $daysUnused = 180): array
    {
        return $this->maintenanceService->cleanupUnusedTemplates($daysUnused);
    }
}