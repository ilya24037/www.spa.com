<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterSubscription;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;

/**
 * Сервис аналитики и статистики подписок
 */
class SubscriptionAnalytics
{
    /**
     * Получить общую статистику подписок
     */
    public function getOverallStatistics(): array
    {
        return [
            'total' => MasterSubscription::count(),
            'active' => MasterSubscription::active()->count(),
            'trial' => MasterSubscription::where('status', SubscriptionStatus::TRIAL)->count(),
            'expired' => MasterSubscription::expired()->count(),
            'cancelled' => MasterSubscription::where('status', SubscriptionStatus::CANCELLED)->count(),
            'revenue' => $this->getRevenueStatistics(),
            'by_plan' => $this->getSubscriptionsByPlan(),
            'churn_rate' => $this->calculateChurnRate(),
            'average_lifetime_value' => $this->calculateAverageLTV(),
            'growth_rate' => $this->calculateGrowthRate(),
        ];
    }

    /**
     * Получить статистику доходов
     */
    public function getRevenueStatistics(): array
    {
        return [
            'total' => MasterSubscription::where('status', SubscriptionStatus::ACTIVE)->sum('price'),
            'monthly' => MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
                ->where('created_at', '>', now()->subMonth())
                ->sum('price'),
            'this_year' => MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
                ->whereYear('created_at', now()->year)
                ->sum('price'),
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateARR(),
        ];
    }

    /**
     * Получить распределение по планам
     */
    public function getSubscriptionsByPlan(): array
    {
        return SubscriptionPlan::getAllPlans()
            ->mapWithKeys(fn($plan) => [
                $plan->value => [
                    'count' => MasterSubscription::where('plan', $plan)->count(),
                    'active' => MasterSubscription::where('plan', $plan)
                        ->where('status', SubscriptionStatus::ACTIVE)
                        ->count(),
                    'revenue' => MasterSubscription::where('plan', $plan)
                        ->where('status', SubscriptionStatus::ACTIVE)
                        ->sum('price'),
                ]
            ])
            ->toArray();
    }

    /**
     * Получить аналитику конверсии
     */
    public function getConversionAnalytics(): array
    {
        $totalTrials = MasterSubscription::where('status', SubscriptionStatus::TRIAL)->count();
        $convertedTrials = MasterSubscription::whereNotNull('trial_ends_at')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        return [
            'trial_to_paid_rate' => $totalTrials > 0 ? round(($convertedTrials / $totalTrials) * 100, 2) : 0,
            'total_trials' => $totalTrials,
            'converted_trials' => $convertedTrials,
            'active_trials' => MasterSubscription::where('status', SubscriptionStatus::TRIAL)
                ->where('trial_ends_at', '>', now())
                ->count(),
            'expired_trials' => MasterSubscription::where('status', SubscriptionStatus::TRIAL)
                ->where('trial_ends_at', '<=', now())
                ->count(),
        ];
    }

    /**
     * Получить когортный анализ
     */
    public function getCohortAnalysis(int $months = 12): array
    {
        $cohorts = [];
        
        for ($i = 0; $i < $months; $i++) {
            $cohortDate = now()->subMonths($i)->startOfMonth();
            $cohortSubscriptions = MasterSubscription::whereMonth('created_at', $cohortDate->month)
                ->whereYear('created_at', $cohortDate->year)
                ->pluck('id');

            if ($cohortSubscriptions->isEmpty()) {
                continue;
            }

            $cohorts[$cohortDate->format('Y-m')] = [
                'cohort_size' => $cohortSubscriptions->count(),
                'retention' => $this->calculateCohortRetention($cohortSubscriptions, $cohortDate),
            ];
        }

        return $cohorts;
    }

