<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $projects = $user->projects()
            ->with(['owner', 'members'])
            ->withCount(['tasks', 'milestones'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Получаем статистику для дашборда
        $stats = [
            'total' => $user->projects()->count(),
            'active' => $user->projects()->where('status', 'active')->count(),
            'completed' => $user->projects()->where('status', 'completed')->count(),
            'on_hold' => $user->projects()->where('status', 'on_hold')->count(),
        ];

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        $users = User::where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Projects/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'members' => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.role' => 'required|in:manager,developer,viewer',
        ]);

        DB::transaction(function () use ($validated, $request) {
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
                foreach ($validated['members'] as $member) {
                    $project->members()->create([
                        'user_id' => $member['user_id'],
                        'role' => $member['role'],
                    ]);
                }
            }
        });

        return redirect()->route('projects.index')
            ->with('success', 'Проект успешно создан');
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        // Загружаем все необходимые данные
        $project->load([
            'owner',
            'members.user',
            'milestones' => function ($query) {
                $query->orderBy('due_date');
            },
            'tasks' => function ($query) {
                $query->with('assignee')
                    ->orderBy('priority', 'desc')
                    ->orderBy('due_date');
            },
        ]);

        // Получаем метрики за последние 30 дней
        $metrics = $project->getDailyMetrics(30);
        
        // Получаем последние активности
        $activities = $project->getRecentActivities(20);
        
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
            ->with('user')
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

        $project->load('members.user');
        
        $users = User::where('is_active', true)
            ->whereNotIn('id', $project->members->pluck('user_id'))
            ->select('id', 'name', 'email')
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
            'description' => 'nullable|string',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Проект успешно обновлен');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Проект успешно удален');
    }

    // Метод для обновления прогресса (вызывается автоматически)
    public function updateProgress(Project $project)
    {
        $project->updateMetrics();

        return response()->json([
            'progress' => $project->progress,
            'health_score' => $project->health_score,
        ]);
    }
// Метод для получения метрик проекта
public function getMetrics(Project $project)
{
    $this->authorize('view', $project);
    
    $metrics = $project->getDailyMetrics(30);
    
    return response()->json([
        'metrics' => $metrics,
    ]);
}

// Метод для получения активности проекта
public function getActivities(Project $project)
{
    $this->authorize('view', $project);
    
    $activities = $project->getRecentActivities(50);
    
    return response()->json([
        'activities' => $activities,
    ]);
}

    // Экспорт отчета по проекту
    public function exportReport(Project $project)
    {
        $this->authorize('view', $project);

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
        ];

        // Здесь можно генерировать PDF или Excel файл
        // Для примера возвращаем JSON
        return response()->json($data);
    }
}