<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик управления шаблонами (импорт/экспорт, статистика, создание по умолчанию)
 */
class TemplateManagementHandler
{
    public function __construct(
        protected TemplateCrudHandler $crudHandler
    ) {}

    /**
     * Экспортировать шаблон
     */
    public function export(NotificationTemplate $template): array
    {
        return $template->export();
    }

    /**
     * Импортировать шаблон
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
     * Получить статистику использования шаблонов
     */
    public function getUsageStats(NotificationTemplate $template = null): array
    {
        if ($template) {
            return $template->getUsageStats();
        }

        // Статистика по всем шаблонам
        return [
            'total_templates' => NotificationTemplate::count(),
            'active_templates' => NotificationTemplate::active()->count(),
            'inactive_templates' => NotificationTemplate::inactive()->count(),
            'templates_by_type' => NotificationTemplate::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'templates_by_locale' => NotificationTemplate::select('locale', DB::raw('count(*) as count'))
                ->groupBy('locale')
                ->pluck('count', 'locale')
                ->toArray(),
            'most_used' => NotificationTemplate::withCount('notifications')
                ->orderBy('notifications_count', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'type', 'notifications_count']),
            'least_used' => NotificationTemplate::withCount('notifications')
                ->orderBy('notifications_count', 'asc')
                ->limit(10)
                ->get(['id', 'name', 'type', 'notifications_count']),
            'usage_by_month' => $this->getMonthlyUsageStats(),
        ];
    }

    /**
     * Создать шаблоны по умолчанию
     */
    public function createDefaultTemplates(): array
    {
        $templates = [];
        
        $defaultTemplates = [
            [
                'name' => 'default_welcome',
                'type' => NotificationType::USER_WELCOME,
                'title' => 'Добро пожаловать, {{user_name}}!',
                'subject' => 'Добро пожаловать на SPA Platform',
                'content' => 'Здравствуйте, {{user_name}}! Рады видеть вас на нашей платформе.',
                'variables' => [
                    'user_name' => ['required' => true, 'description' => 'Имя пользователя'],
                ],
                'channels' => [NotificationChannel::EMAIL->value],
                'category' => 'auth',
            ],
            [
                'name' => 'default_booking_confirmed',
                'type' => NotificationType::BOOKING_CONFIRMED,
                'title' => 'Бронирование подтверждено',
                'subject' => 'Ваше бронирование №{{booking_number}} подтверждено',
                'content' => 'Ваше бронирование на {{service_name}} у мастера {{master_name}} подтверждено на {{booking_date}}.',
                'variables' => [
                    'booking_number' => ['required' => true],
                    'service_name' => ['required' => true],
                    'master_name' => ['required' => true],
                    'booking_date' => ['required' => true],
                ],
                'channels' => [NotificationChannel::EMAIL->value, NotificationChannel::PUSH->value],
                'category' => 'booking',
            ],
            [
                'name' => 'default_payment_completed',
                'type' => NotificationType::PAYMENT_COMPLETED,
                'title' => 'Платеж выполнен',
                'subject' => 'Платеж на сумму {{amount}} выполнен успешно',
                'content' => 'Ваш платеж на сумму {{amount}} выполнен успешно. Номер операции: {{transaction_id}}.',
                'variables' => [
                    'amount' => ['required' => true],
                    'transaction_id' => ['required' => true],
                ],
                'channels' => [NotificationChannel::EMAIL->value, NotificationChannel::SMS->value],
                'category' => 'payment',
            ],
            [
                'name' => 'default_booking_reminder',
                'type' => NotificationType::BOOKING_REMINDER,
                'title' => 'Напоминание о бронировании',
                'subject' => 'Напоминание: через час ваш сеанс {{service_name}}',
                'content' => 'Напоминаем, что через час у вас сеанс {{service_name}} у мастера {{master_name}}.',
                'variables' => [
                    'service_name' => ['required' => true],
                    'master_name' => ['required' => true],
                    'booking_time' => ['required' => false],
                ],
                'channels' => [NotificationChannel::PUSH->value, NotificationChannel::SMS->value],
                'category' => 'booking',
            ],
            [
                'name' => 'default_review_request',
                'type' => NotificationType::REVIEW_REQUEST,
                'title' => 'Оцените услугу',
                'subject' => 'Поделитесь впечатлениями о сеансе {{service_name}}',
                'content' => 'Как прошел ваш сеанс {{service_name}} у мастера {{master_name}}? Поделитесь впечатлениями!',
                'variables' => [
                    'service_name' => ['required' => true],
                    'master_name' => ['required' => true],
                    'review_url' => ['required' => false],
                ],
                'channels' => [NotificationChannel::EMAIL->value],
                'category' => 'review',
            ],
        ];

        foreach ($defaultTemplates as $templateData) {
            try {
                $templates[] = $this->crudHandler->create($templateData);
            } catch (\Exception $e) {
                Log::error('Failed to create default template', [
                    'template_name' => $templateData['name'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $templates;
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
                
                // Проверяем, существует ли шаблон
                $existing = NotificationTemplate::where('name', $data['name'])->first();
                
                if ($existing) {
                    // Обновляем существующий
                    $template = $this->crudHandler->update($existing, $data);
                    $synced[] = ['action' => 'updated', 'template' => $template];
                } else {
                    // Создаем новый
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

    /**
     * Создать резервную копию всех шаблонов
     */
    public function createBackup(): array
    {
        $templates = NotificationTemplate::all();
        $backup = [];
        
        foreach ($templates as $template) {
            $backup[] = $this->export($template);
        }
        
        $backupData = [
            'version' => '1.0',
            'created_at' => now()->toISOString(),
            'templates_count' => count($backup),
            'templates' => $backup,
        ];
        
        // Сохраняем в storage
        $filename = 'template_backup_' . now()->format('Y_m_d_H_i_s') . '.json';
        $path = storage_path('app/backups/' . $filename);
        
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        file_put_contents($path, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        Log::info('Templates backup created', [
            'filename' => $filename,
            'templates_count' => count($backup),
        ]);
        
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
        
        return $this->batchImport($backupData['templates'] ?? []);
    }

    /**
     * Получить статистику использования по месяцам
     */
    protected function getMonthlyUsageStats(): array
    {
        return DB::table('notifications')
            ->join('notification_templates', 'notifications.notification_template_id', '=', 'notification_templates.id')
            ->selectRaw('
                DATE_FORMAT(notifications.created_at, "%Y-%m") as month,
                notification_templates.type,
                COUNT(*) as usage_count
            ')
            ->where('notifications.created_at', '>=', now()->subMonths(12))
            ->groupBy('month', 'notification_templates.type')
            ->orderBy('month', 'desc')
            ->get()
            ->groupBy('month')
            ->toArray();
    }

    /**
     * Валидировать структуру данных импорта
     */
    public function validateImportData(array $data): array
    {
        $errors = [];
        
        $requiredFields = ['name', 'type', 'content'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        // Валидация типа уведомления
        if (isset($data['type'])) {
            try {
                NotificationType::from($data['type']);
            } catch (\ValueError $e) {
                $errors[] = "Invalid notification type: {$data['type']}";
            }
        }
        
        // Валидация каналов
        if (isset($data['channels']) && is_array($data['channels'])) {
            $validChannels = array_column(NotificationChannel::cases(), 'value');
            
            foreach ($data['channels'] as $channel) {
                if (!in_array($channel, $validChannels)) {
                    $errors[] = "Invalid notification channel: {$channel}";
                }
            }
        }
        
        return $errors;
    }

    /**
     * Очистка неиспользуемых шаблонов
     */
    public function cleanupUnusedTemplates(int $daysUnused = 180): array
    {
        $cutoffDate = now()->subDays($daysUnused);
        
        $unusedTemplates = NotificationTemplate::whereDoesntHave('notifications', function ($query) use ($cutoffDate) {
                $query->where('created_at', '>=', $cutoffDate);
            })
            ->where('created_at', '<', $cutoffDate)
            ->get();
        
        $deleted = [];
        
        foreach ($unusedTemplates as $template) {
            try {
                $deleted[] = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'type' => $template->type->value,
                ];
                
                $template->delete();
            } catch (\Exception $e) {
                Log::error('Failed to delete unused template', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Unused templates cleanup completed', [
            'deleted_count' => count($deleted),
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
        ]);
        
        return [
            'deleted_count' => count($deleted),
            'deleted_templates' => $deleted,
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
        ];
    }
}