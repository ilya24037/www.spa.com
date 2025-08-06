<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\NotificationTemplate;
use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Обработчик CRUD операций с шаблонами уведомлений
 */
class TemplateCrudHandler
{
    /**
     * Время кеширования шаблонов (секунды)
     */
    protected int $cacheTime = 3600;

    /**
     * Создать новый шаблон
     */
    public function create(array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($data) {
            // Валидация данных
            $this->validateTemplateData($data);
            
            // Создать шаблон
            $template = NotificationTemplate::create([
                'name' => $data['name'],
                'type' => NotificationType::from($data['type']),
                'title' => $data['title'] ?? null,
                'subject' => $data['subject'] ?? null,
                'content' => $data['content'],
                'variables' => $data['variables'] ?? [],
                'channels' => $data['channels'] ?? [],
                'locale' => $data['locale'] ?? 'ru',
                'priority' => $data['priority'] ?? 'medium',
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'is_active' => $data['is_active'] ?? false, // По умолчанию неактивен
            ]);

            // Очистить кеш
            $this->clearTemplateCache($template);

            Log::info('Notification template created', [
                'template_id' => $template->id,
                'name' => $template->name,
                'type' => $template->type->value,
            ]);

            return $template;
        });
    }

    /**
     * Обновить шаблон
     */
    public function update(NotificationTemplate $template, array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            // Валидация данных
            $this->validateTemplateData($data, $template->id);
            
            // Сохранить старую версию для аудита
            $this->createTemplateVersion($template);
            
            // Обновить шаблон
            $template->update([
                'title' => $data['title'] ?? $template->title,
                'subject' => $data['subject'] ?? $template->subject,
                'content' => $data['content'] ?? $template->content,
                'variables' => $data['variables'] ?? $template->variables,
                'channels' => $data['channels'] ?? $template->channels,
                'priority' => $data['priority'] ?? $template->priority,
                'category' => $data['category'] ?? $template->category,
                'description' => $data['description'] ?? $template->description,
                'metadata' => array_merge($template->metadata ?? [], $data['metadata'] ?? []),
            ]);

            // Очистить кеш
            $this->clearTemplateCache($template);

            Log::info('Notification template updated', [
                'template_id' => $template->id,
                'name' => $template->name,
            ]);

            return $template->fresh();
        });
    }

    /**
     * Активировать шаблон
     */
    public function activate(NotificationTemplate $template): bool
    {
        // Проверить валидность шаблона
        $errors = $template->validate();
        
        if (!empty($errors)) {
            throw new \Exception('Template validation failed: ' . implode(', ', $errors));
        }

        $template->activate();
        $this->clearTemplateCache($template);

        Log::info('Notification template activated', [
            'template_id' => $template->id,
            'name' => $template->name,
        ]);

        return true;
    }

    /**
     * Деактивировать шаблон
     */
    public function deactivate(NotificationTemplate $template): bool
    {
        $template->deactivate();
        $this->clearTemplateCache($template);

        Log::info('Notification template deactivated', [
            'template_id' => $template->id,
            'name' => $template->name,
        ]);

        return true;
    }

    /**
     * Клонировать шаблон
     */
    public function duplicate(NotificationTemplate $template, string $newName = null): NotificationTemplate
    {
        $newTemplate = $template->duplicate($newName);
        
        Log::info('Notification template duplicated', [
            'original_id' => $template->id,
            'new_id' => $newTemplate->id,
            'new_name' => $newTemplate->name,
        ]);

        return $newTemplate;
    }

    /**
     * Удалить шаблон
     */
    public function delete(NotificationTemplate $template): bool
    {
        // Проверяем, используется ли шаблон
        $usageCount = $template->notifications()->count();
        
        if ($usageCount > 0) {
            throw new \Exception("Cannot delete template. It is used in {$usageCount} notifications");
        }

        $templateId = $template->id;
        $templateName = $template->name;

        // Очистить кеш
        $this->clearTemplateCache($template);

        // Удалить шаблон
        $template->delete();

        Log::info('Notification template deleted', [
            'template_id' => $templateId,
            'name' => $templateName,
        ]);

        return true;
    }

    /**
     * Массовое обновление шаблонов
     */
    public function batchUpdate(array $templateIds, array $data): int
    {
        $updated = 0;

        foreach ($templateIds as $templateId) {
            try {
                $template = NotificationTemplate::find($templateId);
                if ($template) {
                    $this->update($template, $data);
                    $updated++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to batch update template', [
                    'template_id' => $templateId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    /**
     * Массовая активация/деактивация шаблонов
     */
    public function batchToggleStatus(array $templateIds, bool $isActive): int
    {
        $updated = 0;

        foreach ($templateIds as $templateId) {
            try {
                $template = NotificationTemplate::find($templateId);
                if ($template) {
                    if ($isActive) {
                        $this->activate($template);
                    } else {
                        $this->deactivate($template);
                    }
                    $updated++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to batch toggle template status', [
                    'template_id' => $templateId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    /**
     * Валидация данных шаблона
     */
    public function validateTemplateData(array $data, int $excludeId = null): void
    {
        // Проверить уникальность имени
        $query = NotificationTemplate::where('name', $data['name']);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        if ($query->exists()) {
            throw new \Exception('Template with this name already exists');
        }

        // Проверить обязательные поля
        if (empty($data['content'])) {
            throw new \Exception('Template content is required');
        }

        if (empty($data['channels'])) {
            throw new \Exception('At least one notification channel is required');
        }

        // Валидация переменных
        if (isset($data['variables']) && is_array($data['variables'])) {
            $this->validateVariables($data['variables']);
        }

        // Валидация каналов
        if (isset($data['channels']) && is_array($data['channels'])) {
            $this->validateChannels($data['channels']);
        }
    }

    /**
     * Валидировать переменные шаблона
     */
    protected function validateVariables(array $variables): void
    {
        foreach ($variables as $key => $config) {
            if (!is_string($key) || empty($key)) {
                throw new \Exception('Variable key must be a non-empty string');
            }
            
            if (!is_array($config)) {
                throw new \Exception('Variable configuration must be an array');
            }
        }
    }

    /**
     * Валидировать каналы уведомлений
     */
    protected function validateChannels(array $channels): void
    {
        $validChannels = array_column(NotificationChannel::cases(), 'value');
        
        foreach ($channels as $channel) {
            if (!in_array($channel, $validChannels)) {
                throw new \Exception("Invalid notification channel: {$channel}");
            }
        }
    }

    /**
     * Создать версию шаблона для аудита
     */
    protected function createTemplateVersion(NotificationTemplate $template): void
    {
        // Простая реализация - можно расширить до полноценного версионирования
        $template->update([
            'metadata' => array_merge($template->metadata ?? [], [
                'last_updated_at' => now()->toISOString(),
                'version' => ($template->metadata['version'] ?? 0) + 1,
            ])
        ]);
    }

    /**
     * Очистить кеш шаблона
     */
    public function clearTemplateCache(NotificationTemplate $template): void
    {
        $keys = [
            "notification_template:{$template->name}:{$template->locale}",
            "notification_template_type:{$template->type->value}:{$template->locale}",
            'notification_template_variables',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Очистить весь кеш шаблонов
     */
    public function clearAllTemplateCache(): void
    {
        // Очищаем паттерны кеша
        $patterns = [
            'notification_template:*',
            'notification_template_type:*',
            'notification_template_variables',
        ];

        foreach ($patterns as $pattern) {
            Cache::flush(); // В реальном проекте лучше использовать tagged cache
        }
    }

    /**
     * Проверить уникальность имени шаблона
     */
    public function isNameUnique(string $name, int $excludeId = null): bool
    {
        $query = NotificationTemplate::where('name', $name);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }
}