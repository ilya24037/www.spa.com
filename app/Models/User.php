<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Отношение к профилю
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Проверка роли пользователя
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Проверка, является ли пользователь мастером
     */
    public function isMaster()
    {
        return $this->role === 'master';
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Проверка, является ли пользователь клиентом
     */
    public function isClient()
    {
        return $this->role === 'client';
    }

    /**
     * Boot метод для автоматического создания профиля
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            $user->profile()->create([
                'bio' => '',
                'city' => '',
            ]);
        });
    }

    /**
     * Получить URL аватара
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Возвращаем дефолтный аватар
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=0ea5e9&background=e0f2fe';
    }

    /**
     * Проекты, созданные пользователем
     */
    public function ownedProjects()
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    /**
     * Все проекты, в которых участвует пользователь
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role', 'joined_at', 'is_active')
            ->wherePivot('is_active', true);
    }

    /**
     * Активные проекты пользователя
     */
    public function activeProjects()
    {
        return $this->projects()
            ->whereIn('status', ['planning', 'active'])
            ->orderBy('projects.created_at', 'desc');
    }

    /**
     * Задачи, назначенные пользователю
     */
    public function assignedTasks()
    {
        return $this->hasMany(ProjectTask::class, 'assigned_to');
    }

    /**
     * Активные задачи пользователя
     */
    public function activeTasks()
    {
        return $this->assignedTasks()
            ->whereNotIn('status', ['done', 'blocked'])
            ->orderBy('priority', 'desc')
            ->orderBy('due_date');
    }

    /**
     * Получить статистику пользователя по проектам
     */
    public function getProjectStats(): array
    {
        $totalProjects = $this->projects()->count();
        $activeProjects = $this->activeProjects()->count();
        $completedProjects = $this->projects()->where('status', 'completed')->count();
        
        $totalTasks = $this->assignedTasks()->count();
        $completedTasks = $this->assignedTasks()->where('status', 'done')->count();
        $overdueTasks = $this->assignedTasks()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'blocked'])
            ->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'task_completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
        ];
    }

    /**
     * Получить предстоящие дедлайны
     */
    public function getUpcomingDeadlines($days = 7)
    {
        return $this->assignedTasks()
            ->where('due_date', '<=', now()->addDays($days))
            ->where('due_date', '>=', now())
            ->whereNotIn('status', ['done', 'blocked'])
            ->orderBy('due_date')
            ->with(['project', 'milestone'])
            ->get();
    }
}