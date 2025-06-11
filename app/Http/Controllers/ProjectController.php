<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Создаем ключ кэша на основе пользователя и фильтров
        $cacheKey = "user_projects_{$user->id}_" . md5(serialize($request->only(['status', 'search'])));
        
        $projects = Cache::remember($cacheKey, 300, function () use ($user, $request) {
            return $user->projects()
                ->with([
                    'owner:id,name,email',
                    'members:id,project_id,user_id,role',
                    'members.user:id,name,email'
                ])
                ->withCount(['tasks', 'milestones'])
                ->when($request->status, function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when($request->search, function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->select([
                    'id', 'name', 'description', 'status', 'progress', 'health_score',
                    'budget', 'spent_budget', 'start_date', 'end_date', 'created_at'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate(12);
        });

        // Получаем статистику для дашборда (тоже с кэшированием)
        $statsCacheKey = "user_stats_{$user->id}";
        $stats = Cache::remember($statsCacheKey, 600, function () use ($user) {
            $projectsQuery = $user->projects();
            
            return [
                'total' => $projectsQuery->count(),
                'active' => $projectsQuery->where('status', 'active')->count(),
                'completed' => $projectsQuery->where('status', 'completed')->count(),
                'on_hold' => $projectsQuery->where('status', 'on_hold')->count(),
            ];
        });

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        $users = Cache::remember('active_users_list', 3600, function () {
            return User::where('is_active', true)
                ->where('id', '!=', auth()->id())
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
        });

        return Inertia::render('Projects/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0|max:99999999.99',
            'members' => 'nullable|array|max:50',
            'members.*.user_id' => 'required|exists:users,id,is_active,1',
            'members.*.role' => 'required|in:manager,developer,viewer',
        ]);

        try {
            $project = DB::transaction(function () use ($validated, $request) {
                // Создаем проект
                $project = $request->user()->ownedProjects()->create([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'budget' => $validated['budget'],
                    'status' => 'planning',
                ]);

                // Добавляем участников
                if (!empty($validated['members'])) {
                    $members = collect($validated['members'])
                        ->unique('user_id')
                        ->map(function ($member) use ($project, $request) {
                            return [
                                'project_id' => $project->id,
                                'user_id' => $member['user_id'],
                                'role' => $member['role'],
                                'joined_at' => now(),
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        });

                    ProjectMember::insert($members->toArray());
                }

                return $project;
            });

            // Очищаем кэш
            $this->clearUserCache($request->user()->id);

            return redirect()->route('projects.index')
                ->with('success', 'Проект успешно создан');

        } catch (\Exception $e) {
            Log::error('Project creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()->withErrors(['error' => 'Ошибка при создании проекта'])
                         ->withInput();
        }
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        // Оптимизированная загрузка с минимальными запросами
        $project->load([
            'owner:id,name,email',
            'members:id,project_id,user_id,role,joined_at,is_active',
            'members.user:id,name,email',
            'milestones:id,project_id,name,description,due_date,completed_date,status,weight,is_critical,order',
            'tasks:id,project_id,milestone_id,assigned_to,title,description,status,priority,progress,due_date,estimated_hours,actual_hours',
            'tasks.assignee:id,name,email',
            'tasks.milestone:id,name'
        ]);

        // Получаем метрики за последние 30 дней (с кэшированием)
        $metricsCacheKey = "project_metrics_{$project->id}_30days";
        $metrics = Cache::remember($metricsCacheKey, 300, function () use ($project) {
            return $project->getDailyMetrics(30);
        });
        
        // Получаем последние активности (с кэшированием)
        $activitiesCacheKey = "project_activities_{$project->id}_recent";
        $activities = Cache::remember($activitiesCacheKey, 60, function () use ($project) {
            return $project->getRecentActivities(20);
        });
        
        // Получаем временную шкалу прогресса
        $timeline = $project->getTimelineProgress();
        
        // Статистика по задачам
        $taskStats = [
            'total' => $project->tasks->count(),
            'completed' => $project->tasks->where('status', 'done')->count(),
            'in_progress' => $project->tasks->where('status', 'in_progress')->count(),
            'overdue' => $project->tasks->filter->isOverdue()->count(),
        ];

        // Статистика по участникам
        $memberStats = $project->members()
            ->active()
            ->with('user:id,name,email')
            ->get()
            ->map(function ($member) {
                return [
                    'user' => $member->user,
                    'role' => $member->role,
                    'stats' => $member->getContributionStats(),
                ];
            });

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'metrics' => $metrics,
            'activities' => $activities,
            'timeline' => $timeline,
            'taskStats' => $taskStats,
            'memberStats' => $memberStats,
            'canEdit' => $request->user()->can('update', $project),
        ]);
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $project->load('members.user:id,name,email');
        
        $users = User::where('is_active', true)
            ->whereNotIn('id', $project->members->pluck('user_id'))
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Projects/Edit', [
            'project' => $project,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0|max:99999999.99',
        ]);

        try {
            $project->update($validated);

            // Очищаем кэш
            $this->clearProjectCache($project->id);

            return redirect()->route('projects.show', $project)
                ->with('success', 'Проект успешно обновлен');

        } catch (\Exception $e) {
            Log::error('Project update failed', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Ошибка при обновлении проекта']);
        }
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        try {
            DB::transaction(function () use ($project) {
                // Мягкое удаление связанных данных
                $project->tasks()->delete();
                $project->milestones()->delete();
                $project->members()->delete();
                $project->activityLogs()->delete();
                $project->metrics()->delete();
                
                // Удаляем сам проект
                $project->delete();
            });

            // Очищаем кэш
            $this->clearProjectCache($project->id);
            $this->clearUserCache($project->user_id);

            return redirect()->route('projects.index')
                ->with('success', 'Проект успешно удален');

        } catch (\Exception $e) {
            Log::error('Project deletion failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Ошибка при удалении проекта']);
        }
    }

    // Метод для обновления прогресса (вызывается автоматически)
    public function updateProgress(Project $project)
    {
        $this->authorize('view', $project);

        try {
            $project->updateMetrics();

            // Очищаем кэш метрик
            Cache::forget("project_metrics_{$project->id}_30days");

            return response()->json([
                'progress' => $project->progress,
                'health_score' => $project->health_score,
            ]);

        } catch (\Exception $e) {
            Log::error('Progress update failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Ошибка обновления прогресса'
            ], 500);
        }
    }

    // Метод для получения метрик проекта
    public function getMetrics(Project $project)
    {
        $this->authorize('view', $project);
        
        $metrics = Cache::remember("project_metrics_{$project->id}_30days", 300, function () use ($project) {
            return $project->getDailyMetrics(30);
        });
        
        return response()->json([
            'metrics' => $metrics,
        ]);
    }

    // Метод для получения активности проекта
    public function getActivities(Project $project)
    {
        $this->authorize('view', $project);
        
        $activities = Cache::remember("project_activities_{$project->id}_recent", 60, function () use ($project) {
            return $project->getRecentActivities(50);
        });
        
        return response()->json([
            'activities' => $activities,
        ]);
    }

    // Экспорт отчета по проекту
    public function exportReport(Project $project)
    {
        $this->authorize('view', $project);

        try {
            // Собираем данные для отчета
            $data = [
                'project' => $project->load(['owner', 'members.user', 'milestones', 'tasks']),
                'metrics' => $project->getDailyMetrics(30),
                'timeline' => $project->getTimelineProgress(),
                'taskStats' => [
                    'total' => $project->tasks->count(),
                    'completed' => $project->tasks->where('status', 'done')->count(),
                    'overdue' => $project->tasks->filter->isOverdue()->count(),
                ],
                'memberStats' => $project->members()->active()->with('user')->get()
                    ->map->getContributionStats(),
                'generated_at' => now()->toISOString(),
            ];

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error('Report export failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Ошибка генерации отчета'
            ], 500);
        }
    }

    /**
     * Очистка кэша пользователя
     */
    private function clearUserCache(int $userId): void
    {
        Cache::forget("user_projects_{$userId}_" . md5(''));
        Cache::forget("user_stats_{$userId}");
        
        // Очищаем все возможные варианты кэша проектов пользователя
        $patterns = [
            "user_projects_{$userId}_*",
        ];
        
        foreach ($patterns as $pattern) {
            Cache::flush(); // В production лучше использовать более точную очистку по тегам
        }
    }

    /**
     * Очистка кэша проекта
     */
    private function clearProjectCache(int $projectId): void
    {
        $patterns = [
            "project_metrics_{$projectId}_*",
            "project_activities_{$projectId}_*",
        ];
        
        foreach ($patterns as $pattern) {
            Cache::flush(); // В production лучше использовать tagged cache
        }
    }
}