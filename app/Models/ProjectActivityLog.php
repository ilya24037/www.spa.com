<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'impact_score',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // Отключаем updated_at, так как логи не обновляются
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    // Отношения
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Методы
    public function getEntityAttribute()
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        $modelClass = 'App\\Models\\' . ucfirst($this->entity_type);
        
        if (class_exists($modelClass)) {
            return $modelClass::find($this->entity_id);
        }

        return null;
    }

    // Типы активности
    const TYPES = [
        'task_created' => 'Создана задача',
        'task_updated' => 'Обновлена задача',
        'task_completed' => 'Завершена задача',
        'task_overdue' => 'Задача просрочена',
        'milestone_completed' => 'Достигнут этап',
        'critical_milestone_completed' => 'Достигнут критический этап',
        'milestone_delayed' => 'Этап задержан',
        'member_added' => 'Добавлен участник',
        'member_removed' => 'Удален участник',
        'time_logged' => 'Записано время',
        'critical_health' => 'Критическое здоровье проекта',
        'budget_exceeded' => 'Превышен бюджет',
        'schedule_behind' => 'Отставание от графика',
    ];

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    // Цвета для типов активности
    public function getTypeColor(): string
    {
        $colors = [
            'task_created' => 'blue',
            'task_completed' => 'green',
            'task_updated' => 'yellow',
            'task_overdue' => 'orange',
            'milestone_completed' => 'purple',
            'critical_milestone_completed' => 'red',
            'milestone_delayed' => 'orange',
            'member_added' => 'indigo',
            'member_removed' => 'gray',
            'time_logged' => 'gray',
            'critical_health' => 'red',
            'budget_exceeded' => 'red',
            'schedule_behind' => 'orange',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    // Scopes
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeHighImpact($query, $minScore = 5)
    {
        return $query->where('impact_score', '>=', $minScore);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}