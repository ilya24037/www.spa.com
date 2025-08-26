<?php

namespace App\Domain\Notification\Models;

use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель шаблонов уведомлений
 */
class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'name',
        'type',
        'title',
        'subject',
        'content',
        'variables',
        'channels',
        'locale',
        'is_active',
        'priority',
        'category',
        'description',
        'metadata',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'variables',
        'channels',
        'metadata',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'is_active' => 'boolean',
        // JSON поля обрабатываются через JsonFieldsTrait
    ];

    protected $attributes = [
        'is_active' => true,
        'priority' => 'medium',
        'locale' => 'ru',
    ];

    /**
     * Связь с уведомлениями
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'template', 'name');
    }

    /**
     * Скоупы для запросов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByType($query, NotificationType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeByChannel($query, NotificationChannel $channel)
    {
        return $query->whereJsonContains('channels', $channel->value);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeSearchByName($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
    }

    /**
     * Проверки состояния
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function supportsChannel(NotificationChannel $channel): bool
    {
        return in_array($channel->value, $this->channels ?? []);
    }

    public function hasVariable(string $variable): bool
    {
        return in_array($variable, array_keys($this->variables ?? []));
    }

    /**
     * @deprecated Используйте TemplateRenderingService::getRequiredVariables()
     */
    public function getRequiredVariables(): array
    {
        $renderingService = app(\App\Domain\Notification\Services\TemplateRenderingService::class);
        return $renderingService->getRequiredVariables($this);
    }

    /**
     * @deprecated Используйте TemplateRenderingService::getOptionalVariables()
     */
    public function getOptionalVariables(): array
    {
        $renderingService = app(\App\Domain\Notification\Services\TemplateRenderingService::class);
        return $renderingService->getOptionalVariables($this);
    }

    /**
     * Обработка шаблона
     * @deprecated Используйте TemplateRenderingService::render()
     */
    public function render(array $data = []): array
    {
        $renderingService = app(\App\Domain\Notification\Services\TemplateRenderingService::class);
        return $renderingService->render($this, $data);
    }

    /**
     * Валидация шаблона
     * @deprecated Используйте TemplateValidationService::validate()
     */
    public function validate(): array
    {
        $validationService = app(\App\Domain\Notification\Services\TemplateValidationService::class);
        return $validationService->validate($this);
    }

    /**
     * Извлечение переменных из контента
     * @deprecated Используйте TemplateRenderingService::extractVariables()
     */
    private function extractVariablesFromContent(): array
    {
        $renderingService = app(\App\Domain\Notification\Services\TemplateRenderingService::class);
        $content = $this->title . ' ' . $this->subject . ' ' . $this->content;
        return $renderingService->extractVariables($content);
    }

    /**
     * Клонирование шаблона
     */
    public function duplicate(string $newName = null): self
    {
        $newTemplate = $this->replicate();
        $newTemplate->name = $newName ?: $this->name . '_copy';
        $newTemplate->is_active = false; // Копия неактивна по умолчанию
        $newTemplate->save();
        
        return $newTemplate;
    }

    /**
     * Активация/деактивация
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Статистика использования
     */
    public function getUsageStats(): array
    {
        $notifications = $this->notifications();
        
        return [
            'total_sent' => $notifications->count(),
            'sent_today' => $notifications->whereDate('created_at', today())->count(),
            'sent_this_week' => $notifications->where('created_at', '>=', now()->startOfWeek())->count(),
            'sent_this_month' => $notifications->where('created_at', '>=', now()->startOfMonth())->count(),
            'success_rate' => $this->calculateSuccessRate(),
            'avg_delivery_time' => $this->calculateAverageDeliveryTime(),
        ];
    }

    private function calculateSuccessRate(): float
    {
        $total = $this->notifications()->count();
        
        if ($total === 0) {
            return 0.0;
        }
        
        $delivered = $this->notifications()
            ->where('status', \App\Enums\NotificationStatus::DELIVERED)
            ->count();
            
        return round(($delivered / $total) * 100, 2);
    }

    private function calculateAverageDeliveryTime(): ?float
    {
        $deliveredNotifications = $this->notifications()
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at')
            ->get();
            
        if ($deliveredNotifications->isEmpty()) {
            return null;
        }
        
        $totalTime = $deliveredNotifications->sum(function($notification) {
            return $notification->delivered_at->diffInSeconds($notification->created_at);
        });
        
        return round($totalTime / $deliveredNotifications->count(), 2);
    }

    /**
     * Экспорт/импорт шаблонов
     */
    public function export(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'title' => $this->title,
            'subject' => $this->subject,
            'content' => $this->content,
            'variables' => $this->variables,
            'channels' => $this->channels,
            'locale' => $this->locale,
            'priority' => $this->priority,
            'category' => $this->category,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }

    public static function import(array $data): self
    {
        // Валидация импортируемых данных
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Template name is required for import');
        }

        // Создание шаблона
        $template = new static();
        $template->fill([
            'name' => $data['name'],
            'type' => NotificationType::from($data['type']),
            'title' => $data['title'] ?? null,
            'subject' => $data['subject'] ?? null,
            'content' => $data['content'] ?? null,
            'variables' => $data['variables'] ?? [],
            'channels' => $data['channels'] ?? [],
            'locale' => $data['locale'] ?? 'ru',
            'priority' => $data['priority'] ?? 'medium',
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'is_active' => false, // Импортированные неактивны по умолчанию
        ]);

        $template->save();
        return $template;
    }

    /**
     * Поиск шаблона по имени и типу
     */
    public static function findByNameAndType(string $name, NotificationType $type): ?self
    {
        return static::where('name', $name)
                    ->where('type', $type)
                    ->active()
                    ->first();
    }

    /**
     * Получить шаблон по умолчанию для типа
     */
    public static function getDefaultForType(NotificationType $type, string $locale = 'ru'): ?self
    {
        return static::where('type', $type)
                    ->where('locale', $locale)
                    ->where('name', 'like', 'default_%')
                    ->active()
                    ->first();
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'required_variables' => $this->getRequiredVariables(),
            'optional_variables' => $this->getOptionalVariables(),
            'supported_channels' => $this->channels,
            'usage_stats' => $this->getUsageStats(),
            'validation_errors' => $this->validate(),
        ]);
    }
}