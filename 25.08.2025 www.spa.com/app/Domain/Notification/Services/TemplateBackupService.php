<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Сервис резервного копирования шаблонов
 */
class TemplateBackupService
{
    public function __construct(
        private TemplateImportExportService $importExportService
    ) {}

    /**
     * Создать резервную копию всех шаблонов
     */
    public function createBackup(): array
    {
        $templates = NotificationTemplate::all();
        $backup = [];
        
        foreach ($templates as $template) {
            $backup[] = $this->importExportService->export($template);
        }
        
        $backupData = [
            'version' => '1.0',
            'created_at' => now()->toISOString(),
            'templates_count' => count($backup),
            'templates' => $backup,
        ];
        
        $filename = $this->generateBackupFilename();
        $path = $this->getBackupPath($filename);
        
        $this->ensureBackupDirectoryExists($path);
        $this->saveBackupFile($path, $backupData);
        
        $this->logBackupCreated($filename, count($backup));
        
        return [
            'filename' => $filename,
            'path' => $path,
            'templates_count' => count($backup),
            'size' => filesize($path),
        ];
    }

    /**
     * Восстановить из резервной копии
     */
    public function restoreFromBackup(string $backupFile): array
    {
        if (!file_exists($backupFile)) {
            throw new \Exception("Backup file not found: {$backupFile}");
        }
        
        $backupData = json_decode(file_get_contents($backupFile), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid backup file format');
        }
        
        return $this->importExportService->batchImport($backupData['templates'] ?? []);
    }

    /**
     * Генерация имени файла резервной копии
     */
    private function generateBackupFilename(): string
    {
        return 'template_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
    }

    /**
     * Получение пути к файлу резервной копии
     */
    private function getBackupPath(string $filename): string
    {
        return storage_path('app/backups/' . $filename);
    }

    /**
     * Обеспечение существования директории резервных копий
     */
    private function ensureBackupDirectoryExists(string $path): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
    }

    /**
     * Сохранение файла резервной копии
     */
    private function saveBackupFile(string $path, array $backupData): void
    {
        file_put_contents($path, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Логирование создания резервной копии
     */
    private function logBackupCreated(string $filename, int $templateCount): void
    {
        Log::info('Templates backup created', [
            'filename' => $filename,
            'templates_count' => $templateCount,
        ]);
    }
}