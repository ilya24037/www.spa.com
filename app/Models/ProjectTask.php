<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'milestone_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'estimated_hours',
        'actual_hours',
        'start_date',
        'due_date',
        'completed_at',
        'progress',
        'order',
        'checklist',
        'tags',
        'dependencies',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'checklist' => 'array',
        'tags' => 'array',
        'dependencies' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Автоматически обновляем прогресс при изменении статуса
        static::updating(function ($task) {
            $oldStatus = $task->getOriginal('status');
            
            if ($task->status !== $oldStatus) {
                switch ($task->status) {
                    case 'todo':
                        $task->progress = 0;
                        $task->completed_at = null;
                        break;
                    case 'in_progress':
                        if ($task->progress === 0) {
                            $task->progress = 25;
                        }
                        if (!$task->start_date) {
                            $task->start_date = now();
                        }
                        break;
                    case 'review':
                        $task->progress = 75;
                        break;
                    case 'done':
                        $task->progress = 100;
                        $task->completed_at = now();
                        break;
                }
            }
        });

        // Логируем создание задачи
        static::created(function ($task) {
            ProjectActivityLog::create([
                'project_id' => $task->project_id,
                'user_id' => $task->created_by,
                'type' => 'task_created',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'description' => "Создана задача: {$task->title}",
                'impact_score' => 1,
            ]);
            
            // Обновляем метрики проекта
            $task->project->updateMetrics();
        });

        // Логируем изменения
        static::updated(function ($task) {
            $changes = [];
            
            if ($task->isDirty('status')) {
                $oldStatus = $task->getOriginal('status');
                $changes[] = "статус с {$oldStatus} на {$task->status}";
                
                if ($task->status === 'done') {
                    ProjectActivityLog::create([
                        'project_id' => $task->project_id,
                        'user_id' => auth()->id(),
                        'type' => 'task_completed',
                        'entity_type' => 'task',
                        'entity_id' => $task->id,
                        'description' => "Завершена задача: {$task->title}",
                        'impact_score' => 5,
                    ]);
                }
            }
            
            if ($task->isDirty('assigned_to')) {
                $assignee = User::find($task->assigned_to);
                $changes[] = "назначена {$assignee->name}";
            }
            
            if (!empty($changes)) {
                ProjectActivityLog::create([
                    'project_id' => $task->project_id,
                    'user_id' => auth()->id(),
                    'type' => 'task_updated',
                    'entity_type' => 'task',
                    'entity_id' => $task->id,
                    'description' => "Обновлена задача {$task->title}: " . implode(', ', $changes),
                    'old_values' => $task->getOriginal(),
                    'new_values' => $task->getAttributes(),
                    'impact_score' => 2,
                ]);
            }
            
            // Обновляем метрики проекта
            $task->project->updateMetrics();
        });
    }

    // Отношения
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Методы
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['done', 'blocked']);
    }

    public function getDaysRemaining(): ?int
    {
        if (!$this->due_date || $this->status === 'done') {
            return null;
        }
        
        return now()->diffInDays($this->due_date, false);
    }

    public function updateProgress(int $progress): void
    {
        $this->progress = max(0, min(100, $progress));
        
        // Автоматически обновляем статус на основе прогресса
        if ($progress === 0 && $this->status !== 'blocked') {
            $this->status = 'todo';
        } elseif ($progress > 0 && $progress < 100) {
            if ($this->status === 'todo') {
                $this->status = 'in_progress';
            }
        } elseif ($progress === 100) {
            $this->status = 'done';
        }
        
        $this->save();
    }

    public function updateChecklist(array $checklist): void
    {
        $this->checklist = $checklist;
        
        // Подсчитываем прогресс на основе чеклиста
        $total = count($checklist);
        $completed = collect($checklist)->where('completed', true)->count();
        
        if ($total > 0) {
            $this->updateProgress(round(($completed / $total) * 100));
        }
        
        $this->save();
    }

    public function canStart(): bool
    {
        if (empty($this->dependencies)) {
            return true;
        }
        
        // Проверяем, все ли зависимые задачи выполнены
        $dependentTasks = self::whereIn('id', $this->dependencies)->get();
        
        return $dependentTasks->every(function ($task) {
            return $task->status === 'done';
        });
    }

    public function logTimeSpent(int $hours, ?string $description = null): void
    {
        $this->actual_hours += $hours;
        $this->save();
        
        ProjectActivityLog::create([
            'project_id' => $this->project_id,
            'user_id' => auth()->id(),
            'type' => 'time_logged',
            'entity_type' => 'task',
            'entity_id' => $this->id,
            'description' => "Записано {$hours} часов на задачу: {$this->title}" . ($description ? " - {$description}" : ''),
            'impact_score' => 1,
        ]);
    }
}