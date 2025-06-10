<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'status',
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'budget',
        'spent_budget',
        'progress',
        'health_score',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'decimal:2',
        'spent_budget' => 'decimal:2',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Автоматически устанавливаем actual_start_date при первой активности
        static::updating(function ($project) {
            if ($project->status === 'active' && !$project->actual_start_date) {
                $project->actual_start_date = now();
            }
            
            if ($project->status === 'completed' && !$project->actual_end_date) {
                $project->actual_end_date = now();
            }
        });

        // После создания проекта создаем первую запись метрик
        static::created(function ($project) {
            $project->metrics()->create([
                'date' => today(),
                'health_score' => 100,
            ]);
            
            // Добавляем владельца как участника
            $project->members()->create([
                'user_id' => $project->user_id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        });
    }

    // Отношения
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ProjectActivityLog::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ProjectMetric::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role', 'joined_at', 'is_active')
            ->wherePivot('is_active', true);
    }

    // Автоматические вычисления
    public function calculateProgress(): int
    {
        // Вычисляем прогресс на основе выполненных задач и этапов
        $totalWeight = 0;
        $completedWeight = 0;

        // Учитываем вес этапов
        $milestones = $this->milestones;
        foreach ($milestones as $milestone) {
            $totalWeight += $milestone->weight;
            if ($milestone->status === 'completed') {
                $completedWeight += $milestone->weight;
            }
        }

        // Если нет этапов, считаем по задачам
        if ($totalWeight === 0) {
            $totalTasks = $this->tasks()->count();
            $completedTasks = $this->tasks()->where('status', 'done')->count();
            
            if ($totalTasks > 0) {
                return round(($completedTasks / $totalTasks) * 100);
            }
            
            return 0;
        }

        return round(($completedWeight / $totalWeight) * 100);
    }

    public function calculateHealthScore(): int
    {
        $score = 100;
        $penalties = 0;

        // Проверяем просроченные задачи
        $overdueTasks = $this->tasks()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'blocked'])
            ->count();
        
        $penalties += $overdueTasks * 5; // -5 за каждую просроченную задачу

        // Проверяем отставание от графика
        if ($this->end_date && $this->start_date) {
            $totalDays = $this->start_date->diffInDays($this->end_date);
            $elapsedDays = $this->start_date->diffInDays(now());
            $expectedProgress = min(100, ($elapsedDays / $totalDays) * 100);
            
            if ($this->progress < $expectedProgress - 10) {
                $penalties += round($expectedProgress - $this->progress);
            }
        }

        // Проверяем превышение бюджета
        if ($this->budget > 0 && $this->spent_budget > $this->budget) {
            $overBudgetPercent = (($this->spent_budget - $this->budget) / $this->budget) * 100;
            $penalties += round($overBudgetPercent / 2);
        }

        return max(0, $score - $penalties);
    }

    public function updateMetrics(): void
    {
        $today = today();
        
        $metrics = $this->metrics()->firstOrCreate(
            ['date' => $today],
            []
        );

        $tasks = $this->tasks;
        $metrics->tasks_total = $tasks->count();
        $metrics->tasks_completed = $tasks->where('status', 'done')->count();
        $metrics->tasks_in_progress = $tasks->where('status', 'in_progress')->count();
        $metrics->tasks_overdue = $tasks->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'blocked'])
            ->count();

        // Вычисляем процент потраченного бюджета
        if ($this->budget > 0) {
            $metrics->budget_spent_percentage = ($this->spent_budget / $this->budget) * 100;
        }

        // Вычисляем процент потраченного времени
        if ($this->end_date && $this->start_date) {
            $totalDays = $this->start_date->diffInDays($this->end_date);
            $elapsedDays = $this->start_date->diffInDays(now());
            $metrics->time_spent_percentage = min(100, ($elapsedDays / $totalDays) * 100);
        }

        // Вычисляем скорость команды (задач выполнено за последние 7 дней)
        $tasksLastWeek = $this->tasks()
            ->where('completed_at', '>=', now()->subDays(7))
            ->where('status', 'done')
            ->count();
        $metrics->team_velocity = round($tasksLastWeek / 7, 1);

        // Обновляем health score
        $metrics->health_score = $this->calculateHealthScore();
        
        $metrics->save();

        // Обновляем прогресс проекта
        $this->progress = $this->calculateProgress();
        $this->health_score = $metrics->health_score;
        $this->save();
    }

    // Дополнительные методы
    public function getTimelineProgress(): array
    {
        if (!$this->start_date || !$this->end_date) {
            return ['progress' => 0, 'status' => 'not_started'];
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        $elapsedDays = $this->start_date->diffInDays(now());
        $remainingDays = max(0, now()->diffInDays($this->end_date, false));

        $timeProgress = min(100, ($elapsedDays / $totalDays) * 100);

        $status = 'on_track';
        if ($this->progress < $timeProgress - 10) {
            $status = 'behind';
        } elseif ($this->progress > $timeProgress + 10) {
            $status = 'ahead';
        }

        return [
            'progress' => round($timeProgress),
            'elapsed_days' => $elapsedDays,
            'remaining_days' => $remainingDays,
            'total_days' => $totalDays,
            'status' => $status,
        ];
    }

    public function getDailyMetrics(int $days = 30): array
    {
        return $this->metrics()
            ->where('date', '>=', now()->subDays($days))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getUpcomingMilestones(int $limit = 5)
    {
        return $this->milestones()
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->limit($limit)
            ->get();
    }

    public function getRecentActivities(int $limit = 10)
    {
        return $this->activityLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