    /**
     * Получить прогноз доходов
     */
    public function getRevenueForecast(int $months = 6): array
    {
        $historicalData = $this->getHistoricalMRR(12);
        $averageGrowth = $this->calculateAverageGrowthRate($historicalData);
        
        $forecast = [];
        $currentMRR = $this->calculateMRR();
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastDate = now()->addMonths($i);
            $forecastMRR = $currentMRR * pow(1 + ($averageGrowth / 100), $i);
            
            $forecast[$forecastDate->format('Y-m')] = [
                'mrr' => round($forecastMRR, 2),
                'confidence' => max(50, 90 - ($i * 10)), // Снижается с течением времени
            ];
        }

        return [
            'forecast' => $forecast,
            'average_growth_rate' => $averageGrowth,
            'current_mrr' => $currentMRR,
        ];
    }

    /**
     * Рассчитать churn rate (отток клиентов)
     */
    private function calculateChurnRate(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $startCount = MasterSubscription::where('created_at', '<', $startOfMonth)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();

        if ($startCount === 0) {
            return 0;
        }

        $cancelledCount = MasterSubscription::whereBetween('cancelled_at', [$startOfMonth, $endOfMonth])
            ->count();

        return round(($cancelledCount / $startCount) * 100, 2);
    }

    /**
     * Рассчитать средний LTV (lifetime value)
     */
    private function calculateAverageLTV(): float
    {
        $avgMonths = MasterSubscription::whereIn('status', [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::EXPIRED,
                SubscriptionStatus::CANCELLED,
            ])
            ->selectRaw('AVG(TIMESTAMPDIFF(MONTH, start_date, COALESCE(cancelled_at, end_date, NOW()))) as avg_months')
            ->value('avg_months') ?? 0;

        $avgPrice = MasterSubscription::whereIn('status', [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::EXPIRED,
                SubscriptionStatus::CANCELLED,
            ])
            ->avg('price') ?? 0;

        return round($avgMonths * $avgPrice, 2);
    }

    /**
     * Рассчитать MRR (Monthly Recurring Revenue)
     */
    private function calculateMRR(): float
    {
        return MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
            ->selectRaw('SUM(price / period_months) as mrr')
            ->value('mrr') ?? 0;
    }

    /**
     * Рассчитать ARR (Annual Recurring Revenue)
     */
    private function calculateARR(): float
    {
        return $this->calculateMRR() * 12;
    }

    /**
     * Рассчитать темп роста
     */
    private function calculateGrowthRate(): float
    {
        $thisMonth = MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereMonth('created_at', now()->month)
            ->count();

        $lastMonth = MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100 : 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Получить историю MRR
     */
    private function getHistoricalMRR(int $months): array
    {
        $data = [];
        
        for ($i = $months; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            
            $mrr = MasterSubscription::where('status', SubscriptionStatus::ACTIVE)
                ->where('created_at', '<=', $date->endOfMonth())
                ->selectRaw('SUM(price / period_months) as mrr')
                ->value('mrr') ?? 0;
            
            $data[$date->format('Y-m')] = $mrr;
        }

        return $data;
    }

    /**
     * Рассчитать средний темп роста
     */
    private function calculateAverageGrowthRate(array $historicalData): float
    {
        if (count($historicalData) < 2) {
            return 0;
        }

        $values = array_values($historicalData);
        $growthRates = [];

        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = (($values[$i] - $values[$i - 1]) / $values[$i - 1]) * 100;
            }
        }

        return count($growthRates) > 0 ? round(array_sum($growthRates) / count($growthRates), 2) : 0;
    }

    /**
     * Рассчитать удержание когорты
     */
    private function calculateCohortRetention($cohortSubscriptionIds, $cohortDate): array
    {
        $retention = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $checkDate = $cohortDate->copy()->addMonths($month);
            
            $activeCount = MasterSubscription::whereIn('id', $cohortSubscriptionIds)
                ->where(function($query) use ($checkDate) {
                    $query->where('status', SubscriptionStatus::ACTIVE)
                          ->orWhere('cancelled_at', '>', $checkDate)
                          ->orWhereNull('cancelled_at');
                })
                ->count();
            
            $retention["month_$month"] = round(($activeCount / count($cohortSubscriptionIds)) * 100, 2);
        }

        return $retention;
    }
}