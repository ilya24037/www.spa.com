<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Сервис статистики пользователей
 */
class UserStatsService
{
    /**
     * Получить общую статистику пользователей
     */
    public function getGeneralStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('status', UserStatus::ACTIVE)->count(),
            'blocked' => User::where('status', UserStatus::BLOCKED)->count(),
            'pending' => User::where('status', UserStatus::PENDING)->count(),
            'by_role' => $this->getStatsByRole(),
            'this_month' => User::whereMonth('created_at', now()->month)->count(),
            'today' => User::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Получить статистику регистраций
     */
    public function getRegistrationStats(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $dailyStats = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Заполняем пропущенные дни нулями
        $period = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - 1 - $i)->format('Y-m-d');
            $period[$date] = $dailyStats[$date] ?? 0;
        }

        return [
            'period_days' => $days,
            'total_registrations' => array_sum($period->toArray()),
            'daily_average' => round(array_sum($period->toArray()) / $days, 2),
            'daily_stats' => $period->toArray(),
            'peak_day' => $this->getPeakRegistrationDay($period->toArray()),
        ];
    }

    /**
     * Получить статистику по ролям
     */
    public function getStatsByRole(): array
    {
        return User::selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->map(function($count, $role) {
                return [
                    'count' => $count,
                    'label' => UserRole::from($role)->getLabel(),
                ];
            })
            ->toArray();
    }

    /**
     * Получить статистику по статусам
     */
    public function getStatsByStatus(): array
    {
        return User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(function($count, $status) {
                return [
                    'count' => $count,
                    'label' => UserStatus::from($status)->getLabel(),
                ];
            })
            ->toArray();
    }

    /**
     * Получить статистику активности пользователей
     */
    public function getActivityStats(): array
    {
        $now = now();
        
        return [
            'online_now' => $this->getOnlineUsersCount(),
            'active_today' => User::where('last_activity', '>=', $now->copy()->startOfDay())->count(),
            'active_week' => User::where('last_activity', '>=', $now->copy()->subWeek())->count(),
            'active_month' => User::where('last_activity', '>=', $now->copy()->subMonth())->count(),
            'never_logged' => User::whereNull('last_activity')->count(),
        ];
    }

    /**
     * Получить демографическую статистику
     */
    public function getDemographicStats(): array
    {
        return [
            'by_city' => $this->getStatsByCity(),
            'by_age_group' => $this->getStatsByAgeGroup(),
            'by_registration_source' => $this->getStatsByRegistrationSource(),
        ];
    }

    /**
     * Получить количество онлайн пользователей
     */
    private function getOnlineUsersCount(): int
    {
        $onlineThreshold = now()->subMinutes(15);
        return User::where('last_activity', '>=', $onlineThreshold)->count();
    }

    /**
     * Получить пиковый день регистраций
     */
    private function getPeakRegistrationDay(array $dailyStats): array
    {
        $maxCount = max($dailyStats);
        $peakDate = array_search($maxCount, $dailyStats);
        
        return [
            'date' => $peakDate,
            'count' => $maxCount,
            'formatted_date' => Carbon::parse($peakDate)->format('d.m.Y'),
        ];
    }

    /**
     * Получить статистику по городам
     */
    private function getStatsByCity(): array
    {
        return DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->whereNotNull('user_profiles.city')
            ->selectRaw('user_profiles.city, COUNT(*) as count')
            ->groupBy('user_profiles.city')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'city')
            ->toArray();
    }

    /**
     * Получить статистику по возрастным группам
     */
    private function getStatsByAgeGroup(): array
    {
        $ageGroups = [
            '18-25' => [18, 25],
            '26-35' => [26, 35],
            '36-45' => [36, 45],
            '46-55' => [46, 55],
            '55+' => [56, 100],
        ];

        $stats = [];
        foreach ($ageGroups as $group => $range) {
            $startDate = now()->subYears($range[1])->startOfYear();
            $endDate = now()->subYears($range[0])->endOfYear();
            
            $stats[$group] = DB::table('users')
                ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->whereBetween('user_profiles.birth_date', [$startDate, $endDate])
                ->count();
        }

        return $stats;
    }

    /**
     * Получить статистику по источникам регистрации
     */
    private function getStatsByRegistrationSource(): array
    {
        return User::selectRaw('registration_source, COUNT(*) as count')
            ->whereNotNull('registration_source')
            ->groupBy('registration_source')
            ->orderByDesc('count')
            ->pluck('count', 'registration_source')
            ->toArray();
    }
}