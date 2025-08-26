<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Domain\Notification\Handlers\TemplateCrudHandler;
use App\Domain\Notification\Handlers\TemplateQueryHandler;
use App\Domain\Notification\Handlers\TemplateRenderHandler;
use App\Domain\Notification\Handlers\TemplateManagementHandler;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;

/**
 * Упрощенный сервис для работы с шаблонами уведомлений
 * Делегирует операции специализированным обработчикам
 */
class TemplateService
{
    public function __construct(
        protected TemplateCrudHandler $crudHandler,
        protected TemplateQueryHandler $queryHandler,
        protected TemplateRenderHandler $renderHandler,
        protected TemplateManagementHandler $managementHandler
    ) {}

    // === CRUD ОПЕРАЦИИ ===

    public function create(array $data): NotificationTemplate
    {
        return $this->crudHandler->create($data);
    }

    public function update(NotificationTemplate $template, array $data): NotificationTemplate
    {
        return $this->crudHandler->update($template, $data);
    }

    public function activate(NotificationTemplate $template): bool
    {
        return $this->crudHandler->activate($template);
    }

    public function deactivate(NotificationTemplate $template): bool
    {
        return $this->crudHandler->deactivate($template);
    }

    public function duplicate(NotificationTemplate $template, string $newName = null): NotificationTemplate
    {
        return $this->crudHandler->duplicate($template, $newName);
    }

    public function delete(NotificationTemplate $template): bool
    {
        return $this->crudHandler->delete($template);
    }

    public function batchUpdate(array $templateIds, array $data): int
    {
        return $this->crudHandler->batchUpdate($templateIds, $data);
    }

    public function batchToggleStatus(array $templateIds, bool $isActive): int
    {
        return $this->crudHandler->batchToggleStatus($templateIds, $isActive);
    }

    // === ЗАПРОСЫ И ПОИСК ===

    public function getTemplate(string $name, string $locale = 'ru'): ?NotificationTemplate
    {
        return $this->queryHandler->getTemplate($name, $locale);
    }

    public function getTemplateByType(NotificationType $type, string $locale = 'ru'): ?NotificationTemplate
    {
        return $this->queryHandler->getTemplateByType($type, $locale);
    }

    public function getAll(array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->queryHandler->getAll($filters);
    }

    public function getByChannel(NotificationChannel $channel, string $locale = 'ru'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getByChannel($channel, $locale);
    }

    public function getByCategory(string $category, string $locale = 'ru'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getByCategory($category, $locale);
    }

    public function search(string $query, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->search($query, $filters);
    }

    public function getPopularTemplates(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getPopularTemplates($limit);
    }

    public function getRecentlyUsed(int $days = 30, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getRecentlyUsed($days, $limit);
    }

    public function getUnusedTemplates(int $days = 90): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getUnusedTemplates($days);
    }

    public function getTemplatesWithErrors(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getTemplatesWithErrors();
    }

    public function getDuplicateTemplates(): array
    {
        return $this->queryHandler->getDuplicateTemplates();
    }

    public function getTemplateStats(): array
    {
        return $this->queryHandler->getTemplateStats();
    }

    public function findSimilarTemplates(NotificationTemplate $template, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->findSimilarTemplates($template, $limit);
    }

    public function exists(string $name, string $locale = 'ru'): bool
    {
        return $this->queryHandler->exists($name, $locale);
    }

    public function getActiveCountByType(NotificationType $type): int
    {
        return $this->queryHandler->getActiveCountByType($type);
    }

    public function getAvailableTemplates(string $locale = 'ru'): array
    {
        return $this->queryHandler->getAvailableTemplates($locale);
    }

    public function getAllVariables(): array
    {
        return $this->queryHandler->getAllVariables();
    }

    public function getTemplatesForExport(array $templateIds): \Illuminate\Database\Eloquent\Collection
    {
        return $this->queryHandler->getTemplatesForExport($templateIds);
    }

    // === РЕНДЕРИНГ И ТЕСТИРОВАНИЕ ===

    public function render(NotificationTemplate $template, array $data = []): array
    {
        return $this->renderHandler->render($template, $data);
    }

    public function test(NotificationTemplate $template, array $testData = []): array
    {
        return $this->renderHandler->test($template, $testData);
    }

    public function batchTest(array $templates, array $testData = []): array
    {
        return $this->renderHandler->batchTest($templates, $testData);
    }

    public function preview(NotificationTemplate $template, array $data = []): array
    {
        return $this->renderHandler->preview($template, $data);
    }

    public function validateVariables(NotificationTemplate $template, array $data): array
    {
        return $this->renderHandler->validateVariables($template, $data);
    }

    public function findMissingVariables(NotificationTemplate $template, array $data): array
    {
        return $this->renderHandler->findMissingVariables($template, $data);
    }

    public function findUnusedVariables(NotificationTemplate $template, array $data): array
    {
        return $this->renderHandler->findUnusedVariables($template, $data);
    }

    public function optimize(NotificationTemplate $template): array
    {
        return $this->renderHandler->optimize($template);
    }

    public function createRenderingReport(NotificationTemplate $template, array $testCases = []): array
    {
        return $this->renderHandler->createRenderingReport($template, $testCases);
    }

    public function getDefaultTestData(): array
    {
        return $this->renderHandler->getDefaultTestData();
    }

    // === УПРАВЛЕНИЕ И СТАТИСТИКА ===

    public function export(NotificationTemplate $template): array
    {
        return $this->managementHandler->export($template);
    }

    public function import(array $data): NotificationTemplate
    {
        return $this->managementHandler->import($data);
    }

    public function batchExport(array $templateIds): array
    {
        return $this->managementHandler->batchExport($templateIds);
    }

    public function batchImport(array $templatesData): array
    {
        return $this->managementHandler->batchImport($templatesData);
    }

    public function getUsageStats(NotificationTemplate $template = null): array
    {
        return $this->managementHandler->getUsageStats($template);
    }

    public function createDefaultTemplates(): array
    {
        return $this->managementHandler->createDefaultTemplates();
    }

    public function syncWithFileSystem(string $templatesPath): array
    {
        return $this->managementHandler->syncWithFileSystem($templatesPath);
    }

    public function createBackup(): array
    {
        return $this->managementHandler->createBackup();
    }

    public function restoreFromBackup(string $backupFile): array
    {
        return $this->managementHandler->restoreFromBackup($backupFile);
    }

    public function validateImportData(array $data): array
    {
        return $this->managementHandler->validateImportData($data);
    }

    public function cleanupUnusedTemplates(int $daysUnused = 180): array
    {
        return $this->managementHandler->cleanupUnusedTemplates($daysUnused);
    }

    // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===

    public function clearTemplateCache(NotificationTemplate $template): void
    {
        $this->crudHandler->clearTemplateCache($template);
    }

    public function clearAllTemplateCache(): void
    {
        $this->crudHandler->clearAllTemplateCache();
    }

    public function isNameUnique(string $name, int $excludeId = null): bool
    {
        return $this->crudHandler->isNameUnique($name, $excludeId);
    }

    public function validateTemplateData(array $data, int $excludeId = null): void
    {
        $this->crudHandler->validateTemplateData($data, $excludeId);
    }
}
