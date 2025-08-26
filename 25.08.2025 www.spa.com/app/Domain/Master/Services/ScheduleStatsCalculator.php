<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Repositories\ScheduleRepository;
use Carbon\Carbon;

/**
 * Сервис расчета статистики расписания
 */
class ScheduleStatsCalculator
{
    private ScheduleRepository $scheduleRepository;

    public function __construct(ScheduleRepository $scheduleRepository)
    {
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * Получить статистику расписания мастера
     */
    public function calculateStats(int $masterId): array
    {
        $schedules = $this->scheduleRepository->getByMasterId($masterId, false); // Получаем все, включая неактивные
        
        $activeSchedules = $schedules->where('is_active', true);
        $totalWorkingDays = $activeSchedules->count();
        $totalWorkingHours = $this->calculateTotalWorkingHours($activeSchedules);
        
        return [
            'working_days_count' => $totalWorkingDays,
            'inactive_days_count' => $schedules->where('is_active', false)->count(),
            'total_working_hours_per_week' => $totalWorkingHours,
            'average_hours_per_day' => $totalWorkingDays > 0 ? round($totalWorkingHours / $totalWorkingDays, 1) : 0,
            'has_breaks' => $schedules->whereNotNull('break_start')->count() > 0,
            'break_days_count' => $schedules->whereNotNull('break_start')->count(),
            'schedule_efficiency' => $this->calculateEfficiency($activeSchedules),
            'weekly_schedule_coverage' => round(($totalWorkingDays / 7) * 100, 1),
        ];
    }

    /**
     * Рассчитать общее количество рабочих часов в неделю
     */
    private function calculateTotalWorkingHours($schedules): float
    {
        $totalWorkingHours = 0;
        
        foreach ($schedules as $schedule) {
            $workingHours = $this->calculateDayWorkingHours($schedule);
            $totalWorkingHours += $workingHours;
        }
        
        return $totalWorkingHours;
    }

    /**
     * Рассчитать рабочие часы в день
     */
    private function calculateDayWorkingHours($schedule): float
    {
        $start = Carbon::createFromFormat('H:i', $schedule->start_time);
        $end = Carbon::createFromFormat('H:i', $schedule->end_time);
        $workingHours = $end->diffInMinutes($start) / 60;
        
        // Вычитаем время перерыва
        if ($schedule->break_start && $schedule->break_end) {
            $breakStart = Carbon::createFromFormat('H:i', $schedule->break_start);
            $breakEnd = Carbon::createFromFormat('H:i', $schedule->break_end);
            $breakHours = $breakEnd->diffInMinutes($breakStart) / 60;
            $workingHours -= $breakHours;
        }
        
        return $workingHours;
    }

    /**
     * Рассчитать эффективность расписания
     */
    private function calculateEfficiency($schedules): array
    {
        if ($schedules->isEmpty()) {
            return [
                'score' => 0,
                'rating' => 'Нет расписания',
                'recommendations' => ['Добавьте рабочие дни в расписание']
            ];
        }

        $score = 0;
        $recommendations = [];
        
        // Проверка покрытия недели
        $workingDaysCount = $schedules->count();
        if ($workingDaysCount >= 6) {
            $score += 30;
        } elseif ($workingDaysCount >= 4) {
            $score += 20;
        } else {
            $recommendations[] = 'Увеличьте количество рабочих дней';
        }
        
        // Проверка продолжительности рабочего дня
        $totalHours = $this->calculateTotalWorkingHours($schedules);
        $avgHoursPerDay = $totalHours / $workingDaysCount;
        
        if ($avgHoursPerDay >= 8) {
            $score += 25;
        } elseif ($avgHoursPerDay >= 6) {
            $score += 15;
        } else {
            $recommendations[] = 'Увеличьте продолжительность рабочего дня';
        }
        
        // Проверка наличия перерывов
        $hasBreaks = $schedules->whereNotNull('break_start')->count() > 0;
        if ($hasBreaks) {
            $score += 15;
        } else {
            $recommendations[] = 'Добавьте перерывы для лучшей организации дня';
        }
        
        // Проверка равномерности распределения
        $dayNumbers = $schedules->pluck('day_of_week')->sort()->values();
        $isEvenlyDistributed = $this->checkEvenDistribution($dayNumbers->toArray());
        
        if ($isEvenlyDistributed) {
            $score += 15;
        } else {
            $recommendations[] = 'Распределите рабочие дни более равномерно по неделе';
        }
        
        // Проверка выходных
        $hasWeekend = $dayNumbers->contains(6) || $dayNumbers->contains(7);
        if ($hasWeekend) {
            $score += 15;
        } else {
            $recommendations[] = 'Рассмотрите возможность работы в выходные для большей доступности';
        }

        return [
            'score' => min($score, 100),
            'rating' => $this->getEfficiencyRating($score),
            'recommendations' => empty($recommendations) ? ['Отличное расписание!'] : $recommendations
        ];
    }

    /**
     * Проверить равномерность распределения дней
     */
    private function checkEvenDistribution(array $dayNumbers): bool
    {
        if (count($dayNumbers) < 3) {
            return false;
        }
        
        // Проверяем, нет ли больших промежутков между рабочими днями
        for ($i = 1; $i < count($dayNumbers); $i++) {
            if ($dayNumbers[$i] - $dayNumbers[$i-1] > 2) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Получить рейтинг эффективности
     */
    private function getEfficiencyRating(int $score): string
    {
        return match(true) {
            $score >= 90 => 'Отлично',
            $score >= 70 => 'Хорошо',
            $score >= 50 => 'Удовлетворительно',
            $score >= 30 => 'Требует улучшения',
            default => 'Плохо',
        };
    }

    /**
     * Получить детальную аналитику по дням недели
     */
    public function getDayAnalytics(int $masterId): array
    {
        $schedules = $this->scheduleRepository->getByMasterId($masterId, false);
        $dayNames = [
            1 => 'Понедельник', 2 => 'Вторник', 3 => 'Среда', 4 => 'Четверг',
            5 => 'Пятница', 6 => 'Суббота', 7 => 'Воскресенье'
        ];
        
        $analytics = [];
        
        for ($day = 1; $day <= 7; $day++) {
            $schedule = $schedules->where('day_of_week', $day)->first();
            
            $analytics[$day] = [
                'day_name' => $dayNames[$day],
                'is_working' => (bool) $schedule?->is_active,
                'working_hours' => $schedule ? $this->calculateDayWorkingHours($schedule) : 0,
                'has_break' => $schedule && $schedule->break_start ? true : false,
                'schedule_details' => $schedule ? [
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'break_start' => $schedule->break_start,
                    'break_end' => $schedule->break_end,
                    'notes' => $schedule->notes,
                ] : null
            ];
        }
        
        return $analytics;
    }
}