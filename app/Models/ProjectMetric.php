<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'date',
        'tasks_total',
        'tasks_completed',
        'tasks_in_progress',
        'tasks_overdue',
        'budget_spent_percentage',
        'time_spent_percentage',
        'team_velocity',
        'health_score',
        'custom_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'budget_spent_percentage' => 'decimal:2',
        'time_spent_percentage' => 'decimal:2',
        'team_velocity' => 'decimal:1',
        'custom_metrics' => 'array',
    ];

    // Отношения
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Методы
    public function getCompletionRate(): float
    {
        if ($this->tasks_total === 0) {
            return 0;
        }

        return round(($this->tasks_completed / $this->tasks_total) * 100, 2);
    }

    public function getProductivityScore(): int
    {
        $completionRate = $this->getCompletionRate();
        $velocityScore = min(100, $this->team_velocity * 10);
        $overdueScore = $this->tasks_total > 0 
            ? 100 - (($this->tasks_overdue / $this->tasks_total) * 100)
            : 100;

        return round(($completionRate + $velocityScore + $overdueScore) / 3);
    }

    public function getBurndownRate(): ?float
    {
        if ($this->team_velocity === 0) {
            return null;
        }

        $remainingTasks = $this->tasks_total - $this->tasks_completed;
        return round($remainingTasks / $this->team_velocity, 1);
    }

    // Сравнение с предыдущей метрикой
    public function compareWithPrevious(): array
    {
        $previous = self::where('project_id', $this->project_id)
            ->where('date', '<', $this->date)
            ->orderBy('date', 'desc')
            ->first();

        if (!$previous) {
            return [
                'health_score_change' => 0,
                'velocity_change' => 0,
                'completion_change' => 0,
                'overdue_change' => 0,
            ];
        }

        return [
            'health_score_change' => $this->health_score - $previous->health_score,
            'velocity_change' => $this->team_velocity - $previous->team_velocity,
            'completion_change' => $this->tasks_completed - $previous->tasks_completed,
            'overdue_change' => $this->tasks_overdue - $previous->tasks_overdue,
        ];
    }

    // Scopes
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
    }

    // Агрегированные данные
    public static function getAverageMetrics($projectId, $days = 30)
    {
        return self::where('project_id', $projectId)
            ->where('date', '>=', now()->subDays($days))
            ->selectRaw('
                AVG(tasks_total) as avg_tasks_total,
                AVG(tasks_completed) as avg_tasks_completed,
                AVG(tasks_overdue) as avg_tasks_overdue,
                AVG(team_velocity) as avg_velocity,
                AVG(health_score) as avg_health_score,
                MIN(health_score) as min_health_score,
                MAX(health_score) as max_health_score
            ')
            ->first();
    }
}