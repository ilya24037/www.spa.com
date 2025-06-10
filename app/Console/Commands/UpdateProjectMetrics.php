<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectMilestone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateProjectMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:update-metrics {--project=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет метрики и прогресс всех активных проектов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        
        $this->info('Начинаю обновление метрик проектов...');

        // Если указан конкретный проект
        if ($projectId = $this->option('project')) {
            $projects = Project::where('id', $projectId)->get();
        } else {
            // Обновляем только активные проекты
            $projects = Project::whereIn('status', ['planning', 'active'])->get();
        }

        $this->info("Найдено проектов для обновления: {$projects->count()}");

        $updatedCount = 0;
        $errors = [];

        foreach ($projects as $project) {
            try {
                $this->line("Обновляю проект: {$project->name} (ID: {$project->id})");

                // Обновляем статусы просроченных задач
                $this->updateOverdueTasks($project);

                // Обновляем статусы этапов
                $this->updateMilestoneStatuses($project);

                // Проверяем автоматическое завершение этапов
                $this->checkMilestoneCompletion($project);

                // Обновляем основные метрики
                $project->updateMetrics();

                // Проверяем критические ситуации
                $this->checkCriticalSituations($project);

                $updatedCount++;
                $this->info("✓ Проект {$project->name} успешно обновлен");

            } catch (\Exception $e) {
                $errors[] = [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'error' => $e->getMessage(),
                ];
                
                $this->error("✗ Ошибка при обновлении проекта {$project->name}: {$e->getMessage()}");
                
                Log::error('Project metrics update failed', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $duration = now()->diffInSeconds($startTime);

        $this->newLine();
        $this->info("Обновление завершено!");
        $this->info("Обновлено проектов: {$updatedCount}/{$projects->count()}");
        $this->info("Время выполнения: {$duration} секунд");

        if (!empty($errors)) {
            $this->error("Произошло ошибок: " . count($errors));
            
            foreach ($errors as $error) {
                $this->error("- Проект {$error['project_name']}: {$error['error']}");
            }
        }

        // Отправляем сводный отчет администраторам
        if (!$this->option('project')) {
            $this->sendDailyReport($updatedCount, $errors);
        }

        return count($errors) === 0 ? 0 : 1;
    }

    /**
     * Обновляет статусы просроченных задач
     */
    private function updateOverdueTasks(Project $project)
    {
        $overdueTasks = $project->tasks()
            ->where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'blocked'])
            ->get();

        foreach ($overdueTasks as $task) {
            // Логируем просрочку
            \App\Models\ProjectActivityLog::create([
                'project_id' => $project->id,
                'user_id' => null, // Системное действие
                'type' => 'task_overdue',
                'entity_type' => 'task',
                'entity_id' => $task->id,
                'description' => "Задача просрочена: {$task->title}",
                'impact_score' => -5,
            ]);
        }

        $this->line("  - Найдено просроченных задач: {$overdueTasks->count()}");
    }

    /**
     * Обновляет статусы этапов
     */
    private function updateMilestoneStatuses(Project $project)
    {
        $milestones = $project->milestones()
            ->where('status', '!=', 'completed')
            ->get();

        foreach ($milestones as $milestone) {
            $oldStatus = $milestone->status;
            $milestone->updateStatus();

            if ($milestone->status !== $oldStatus && $milestone->status === 'delayed') {
                // Логируем задержку этапа
                \App\Models\ProjectActivityLog::create([
                    'project_id' => $project->id,
                    'user_id' => null,
                    'type' => 'milestone_delayed',
                    'entity_type' => 'milestone',
                    'entity_id' => $milestone->id,
                    'description' => "Этап задержан: {$milestone->name}",
                    'impact_score' => -10,
                ]);
            }
        }
    }

    /**
     * Проверяет автоматическое завершение этапов
     */
    private function checkMilestoneCompletion(Project $project)
    {
        $milestones = $project->milestones()
            ->where('status', '!=', 'completed')
            ->get();

        foreach ($milestones as $milestone) {
            if ($milestone->checkCompletion()) {
                $this->line("  - Этап '{$milestone->name}' автоматически завершен");
            }
        }
    }

    /**
     * Проверяет критические ситуации
     */
    private function checkCriticalSituations(Project $project)
    {
        // Проверяем критическое падение health score
        if ($project->health_score < 30) {
            \App\Models\ProjectActivityLog::create([
                'project_id' => $project->id,
                'user_id' => null,
                'type' => 'critical_health',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => "Критически низкий показатель здоровья проекта: {$project->health_score}",
                'impact_score' => -20,
            ]);

            // TODO: Отправить уведомление менеджеру проекта
        }

        // Проверяем превышение бюджета
        if ($project->budget > 0 && $project->spent_budget > $project->budget * 1.1) {
            \App\Models\ProjectActivityLog::create([
                'project_id' => $project->id,
                'user_id' => null,
                'type' => 'budget_exceeded',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => "Превышение бюджета более чем на 10%",
                'impact_score' => -15,
            ]);
        }

        // Проверяем отставание от графика
        $timeline = $project->getTimelineProgress();
        if ($timeline['status'] === 'behind' && $timeline['progress'] - $project->progress > 20) {
            \App\Models\ProjectActivityLog::create([
                'project_id' => $project->id,
                'user_id' => null,
                'type' => 'schedule_behind',
                'entity_type' => 'project',
                'entity_id' => $project->id,
                'description' => "Серьезное отставание от графика",
                'impact_score' => -15,
            ]);
        }
    }

    /**
     * Отправляет ежедневный отчет
     */
    private function sendDailyReport($updatedCount, $errors)
    {
        // Собираем статистику
        $criticalProjects = Project::where('health_score', '<', 50)
            ->whereIn('status', ['active'])
            ->count();

        $overdueTasksTotal = ProjectTask::where('due_date', '<', now())
            ->whereNotIn('status', ['done', 'blocked'])
            ->count();

        $completedToday = ProjectTask::whereDate('completed_at', today())
            ->count();

        // TODO: Реализовать отправку email с отчетом администраторам

        $this->info("\nСводная статистика:");
        $this->info("- Проектов в критическом состоянии: {$criticalProjects}");
        $this->info("- Всего просроченных задач: {$overdueTasksTotal}");
        $this->info("- Завершено задач сегодня: {$completedToday}");
    }
}