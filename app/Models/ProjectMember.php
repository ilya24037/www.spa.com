<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'is_active',
        'permissions',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($member) {
            $member->joined_at = $member->joined_at ?? now();
            
            // Устанавливаем разрешения по умолчанию на основе роли
            if (!$member->permissions) {
                $member->permissions = self::getDefaultPermissions($member->role);
            }
        });

        static::created(function ($member) {
            // Логируем добавление участника
            ProjectActivityLog::create([
                'project_id' => $member->project_id,
                'user_id' => auth()->id(),
                'type' => 'member_added',
                'entity_type' => 'member',
                'entity_id' => $member->id,
                'description' => "Добавлен участник: {$member->user->name} ({$member->role})",
                'impact_score' => 3,
            ]);
        });

        static::updating(function ($member) {
            if ($member->isDirty('is_active') && !$member->is_active) {
                $member->left_at = now();
                
                // Логируем удаление участника
                ProjectActivityLog::create([
                    'project_id' => $member->project_id,
                    'user_id' => auth()->id(),
                    'type' => 'member_removed',
                    'entity_type' => 'member',
                    'entity_id' => $member->id,
                    'description' => "Удален участник: {$member->user->name}",
                    'impact_score' => 3,
                ]);
            }
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

    // Методы для проверки разрешений
    public function hasPermission(string $permission): bool
    {
        // Владелец имеет все разрешения
        if ($this->role === 'owner') {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }

    public function canManageTasks(): bool
    {
        return $this->hasPermission('manage_tasks');
    }

    public function canManageMembers(): bool
    {
        return $this->hasPermission('manage_members');
    }

    public function canEditProject(): bool
    {
        return $this->hasPermission('edit_project');
    }

    public function canViewReports(): bool
    {
        return $this->hasPermission('view_reports');
    }

    // Статические методы
    public static function getDefaultPermissions(string $role): array
    {
        $permissions = [
            'owner' => [
                'manage_tasks',
                'manage_members', 
                'edit_project',
                'delete_project',
                'view_reports',
                'manage_budget',
            ],
            'manager' => [
                'manage_tasks',
                'manage_members',
                'edit_project',
                'view_reports',
                'manage_budget',
            ],
            'developer' => [
                'manage_tasks',
                'view_reports',
            ],
            'viewer' => [
                'view_reports',
            ],
        ];

        return $permissions[$role] ?? [];
    }

    // Scope
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Методы для статистики
    public function getContributionStats(): array
    {
        $tasksAssigned = ProjectTask::where('project_id', $this->project_id)
            ->where('assigned_to', $this->user_id)
            ->count();
            
        $tasksCompleted = ProjectTask::where('project_id', $this->project_id)
            ->where('assigned_to', $this->user_id)
            ->where('status', 'done')
            ->count();
            
        $hoursLogged = ProjectTask::where('project_id', $this->project_id)
            ->where('assigned_to', $this->user_id)
            ->sum('actual_hours');
            
        $activeDays = ProjectActivityLog::where('project_id', $this->project_id)
            ->where('user_id', $this->user_id)
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->count();

        return [
            'tasks_assigned' => $tasksAssigned,
            'tasks_completed' => $tasksCompleted,
            'completion_rate' => $tasksAssigned > 0 ? round(($tasksCompleted / $tasksAssigned) * 100) : 0,
            'hours_logged' => $hoursLogged,
            'active_days' => $activeDays,
            'days_in_project' => $this->joined_at->diffInDays(now()),
        ];
    }
}