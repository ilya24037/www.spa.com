<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Domain\Notification\Handlers\TemplateCrudHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис импорта/экспорта шаблонов уведомлений
 */
class TemplateImportExportService
{
    public function __construct(
        private TemplateCrudHandler $crudHandler
    ) {}

    /**
     * Экспорт шаблона
     */
    public function export(NotificationTemplate $template): array
    {
        return $template->export();
    }

    /**
     * Импорт шаблона
     */
    public function import(array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = NotificationTemplate::import($data);
            
            Log::info('Notification template imported', [
                'template_id' => $template->id,
                'name' => $template->name,
            ]);

            return $template;
        });
    }

    /**
     * Массовый экспорт шаблонов
     */
    public function batchExport(array $templateIds): array
    {
        $exported = [];
        
        foreach ($templateIds as $templateId) {
            try {
                $template = NotificationTemplate::find($templateId);
                if ($template) {
                    $exported[] = $this->export($template);
                }
            } catch (\Exception $e) {
                Log::error('Failed to export template', [
                    'template_id' => $templateId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return [
            'exported_count' => count($exported),
            'templates' => $exported,
            'export_date' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * Массовый импорт шаблонов
     */
    public function batchImport(array $templatesData): array
    {
        $imported = [];
        $errors = [];
        
        DB::transaction(function () use ($templatesData, &$imported, &$errors) {
            foreach ($templatesData as $templateData) {
                try {
                    $imported[] = $this->import($templateData);
                } catch (\Exception $e) {
                    $errors[] = [
                        'template_name' => $templateData['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });
        
        return [
            'imported_count' => count($imported),
            'error_count' => count($errors),
            'imported_templates' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Синхронизация шаблонов с файловой системой
     */
    public function syncWithFileSystem(string $templatesPath): array
    {
        $synced = [];
        $errors = [];
        
        if (!is_dir($templatesPath)) {
            throw new \Exception("Templates directory not found: {$templatesPath}");
        }
        
        $files = glob($templatesPath . '/*.json');
        
        foreach ($files as $file) {
            try {
                $data = json_decode(file_get_contents($file), true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON in file: ' . basename($file));
                }
                
                $existing = NotificationTemplate::where('name', $data['name'])->first();
                
                if ($existing) {
                    $template = $this->crudHandler->update($existing, $data);
                    $synced[] = ['action' => 'updated', 'template' => $template];
                } else {
                    $template = $this->crudHandler->create($data);
                    $synced[] = ['action' => 'created', 'template' => $template];
                }
                
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => basename($file),
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return [
            'synced_count' => count($synced),
            'error_count' => count($errors),
            'synced' => $synced,
            'errors' => $errors,
        ];
    }
}