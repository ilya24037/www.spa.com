<?php

namespace App\Domain\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\User\Models\User;

/**
 * Модель для логирования действий администраторов
 * Следует принципам SOLID и DDD архитектуре
 */
class AdminLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'metadata',
        'description',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Администратор, выполнивший действие
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Получить модель, над которой было выполнено действие
     */
    public function getTargetModel()
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }

        $modelClass = $this->model_type;
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($this->model_id);
    }

    /**
     * Форматированное описание действия
     */
    public function getFormattedActionAttribute(): string
    {
        $actions = [
            'approve' => '✅ Одобрил',
            'reject' => '❌ Отклонил',
            'block' => '🚫 Заблокировал',
            'unblock' => '🔓 Разблокировал',
            'edit' => '✏️ Редактировал',
            'delete' => '🗑️ Удалил',
            'bulk_approve' => '✅ Массово одобрил',
            'bulk_reject' => '❌ Массово отклонил',
            'bulk_block' => '🚫 Массово заблокировал',
            'bulk_archive' => '📦 Массово архивировал',
            'bulk_delete' => '🗑️ Массово удалил',
            'restore' => '♻️ Восстановил',
            'archive' => '📦 Архивировал',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Проверка: критическое ли действие
     */
    public function isCritical(): bool
    {
        $criticalActions = ['delete', 'bulk_delete', 'block', 'bulk_block'];
        return in_array($this->action, $criticalActions);
    }

    /**
     * Scope для фильтрации по администратору
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope для фильтрации по действию
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope для фильтрации по модели
     */
    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query->where('model_type', $modelType);

        if ($modelId) {
            $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope для критических действий
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('action', ['delete', 'bulk_delete', 'block', 'bulk_block']);
    }

    /**
     * Scope для действий за период
     */
    public function scopeInPeriod($query, $from, $to = null)
    {
        $query->where('created_at', '>=', $from);

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}