<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обслуживания и очистки шаблонов
 */
class TemplateMaintenanceService
{
    /**
     * Очистка неиспользуемых шаблонов
     */
    public function cleanupUnusedTemplates(int $daysUnused = 180): array
    {
        $cutoffDate = now()->subDays($daysUnused);
        
        $unusedTemplates = $this->findUnusedTemplates($cutoffDate);
        $deleted = $this->deleteUnusedTemplates($unusedTemplates);
        
        $this->logCleanupCompleted($deleted, $cutoffDate);
        
        return [
            'deleted_count' => count($deleted),
            'deleted_templates' => $deleted,
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
        ];
    }

    /**
     * Найти неиспользуемые шаблоны
     */
    private function findUnusedTemplates($cutoffDate)
    {
        return NotificationTemplate::whereDoesntHave('notifications', function ($query) use ($cutoffDate) {
                $query->where('created_at', '>=', $cutoffDate);
            })
            ->where('created_at', '<', $cutoffDate)
            ->get();
    }

    /**
     * Удалить неиспользуемые шаблоны
     */
    private function deleteUnusedTemplates($unusedTemplates): array
    {
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
        
        return $deleted;
    }

    /**
     * Логирование завершения очистки
     */
    private function logCleanupCompleted(array $deleted, $cutoffDate): void
    {
        Log::info('Unused templates cleanup completed', [
            'deleted_count' => count($deleted),
            'cutoff_date' => $cutoffDate->format('Y-m-d'),
        ]);
    }
}