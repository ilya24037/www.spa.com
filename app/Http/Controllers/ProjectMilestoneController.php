<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;

class ProjectMilestoneController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        $milestones = $project->milestones()
            ->withCount('tasks')
            ->with(['tasks' => function ($query) {
                $query->select('id', 'milestone_id', 'status');
            }])
            ->orderBy('order')
            ->orderBy('due_date')
            ->get()
            ->map(function ($milestone) {
                $milestone->progress = $milestone->getProgress();
                return $milestone;
            });

        return response()->json([
            'milestones' => $milestones,
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date|after:today',
            'weight' => 'required|integer|min:1|max:10',
            'is_critical' => 'boolean',
            'deliverables' => 'nullable|array',
            'deliverables.*.name' => 'required|string',
            'deliverables.*.status' => 'required|in:pending,completed',
        ]);

        $maxOrder = $project->milestones()->max('order') ?? 0;

        $milestone = $project->milestones()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'due_date' => $validated['due_date'],
            'weight' => $validated['weight'],
            'is_critical' => $validated['is_critical'] ?? false,
            'deliverables' => $validated['deliverables'] ?? [],
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'milestone' => $milestone,
            'message' => 'Этап успешно создан',
        ], 201);
    }

    public function update(Request $request, Project $project, ProjectMilestone $milestone)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'sometimes|required|date',
            'weight' => 'sometimes|required|integer|min:1|max:10',
            'is_critical' => 'boolean',
            'deliverables' => 'nullable|array',
            'deliverables.*.name' => 'required|string',
            'deliverables.*.status' => 'required|in:pending,completed',
        ]);

        $milestone->update($validated);
        $milestone->updateStatus();

        return response()->json([
            'milestone' => $milestone->load('tasks'),
            'message' => 'Этап успешно обновлен',
        ]);
    }

    public function complete(Request $request, Project $project, ProjectMilestone $milestone)
    {
        $this->authorize('update', $project);

        $milestone->status = 'completed';
        $milestone->completed_date = now();
        $milestone->save();

        // Проверяем, все ли задачи выполнены
        $incompleteTasks = $milestone->tasks()
            ->whereNotIn('status', ['done', 'blocked'])
            ->count();

        if ($incompleteTasks > 0) {
            return response()->json([
                'warning' => true,
                'message' => "Этап отмечен как выполненный, но есть {$incompleteTasks} незавершенных задач",
                'milestone' => $milestone,
            ]);
        }

        return response()->json([
            'milestone' => $milestone,
            'message' => 'Этап успешно завершен',
        ]);
    }

    public function reorder(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'milestones' => 'required|array',
            'milestones.*.id' => 'required|exists:project_milestones,id',
            'milestones.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['milestones'] as $milestoneData) {
            ProjectMilestone::where('id', $milestoneData['id'])
                ->where('project_id', $project->id)
                ->update(['order' => $milestoneData['order']]);
        }

        return response()->json([
            'message' => 'Порядок этапов обновлен',
        ]);
    }

    public function destroy(Project $project, ProjectMilestone $milestone)
    {
        $this->authorize('update', $project);

        // Отвязываем задачи от этапа
        $milestone->tasks()->update(['milestone_id' => null]);

        $milestone->delete();

        return response()->json([
            'message' => 'Этап успешно удален',
        ]);
    }

    // Получение диаграммы Ганта
    public function ganttChart(Project $project)
    {
        $this->authorize('view', $project);

        $milestones = $project->milestones()
            ->with(['tasks' => function ($query) {
                $query->select('id', 'milestone_id', 'title', 'start_date', 'due_date', 'status', 'assigned_to')
                    ->with('assignee:id,name');
            }])
            ->orderBy('due_date')
            ->get();

        $ganttData = $milestones->map(function ($milestone) {
            return [
                'id' => 'milestone_' . $milestone->id,
                'name' => $milestone->name,
                'start' => $milestone->created_at->format('Y-m-d'),
                'end' => $milestone->due_date->format('Y-m-d'),
                'progress' => $milestone->getProgress(),
                'type' => 'milestone',
                'is_critical' => $milestone->is_critical,
                'tasks' => $milestone->tasks->map(function ($task) {
                    return [
                        'id' => 'task_' . $task->id,
                        'name' => $task->title,
                        'start' => $task->start_date ? $task->start_date->format('Y-m-d') : now()->format('Y-m-d'),
                        'end' => $task->due_date ? $task->due_date->format('Y-m-d') : now()->addDays(7)->format('Y-m-d'),
                        'progress' => $task->progress,
                        'assignee' => $task->assignee ? $task->assignee->name : 'Не назначен',
                        'status' => $task->status,
                    ];
                }),
            ];
        });

        return response()->json([
            'gantt' => $ganttData,
        ]);
    }
}