<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'due_date',
        'completed_date',
        'status',
        'weight',
        'order',
        'is_critical',
        'deliverables',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
        'is_critical' => 'boolean',
        'deliverables' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Автоматически обновляем статус
        static::creating(function ($milestone) {
            if ($milestone->due_date->isPast()) {
                $milestone->status = 'delayed';
            }
        });

        static::updating(function ($milestone) {
            $oldStatus = $milestone->getOriginal('status');
            
            // Если этап завершен
            if ($milestone->status === 'completed' && $oldStatus !== 'completed') {
                $milestone->completed_date = now();
                
                // Логируем достижение этапа
                ProjectActivityLog::create([
                    'project_id' => $milestone->project_id,
                    'user_id' => auth()->id(),
                    'type' => 'milestone_completed',
                    'entity_type' => 'milestone',
                    'entity_id' => $milestone->id,
                    'description' => "Достигнут этап: {$milestone->name}",
                    'impact_score' => 10,
                ]);
                
                // Если это критический этап, увеличиваем impact
                if ($milestone->is_critical) {
                    ProjectActivityLog::create([
                        'project_id' => $milestone->project_id,
                        'user_id' => auth()->id(),
                        'type' => 'critical_milestone_completed',
                        'entity_type' => 'milestone',
                        'entity_id' => $milestone->id,
                        'description' => "Достигнут критический этап: {$milestone->name}",
                        'impact_score' => 20,
                    ]);
                }
            }
        });

        static::saved(function ($milestone) {
            // Обновляем прогресс проекта
            $milestone->project->updateMetrics();
        });
    }

    // Отношения
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'milestone_id');
    }

    // Методы
    public function updateStatus(): void
    {
        if ($this->completed_date) {
            $this->status = 'completed';
        } elseif ($this->tasks()->where('status', 'in_progress')->exists()) {
            $this->status = 'in_progress';
        } elseif ($this->due_date->isPast()) {
            $this->status = 'delayed';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }

    public function getProgress(): int
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }
        
        $completedTasks = $this->tasks()->where('status', 'done')->count();
        
        return round(($completedTasks / $totalTasks) * 100);
    }

    public function isDelayed(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'completed';
    }

    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    public function checkCompletion(): bool
    {
        // Проверяем, все ли задачи этапа выполнены
        $incompleteTasks = $this->tasks()->whereNotIn('status', ['done', 'blocked'])->count();
        
        if ($incompleteTasks === 0 && $this->tasks()->count() > 0) {
            $this->status = 'completed';
            $this->completed_date = now();
            $this->save();
            
            return true;
        }
        
        return false;
    }

    public function getDeliverableStatus(): array
    {
        if (empty($this->deliverables)) {
            return [];
        }
        
        return collect($this->deliverables)->map(function ($deliverable) {
            return [
                'name' => $deliverable['name'] ?? '',
                'status' => $deliverable['status'] ?? 'pending',
                'completed' => ($deliverable['status'] ?? 'pending') === 'completed',
            ];
        })->toArray();
    }
}