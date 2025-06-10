<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Обновляем метрики проектов каждый час
        $schedule->command('projects:update-metrics')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                // Логируем ошибку
                \Log::error('Failed to update project metrics');
            });

        // Ежедневная проверка и оптимизация в 2 часа ночи
        $schedule->command('projects:update-metrics')
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Проверка критических задач каждые 30 минут в рабочее время
        $schedule->command('projects:update-metrics')
            ->weekdays()
            ->everyThirtyMinutes()
            ->between('9:00', '18:00');

        // Очистка старых логов активности (старше 90 дней)
        $schedule->call(function () {
            \App\Models\ProjectActivityLog::where('created_at', '<', now()->subDays(90))
                ->delete();
        })->weekly();

        // Архивирование старых метрик (сжатие данных старше 30 дней)
        $schedule->call(function () {
            // Группируем метрики по неделям для проектов старше 30 дней
            $oldMetrics = \App\Models\ProjectMetric::where('date', '<', now()->subDays(30))
                ->get()
                ->groupBy(function ($metric) {
                    return $metric->project_id . '_' . $metric->date->weekOfYear;
                });

            foreach ($oldMetrics as $group) {
                if ($group->count() > 1) {
                    // Берем среднее значение за неделю
                    $avgMetric = [
                        'tasks_total' => round($group->avg('tasks_total')),
                        'tasks_completed' => round($group->avg('tasks_completed')),
                        'tasks_in_progress' => round($group->avg('tasks_in_progress')),
                        'tasks_overdue' => round($group->avg('tasks_overdue')),
                        'budget_spent_percentage' => $group->avg('budget_spent_percentage'),
                        'time_spent_percentage' => $group->avg('time_spent_percentage'),
                        'team_velocity' => $group->avg('team_velocity'),
                        'health_score' => round($group->avg('health_score')),
                    ];

                    // Сохраняем усредненную метрику
                    $firstMetric = $group->first();
                    $firstMetric->update($avgMetric);

                    // Удаляем остальные
                    $group->slice(1)->each->delete();
                }
            }
        })->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}