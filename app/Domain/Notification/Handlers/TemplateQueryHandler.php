<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Обработчик запросов и поиска шаблонов уведомлений
 */
class TemplateQueryHandler
{
    /**
     * Время кеширования шаблонов (секунды)
     */
    protected int $cacheTime = 3600;

    /**
     * Получить шаблон по имени и локали
     */
    public function getTemplate(string $name, string $locale = 'ru'): ?NotificationTemplate
    {
        $cacheKey = "notification_template:{$name}:{$locale}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($name, $locale) {
            return NotificationTemplate::where('name', $name)
                ->where('locale', $locale)
                ->active()
                ->first();
        });
    }

    /**
     * Получить шаблон по типу уведомления
     */
    public function getTemplateByType(NotificationType $type, string $locale = 'ru'): ?NotificationTemplate
    {
        $cacheKey = "notification_template_type:{$type->value}:{$locale}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($type, $locale) {
            return NotificationTemplate::where('type', $type)
                ->where('locale', $locale)
                ->active()
                ->orderBy('priority', 'desc')
                ->first();
        });
    }

    /**
     * Получить все шаблоны с фильтрацией
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = NotificationTemplate::query();

        // Фильтры
        if (!empty($filters['type'])) {
            $query->byType(NotificationType::from($filters['type']));
        }

        if (!empty($filters['locale'])) {
            $query->byLocale($filters['locale']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['active'])) {
            $query->active();
        }

        if (!empty($filters['search'])) {
            $query->searchByName($filters['search']);
        }

        if (!empty($filters['channels'])) {
            $query->whereJsonContains('channels', $filters['channels']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Получить шаблоны по каналу
     */
    public function getByChannel(NotificationChannel $channel, string $locale = 'ru'): Collection
    {
        return NotificationTemplate::byChannel($channel)
            ->byLocale($locale)
            ->active()
            ->get();
    }

    /**
     * Получить доступные шаблоны для пользователя
     */
    public function getAvailableTemplates(string $locale = 'ru'): array
    {
        $cacheKey = "available_templates:{$locale}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($locale) {
            return NotificationTemplate::where('locale', $locale)
                ->active()
                ->select('id', 'name', 'type', 'title', 'category', 'description')
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->groupBy('category')
                ->toArray();
        });
    }

    /**
     * Получить переменные из всех шаблонов
     */
    public function getAllVariables(): array
    {
        $cacheKey = 'notification_template_variables';
        
        return Cache::remember($cacheKey, $this->cacheTime, function () {
            $variables = [];
            
            NotificationTemplate::active()
                ->whereNotNull('variables')
                ->get(['variables'])
                ->each(function ($template) use (&$variables) {
                    foreach ($template->variables ?? [] as $key => $config) {
                        if (!isset($variables[$key])) {
                            $variables[$key] = $config;
                        }
                    }
                });

            return $variables;
        });
    }

    /**
     * Поиск шаблонов по содержимому
     */
    public function search(string $query, array $filters = []): Collection
    {
        $searchQuery = NotificationTemplate::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('title', 'like', "%{$query}%")
                  ->orWhere('subject', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });

        // Применяем дополнительные фильтры
        if (!empty($filters['type'])) {
            $searchQuery->byType(NotificationType::from($filters['type']));
        }

        if (!empty($filters['locale'])) {
            $searchQuery->byLocale($filters['locale']);
        }

        if (!empty($filters['active'])) {
            $searchQuery->active();
        }

        return $searchQuery->orderBy('name')->get();
    }

    /**
     * Получить шаблоны по категории
     */
    public function getByCategory(string $category, string $locale = 'ru'): Collection
    {
        return NotificationTemplate::byCategory($category)
            ->byLocale($locale)
            ->active()
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Получить популярные шаблоны
     */
    public function getPopularTemplates(int $limit = 10): Collection
    {
        return NotificationTemplate::withCount('notifications')
            ->active()
            ->orderBy('notifications_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить недавно используемые шаблоны
     */
    public function getRecentlyUsed(int $days = 30, int $limit = 10): Collection
    {
        return NotificationTemplate::whereHas('notifications', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->withCount(['notifications' => function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            }])
            ->orderBy('notifications_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить неиспользуемые шаблоны
     */
    public function getUnusedTemplates(int $days = 90): Collection
    {
        return NotificationTemplate::whereDoesntHave('notifications', function ($query) use ($days) {
                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->active()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Получить шаблоны с ошибками
     */
    public function getTemplatesWithErrors(): Collection
    {
        $templates = NotificationTemplate::active()->get();
        $templatesWithErrors = [];

        foreach ($templates as $template) {
            $errors = $template->validate();
            if (!empty($errors)) {
                $template->validation_errors = $errors;
                $templatesWithErrors[] = $template;
            }
        }

        return collect($templatesWithErrors);
    }

    /**
     * Получить дубликаты шаблонов
     */
    public function getDuplicateTemplates(): array
    {
        $duplicates = NotificationTemplate::select('name', 'type', 'locale')
            ->groupBy('name', 'type', 'locale')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $result = [];

        foreach ($duplicates as $duplicate) {
            $templates = NotificationTemplate::where('name', $duplicate->name)
                ->where('type', $duplicate->type)
                ->where('locale', $duplicate->locale)
                ->get();

            $result[] = [
                'name' => $duplicate->name,
                'type' => $duplicate->type,
                'locale' => $duplicate->locale,
                'templates' => $templates,
                'count' => $templates->count(),
            ];
        }

        return $result;
    }

    /**
     * Получить статистику шаблонов
     */
    public function getTemplateStats(): array
    {
        $cacheKey = 'template_stats';
        
        return Cache::remember($cacheKey, 1800, function () { // 30 минут
            return [
                'total' => NotificationTemplate::count(),
                'active' => NotificationTemplate::active()->count(),
                'inactive' => NotificationTemplate::inactive()->count(),
                'by_type' => NotificationTemplate::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'by_locale' => NotificationTemplate::selectRaw('locale, COUNT(*) as count')
                    ->groupBy('locale')
                    ->pluck('count', 'locale')
                    ->toArray(),
                'by_category' => NotificationTemplate::selectRaw('category, COUNT(*) as count')
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
            ];
        });
    }

    /**
     * Найти похожие шаблоны
     */
    public function findSimilarTemplates(NotificationTemplate $template, int $limit = 5): Collection
    {
        return NotificationTemplate::where('id', '!=', $template->id)
            ->where(function ($query) use ($template) {
                $query->where('type', $template->type)
                      ->orWhere('category', $template->category)
                      ->orWhere('locale', $template->locale);
            })
            ->active()
            ->limit($limit)
            ->get();
    }

    /**
     * Получить шаблоны для экспорта
     */
    public function getTemplatesForExport(array $templateIds): Collection
    {
        return NotificationTemplate::whereIn('id', $templateIds)
            ->with(['notifications' => function ($query) {
                $query->select('id', 'notification_template_id', 'created_at')
                      ->latest()
                      ->limit(1);
            }])
            ->get();
    }

    /**
     * Проверить существование шаблона
     */
    public function exists(string $name, string $locale = 'ru'): bool
    {
        return NotificationTemplate::where('name', $name)
            ->where('locale', $locale)
            ->exists();
    }

    /**
     * Получить количество активных шаблонов по типу
     */
    public function getActiveCountByType(NotificationType $type): int
    {
        return NotificationTemplate::byType($type)
            ->active()
            ->count();
    }
}