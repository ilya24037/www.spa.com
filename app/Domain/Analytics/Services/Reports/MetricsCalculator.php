<?php

namespace App\Domain\Analytics\Services\Reports;

use App\Domain\Analytics\Models\UserAction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Калькулятор метрик и математических расчетов для отчетов
 */
class MetricsCalculator
{
    /**
     * Рассчитать процентное изменение
     */
    public function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Рассчитать медиану
     */
    public function calculateMedian(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0;
        }

        $middle = intval($count / 2);

        if ($count % 2 === 0) {
            return ($sorted[$middle - 1] + $sorted[$middle]) / 2;
        }

        return $sorted[$middle];
    }

    /**
     * Рассчитать темп роста
     */
    public function calculateGrowthRate(Carbon $from, Carbon $to): float
    {
        $days = $from->diffInDays($to) + 1;
        $previousPeriodFrom = $from->copy()->subDays($days);
        $previousPeriodTo = $from->copy()->subDay();

        $currentPeriodUsers = UserAction::inPeriod($from, $to)
            ->byActionType(UserAction::ACTION_REGISTER)
            ->count();

        $previousPeriodUsers = UserAction::inPeriod($previousPeriodFrom, $previousPeriodTo)
            ->byActionType(UserAction::ACTION_REGISTER)
            ->count();

        return $this->calculatePercentageChange($previousPeriodUsers, $currentPeriodUsers);
    }

    /**
     * Получить ежедневную статистику из коллекции
     */
    public function getDailyStats(Collection $items, Carbon $from, Carbon $to): array
    {
        $daily = [];
        
        $period = $from->copy();
        while ($period->lte($to)) {
            $date = $period->format('Y-m-d');
            $dayItems = $items->where('viewed_at', '>=', $period->startOfDay())
                             ->where('viewed_at', '<=', $period->endOfDay());
            
            $daily[$date] = [
                'count' => $dayItems->count(),
                'unique_count' => $dayItems->unique('ip_address')->count(),
            ];
            
            $period->addDay();
        }

        return $daily;
    }

    /**
     * Рассчитать статистические показатели для коллекции
     */
    public function calculateBasicStats(Collection $values): array
    {
        if ($values->isEmpty()) {
            return [
                'count' => 0,
                'sum' => 0,
                'average' => 0,
                'median' => 0,
                'min' => 0,
                'max' => 0,
                'std_deviation' => 0,
            ];
        }

        $count = $values->count();
        $sum = $values->sum();
        $average = $sum / $count;
        
        return [
            'count' => $count,
            'sum' => $sum,
            'average' => round($average, 2),
            'median' => $this->calculateMedian($values),
            'min' => $values->min(),
            'max' => $values->max(),
            'std_deviation' => $this->calculateStandardDeviation($values, $average),
        ];
    }

    /**
     * Рассчитать стандартное отклонение
     */
    public function calculateStandardDeviation(Collection $values, ?float $mean = null): float
    {
        if ($values->isEmpty()) {
            return 0;
        }

        $mean = $mean ?? $values->avg();
        $variance = $values->reduce(function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / $values->count();

        return round(sqrt($variance), 2);
    }

    /**
     * Рассчитать коэффициент корреляции между двумя наборами данных
     */
    public function calculateCorrelation(Collection $x, Collection $y): float
    {
        if ($x->count() !== $y->count() || $x->isEmpty()) {
            return 0;
        }

        $n = $x->count();
        $meanX = $x->avg();
        $meanY = $y->avg();

        $numerator = 0;
        $sumXSquared = 0;
        $sumYSquared = 0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $x[$i] - $meanX;
            $diffY = $y[$i] - $meanY;
            
            $numerator += $diffX * $diffY;
            $sumXSquared += $diffX * $diffX;
            $sumYSquared += $diffY * $diffY;
        }

        $denominator = sqrt($sumXSquared * $sumYSquared);
        
        return $denominator != 0 ? round($numerator / $denominator, 3) : 0;
    }

    /**
     * Рассчитать процентили
     */
    public function calculatePercentiles(Collection $values, array $percentiles = [25, 50, 75, 95]): array
    {
        if ($values->isEmpty()) {
            return array_fill_keys($percentiles, 0);
        }

        $sorted = $values->sort()->values();
        $count = $sorted->count();
        $result = [];

        foreach ($percentiles as $percentile) {
            $index = ($percentile / 100) * ($count - 1);
            
            if (is_int($index)) {
                $result[$percentile] = $sorted[$index];
            } else {
                $lower = floor($index);
                $upper = ceil($index);
                $weight = $index - $lower;
                
                $result[$percentile] = $sorted[$lower] + $weight * ($sorted[$upper] - $sorted[$lower]);
            }
        }

        return $result;
    }

    /**
     * Рассчитать тренд (возрастающий, убывающий, стабильный)
     */
    public function calculateTrend(Collection $values): array
    {
        if ($values->count() < 2) {
            return [
                'direction' => 'stable',
                'strength' => 0,
                'slope' => 0,
            ];
        }

        $n = $values->count();
        $x = collect(range(0, $n - 1));
        $y = $values->values();

        // Простая линейная регрессия
        $meanX = $x->avg();
        $meanY = $y->avg();

        $numerator = 0;
        $denominator = 0;

        for ($i = 0; $i < $n; $i++) {
            $diffX = $x[$i] - $meanX;
            $numerator += $diffX * ($y[$i] - $meanY);
            $denominator += $diffX * $diffX;
        }

        $slope = $denominator != 0 ? $numerator / $denominator : 0;
        
        $direction = 'stable';
        if ($slope > 0.1) {
            $direction = 'increasing';
        } elseif ($slope < -0.1) {
            $direction = 'decreasing';
        }

        return [
            'direction' => $direction,
            'strength' => round(abs($slope), 3),
            'slope' => round($slope, 3),
        ];
    }

    /**
     * Рассчитать сезонность данных
     */
    public function detectSeasonality(Collection $dailyValues, int $period = 7): array
    {
        if ($dailyValues->count() < $period * 2) {
            return [
                'has_seasonality' => false,
                'seasonal_strength' => 0,
                'peak_day' => null,
            ];
        }

        $weeklyAverages = [];
        $chunks = $dailyValues->chunk($period);
        
        foreach ($chunks as $week) {
            if ($week->count() === $period) {
                foreach ($week->values() as $dayIndex => $value) {
                    $weeklyAverages[$dayIndex] = ($weeklyAverages[$dayIndex] ?? []);
                    $weeklyAverages[$dayIndex][] = $value;
                }
            }
        }

        $dayAverages = [];
        foreach ($weeklyAverages as $dayIndex => $values) {
            $dayAverages[$dayIndex] = collect($values)->avg();
        }

        if (empty($dayAverages)) {
            return [
                'has_seasonality' => false,
                'seasonal_strength' => 0,
                'peak_day' => null,
            ];
        }

        $dayAveragesCollection = collect($dayAverages);
        $variance = $this->calculateStandardDeviation($dayAveragesCollection);
        $mean = $dayAveragesCollection->avg();
        
        $seasonalStrength = $mean > 0 ? $variance / $mean : 0;
        $peakDay = $dayAveragesCollection->search($dayAveragesCollection->max());

        return [
            'has_seasonality' => $seasonalStrength > 0.2,
            'seasonal_strength' => round($seasonalStrength, 3),
            'peak_day' => $peakDay,
            'day_averages' => $dayAverages,
        ];
    }
}