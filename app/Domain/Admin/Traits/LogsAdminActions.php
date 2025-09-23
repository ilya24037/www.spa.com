<?php

namespace App\Domain\Admin\Traits;

use App\Domain\Admin\Models\AdminLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Trait для логирования действий администраторов
 * Следует принципам DRY и SOLID
 */
trait LogsAdminActions
{
    /**
     * Логировать действие администратора
     *
     * @param string $action Действие (approve, reject, block, etc.)
     * @param Model|null $model Модель, над которой выполнено действие
     * @param array $oldValues Старые значения (для отслеживания изменений)
     * @param array $newValues Новые значения
     * @param string|null $description Описание действия
     * @param array $metadata Дополнительные данные
     * @return AdminLog
     */
    protected function logAdminAction(
        string $action,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        array $metadata = []
    ): AdminLog {
        $user = Auth::user();

        // Добавляем информацию о браузере и времени
        $metadata = array_merge($metadata, [
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);

        // Автоматическое формирование описания, если не предоставлено
        if (!$description && $model) {
            $modelName = class_basename($model);
            $modelId = $model->getKey();
            $description = $this->generateDescription($action, $modelName, $modelId);
        }

        return AdminLog::create([
            'admin_id' => $user->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'metadata' => $metadata,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Логировать массовое действие
     *
     * @param string $action Действие (bulk_approve, bulk_reject, etc.)
     * @param array $modelIds ID моделей
     * @param string $modelType Тип модели (полное имя класса)
     * @param string|null $description Описание
     * @return AdminLog
     */
    protected function logBulkAction(
        string $action,
        array $modelIds,
        string $modelType,
        ?string $description = null
    ): AdminLog {
        $count = count($modelIds);

        if (!$description) {
            $modelName = class_basename($modelType);
            $description = "Выполнено массовое действие '{$action}' над {$count} объектами типа {$modelName}";
        }

        return $this->logAdminAction(
            $action,
            null,
            [],
            [],
            $description,
            [
                'model_type' => $modelType,
                'model_ids' => $modelIds,
                'count' => $count,
            ]
        );
    }

    /**
     * Логировать изменение модели
     *
     * @param Model $model Модель
     * @param array $oldValues Старые значения
     * @param array $newValues Новые значения
     * @return AdminLog
     */
    protected function logModelEdit(Model $model, array $oldValues, array $newValues): AdminLog
    {
        // Фильтруем только измененные поля
        $changes = [];
        foreach ($newValues as $key => $newValue) {
            if (!isset($oldValues[$key]) || $oldValues[$key] !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValues[$key] ?? null,
                    'new' => $newValue,
                ];
            }
        }

        return $this->logAdminAction(
            'edit',
            $model,
            $oldValues,
            $newValues,
            null,
            ['changes' => $changes]
        );
    }

    /**
     * Генерация описания действия
     *
     * @param string $action
     * @param string $modelName
     * @param mixed $modelId
     * @return string
     */
    private function generateDescription(string $action, string $modelName, $modelId): string
    {
        $actionDescriptions = [
            'approve' => 'одобрено',
            'reject' => 'отклонено',
            'block' => 'заблокировано',
            'unblock' => 'разблокировано',
            'delete' => 'удалено',
            'restore' => 'восстановлено',
            'archive' => 'архивировано',
            'edit' => 'отредактировано',
        ];

        $actionText = $actionDescriptions[$action] ?? $action;

        return "{$modelName} #{$modelId} {$actionText}";
    }

    /**
     * Получить последние действия текущего администратора
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getMyRecentActions(int $limit = 10)
    {
        return AdminLog::byAdmin(Auth::id())
            ->with('admin')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Получить статистику действий администратора
     *
     * @param int|null $adminId
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @return array
     */
    protected function getAdminStatistics(?int $adminId = null, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $adminId = $adminId ?? Auth::id();
        $from = $from ?? now()->subMonth();
        $to = $to ?? now();

        $query = AdminLog::byAdmin($adminId)->inPeriod($from, $to);

        return [
            'total_actions' => $query->count(),
            'approvals' => (clone $query)->where('action', 'approve')->count(),
            'rejections' => (clone $query)->where('action', 'reject')->count(),
            'blocks' => (clone $query)->where('action', 'block')->count(),
            'edits' => (clone $query)->where('action', 'edit')->count(),
            'critical_actions' => (clone $query)->critical()->count(),
        ];
    }
}