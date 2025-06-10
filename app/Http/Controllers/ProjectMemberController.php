<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $members = $project->members()
            ->active()
            ->with('user:id,name,email,avatar')
            ->get()
            ->map(function ($member) {
                $member->stats = $member->getContributionStats();
                return $member;
            });

        return response()->json([
            'members' => $members,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('manageMember', $project);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:manager,developer,viewer',
        ]);

        // Проверяем, не является ли пользователь уже участником
        $existingMember = $project->members()
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existingMember) {
            if ($existingMember->is_active) {
                return response()->json([
                    'message' => 'Пользователь уже является участником проекта',
                ], 422);
            } else {
                // Реактивируем участника
                $existingMember->update([
                    'is_active' => true,
                    'role' => $validated['role'],
                    'left_at' => null,
                ]);

                return response()->json([
                    'member' => $existingMember->load('user'),
                    'message' => 'Участник успешно добавлен в проект',
                ]);
            }
        }

        $member = $project->members()->create([
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
        ]);

        // Отправляем уведомление пользователю
        $user = User::find($validated['user_id']);
        // TODO: Отправить уведомление

        return response()->json([
            'member' => $member->load('user'),
            'message' => 'Участник успешно добавлен',
        ], 201);
    }

    public function update(Request $request, Project $project, ProjectMember $member)
    {
        $this->authorize('manageMember', $project);

        $validated = $request->validate([
            'role' => 'required|in:manager,developer,viewer',
            'permissions' => 'nullable|array',
        ]);

        // Нельзя изменить роль владельца
        if ($member->role === 'owner') {
            return response()->json([
                'message' => 'Нельзя изменить роль владельца проекта',
            ], 403);
        }

        $member->update([
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? ProjectMember::getDefaultPermissions($validated['role']),
        ]);

        return response()->json([
            'member' => $member->load('user'),
            'message' => 'Роль участника обновлена',
        ]);
    }

    public function remove(Project $project, ProjectMember $member)
    {
        $this->authorize('manageMember', $project);

        // Нельзя удалить владельца
        if ($member->role === 'owner') {
            return response()->json([
                'message' => 'Нельзя удалить владельца проекта',
            ], 403);
        }

        DB::transaction(function () use ($member) {
            // Снимаем назначение со всех задач
            $member->project->tasks()
                ->where('assigned_to', $member->user_id)
                ->update(['assigned_to' => null]);

            // Деактивируем участника
            $member->update([
                'is_active' => false,
                'left_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Участник удален из проекта',
        ]);
    }

    public function bulkInvite(Request $request, Project $project)
    {
        $this->authorize('manageMember', $project);

        $validated = $request->validate([
            'emails' => 'required|array',
            'emails.*' => 'required|email',
            'role' => 'required|in:manager,developer,viewer',
        ]);

        $results = [
            'added' => [],
            'existing' => [],
            'not_found' => [],
        ];

        foreach ($validated['emails'] as $email) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $results['not_found'][] = $email;
                continue;
            }

            $existingMember = $project->members()
                ->where('user_id', $user->id)
                ->first();

            if ($existingMember && $existingMember->is_active) {
                $results['existing'][] = $email;
                continue;
            }

            if ($existingMember) {
                $existingMember->update([
                    'is_active' => true,
                    'role' => $validated['role'],
                    'left_at' => null,
                ]);
            } else {
                $project->members()->create([
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                ]);
            }

            $results['added'][] = $email;
        }

        return response()->json([
            'results' => $results,
            'message' => count($results['added']) . ' участников добавлено',
        ]);
    }

    public function getAvailableUsers(Project $project)
    {
        $this->authorize('view', $project);

        $memberIds = $project->members()
            ->active()
            ->pluck('user_id');

        $users = User::where('is_active', true)
            ->whereNotIn('id', $memberIds)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    public function getWorkload(Project $project, ProjectMember $member)
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()
            ->where('assigned_to', $member->user_id)
            ->whereNotIn('status', ['done', 'blocked'])
            ->select('id', 'title', 'priority', 'due_date', 'estimated_hours', 'actual_hours')
            ->orderBy('due_date')
            ->get();

        $workload = [
            'total_tasks' => $tasks->count(),
            'high_priority_tasks' => $tasks->where('priority', 'high')->count() + $tasks->where('priority', 'critical')->count(),
            'estimated_hours' => $tasks->sum('estimated_hours'),
            'hours_logged' => $tasks->sum('actual_hours'),
            'overdue_tasks' => $tasks->filter->isOverdue()->count(),
            'upcoming_deadlines' => $tasks->filter(function ($task) {
                return $task->due_date && $task->due_date->isBetween(now(), now()->addDays(7));
            })->count(),
        ];

        return response()->json([
            'member' => $member->load('user'),
            'tasks' => $tasks,
            'workload' => $workload,
        ]);
    }
}