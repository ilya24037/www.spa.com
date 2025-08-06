<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\NotificationTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Сервис статистики шаблонов уведомлений
 */
class TemplateStatsService
{
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
            'templates_by_type' => $this->getTemplatesByType(),
            'templates_by_locale' => $this->getTemplatesByLocale(),
            'most_used' => $this->getMostUsedTemplates(),
            'least_used' => $this->getLeastUsedTemplates(),
            'usage_by_month' => $this->getMonthlyUsageStats(),
        ];
    }

    /**
     * Получить распределение шаблонов по типам
     */
    private function getTemplatesByType(): array
    {
        return NotificationTemplate::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Получить распределение шаблонов по локалям
     */
    private function getTemplatesByLocale(): array
    {
        return NotificationTemplate::select('locale', DB::raw('count(*) as count'))
            ->groupBy('locale')
            ->pluck('count', 'locale')
            ->toArray();
    }

    /**
     * Получить наиболее используемые шаблоны
     */
    private function getMostUsedTemplates()
    {
        return NotificationTemplate::withCount('notifications')
            ->orderBy('notifications_count', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'type', 'notifications_count']);
    }

    /**
     * Получить наименее используемые шаблоны
     */
    private function getLeastUsedTemplates()
    {
        return NotificationTemplate::withCount('notifications')
            ->orderBy('notifications_count', 'asc')
            ->limit(10)
            ->get(['id', 'name', 'type', 'notifications_count']);
    }

    /**
     * Получить статистику использования по месяцам
     */
    public function getMonthlyUsageStats(): array
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
}