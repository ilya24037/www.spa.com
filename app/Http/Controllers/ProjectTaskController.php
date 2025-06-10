<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectTaskController extends Controller
{
    public function index(Project $project, Request $request)
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()
            ->with(['assignee', 'milestone', 'creator'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->assignee, function ($query, $assignee) {
                $query->where('assigned_to', $assignee);
            })
            ->when($request->milestone, function ($query, $milestone) {
                $query->where('milestone_id', $milestone);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->orderBy('order')
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|exists:project_milestones,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,critical',
            'estimated_hours' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after:start_date',
            'tags' => 'nullable|array',
            'checklist' => 'nullable|array',
            'dependencies' => 'nullable|array',
        ]);

        $task = DB::transaction(function () use ($project, $validated, $request) {
            // Получаем максимальный order
            $maxOrder = $project->tasks()->max('order') ?? 0;

            $task = $project->tasks()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'milestone_id' => $validated['milestone_id'],
                'assigned_to' => $validated['assigned_to'],
                'created_by' => $request->user()->id,
                'priority' => $validated['priority'],
                'estimated_hours' => $validated['estimated_hours'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
                'tags' => $validated['tags'] ?? [],
                'checklist' => $validated['checklist'] ?? [],
                'dependencies' => $validated['dependencies'] ?? [],
                'order' => $maxOrder + 1,
            ]);

            return $task;
        });

        return response()->json([
            'task' => $task->load(['assignee', 'milestone', 'creator']),
            'message' => 'Задача успешно создана',
        ], 201);
    }

    public function update(Request $request, Project $project, ProjectTask $task)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'milestone_id' => 'nullable|exists:project_milestones,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'sometimes|required|in:todo,in_progress,review,done,blocked',
            'priority' => 'sometimes|required|in:low,medium,high,critical',
            'estimated_hours' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
            'checklist' => 'nullable|array',
            'dependencies' => 'nullable|array',
        ]);

        $task->update($validated);

        return response()->json([
            'task' => $task->load(['assignee', 'milestone', 'creator']),
            'message' => 'Задача успешно обновлена',
        ]);
    }

    public function updateProgress(Request $request, Project $project, ProjectTask $task)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $task->updateProgress($validated['progress']);

        return response()->json([
            'task' => $task->load(['assignee', 'milestone']),
            'message' => 'Прогресс обновлен',
        ]);
    }

    public function updateChecklist(Request $request, Project $project, ProjectTask $task)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'checklist' => 'required|array',
            'checklist.*.text' => 'required|string',
            'checklist.*.completed' => 'required|boolean',
        ]);

        $task->updateChecklist($validated['checklist']);

        return response()->json([
            'task' => $task,
            'message' => 'Чеклист обновлен',
        ]);
    }

    public function logTime(Request $request, Project $project, ProjectTask $task)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'hours' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        $task->logTimeSpent($validated['hours'], $validated['description']);

        return response()->json([
            'task' => $task,
            'message' => 'Время записано',
        ]);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:project_tasks,id',
            'tasks.*.order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['tasks'] as $taskData) {
                ProjectTask::where('id', $taskData['id'])
                    ->update(['order' => $taskData['order']]);
            }
        });

        return response()->json([
            'message' => 'Порядок задач обновлен',
        ]);
    }

    public function bulkUpdate(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'exists:project_tasks,id',
            'action' => 'required|in:status,priority,assignee,milestone',
            'value' => 'required',
        ]);

        $tasks = $project->tasks()->whereIn('id', $validated['task_ids'])->get();

        foreach ($tasks as $task) {
            switch ($validated['action']) {
                case 'status':
                    $task->status = $validated['value'];
                    break;
                case 'priority':
                    $task->priority = $validated['value'];
                    break;
                case 'assignee':
                    $task->assigned_to = $validated['value'];
                    break;
                case 'milestone':
                    $task->milestone_id = $validated['value'];
                    break;
            }
            $task->save();
        }

        return response()->json([
            'message' => 'Задачи успешно обновлены',
            'updated_count' => $tasks->count(),
        ]);
    }

    public function destroy(Project $project, ProjectTask $task)
    {
        $this->authorize('update', $project);

        $task->delete();

        return response()->json([
            'message' => 'Задача успешно удалена',
        ]);
    }

    // Получение задач для канбан-доски
    public function kanban(Project $project)
    {
        $this->authorize('view', $project);

        $tasksByStatus = $project->tasks()
            ->with(['assignee', 'milestone'])
            ->get()
            ->groupBy('status');

        $kanbanData = [
            'todo' => $tasksByStatus->get('todo', collect()),
            'in_progress' => $tasksByStatus->get('in_progress', collect()),
            'review' => $tasksByStatus->get('review', collect()),
            'done' => $tasksByStatus->get('done', collect()),
            'blocked' => $tasksByStatus->get('blocked', collect()),
        ];

        return response()->json([
            'kanban' => $kanbanData,
        ]);
    }

    // Обновление статуса задачи при перетаскивании
    public function updateKanban(Request $request, Project $project, ProjectTask $task)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,review,done,blocked',
            'order' => 'required|integer|min:0',
        ]);

        $task->update([
            'status' => $validated['status'],
            'order' => $validated['order'],
        ]);

        return response()->json([
            'task' => $task->load(['assignee', 'milestone']),
            'message' => 'Статус обновлен',
        ]);
    }
}