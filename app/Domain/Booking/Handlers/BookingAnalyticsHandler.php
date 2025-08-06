<?php

namespace App\Domain\Booking\Handlers;

use App\Domain\Booking\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Обработчик аналитики и статистики бронирований
 */
class BookingAnalyticsHandler
{
    public function __construct(
        protected Booking $model
    ) {}

    /**
     * Получить общую статистику бронирований
     */
    public function getStatistics(array $filters = []): array
    {
        $query = $this->model->newQuery();
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('start_time', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('start_time', '<=', $filters['date_to']);
        }

        $stats = $query->select([
            DB::raw('COUNT(*) as total_bookings'),
            DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_bookings'),
            DB::raw('COUNT(CASE WHEN status IN ("cancelled_by_client", "cancelled_by_master", "no_show") THEN 1 END) as cancelled_bookings'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN total_price ELSE 0 END) as total_revenue'),
            DB::raw('AVG(CASE WHEN status = "completed" THEN total_price ELSE NULL END) as average_booking_value'),
            DB::raw('COUNT(CASE WHEN status IN ("pending", "confirmed", "in_progress") THEN 1 END) as active_bookings'),
        ])->first();

        $conversionRate = $stats->total_bookings > 0 
            ? round(($stats->completed_bookings / $stats->total_bookings) * 100, 2) 
            : 0;

        $cancellationRate = $stats->total_bookings > 0 
            ? round(($stats->cancelled_bookings / $stats->total_bookings) * 100, 2) 
            : 0;

        return [
            'total_bookings' => $stats->total_bookings ?? 0,
            'completed_bookings' => $stats->completed_bookings ?? 0,
            'cancelled_bookings' => $stats->cancelled_bookings ?? 0,
            'active_bookings' => $stats->active_bookings ?? 0,
            'total_revenue' => $stats->total_revenue ?? 0,
            'average_booking_value' => $stats->average_booking_value ?? 0,
            'conversion_rate' => $conversionRate,
            'cancellation_rate' => $cancellationRate,
        ];
    }

    /**
     * Получить выручку по месяцам
     */
    public function getRevenueByMonth(int $year, ?int $masterId = null): Collection
    {
        $query = $this->model->whereYear('start_time', $year)
                            ->where('status', 'completed');
        
        if ($masterId) {
            $query->forMaster($masterId);
        }

        return $query->select([
            DB::raw('MONTH(start_time) as month'),
            DB::raw('SUM(total_price) as revenue'),
            DB::raw('COUNT(*) as bookings_count')
        ])
        ->groupBy(DB::raw('MONTH(start_time)'))
        ->orderBy('month')
        ->get();
    }

    /**
     * Получить популярные услуги
     */
    public function getPopularServices(int $limit = 10, ?int $masterId = null): Collection
    {
        $query = $this->model->join('services', 'bookings.service_id', '=', 'services.id')
                            ->where('bookings.status', 'completed');
        
        if ($masterId) {
            $query->where('bookings.master_id', $masterId);
        }

        return $query->select([
            'services.id',
            'services.name',
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('SUM(bookings.total_price) as total_revenue'),
            DB::raw('AVG(bookings.total_price) as average_price')
        ])
        ->groupBy('services.id', 'services.name')
        ->orderByDesc('bookings_count')
        ->limit($limit)
        ->get();
    }

    /**
     * Получить частых клиентов
     */
    public function getFrequentClients(int $limit = 10, ?int $masterId = null): Collection
    {
        $query = $this->model->join('users', 'bookings.client_id', '=', 'users.id')
                            ->where('bookings.status', 'completed');
        
        if ($masterId) {
            $query->where('bookings.master_id', $masterId);
        }

        return $query->select([
            'users.id',
            'users.name',
            'users.email',
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('SUM(bookings.total_price) as total_spent'),
            DB::raw('MAX(bookings.start_time) as last_booking')
        ])
        ->groupBy('users.id', 'users.name', 'users.email')
        ->orderByDesc('bookings_count')
        ->limit($limit)
        ->get();
    }

    /**
     * Получить детализированную аналитику
     */
    public function getDetailedAnalytics(array $filters = []): array
    {
        return [
            'overview' => $this->getStatistics($filters),
            'revenue_trends' => $this->getRevenueTrends($filters),
            'booking_trends' => $this->getBookingTrends($filters),
            'status_distribution' => $this->getStatusDistribution($filters),
            'time_analysis' => $this->getTimeAnalysis($filters),
            'performance_metrics' => $this->getPerformanceMetrics($filters),
        ];
    }

    /**
     * Получить тренды выручки
     */
    public function getRevenueTrends(array $filters = []): array
    {
        $query = $this->model->where('status', 'completed');
        
        $this->applyDateFilters($query, $filters);
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }

        $groupBy = $filters['group_by'] ?? 'day';
        
        switch ($groupBy) {
            case 'hour':
                $selectRaw = 'HOUR(start_time) as period';
                $groupByRaw = 'HOUR(start_time)';
                break;
            case 'day':
                $selectRaw = 'DATE(start_time) as period';
                $groupByRaw = 'DATE(start_time)';
                break;
            case 'week':
                $selectRaw = 'YEARWEEK(start_time) as period';
                $groupByRaw = 'YEARWEEK(start_time)';
                break;
            case 'month':
                $selectRaw = 'DATE_FORMAT(start_time, "%Y-%m") as period';
                $groupByRaw = 'YEAR(start_time), MONTH(start_time)';
                break;
            default:
                $selectRaw = 'DATE(start_time) as period';
                $groupByRaw = 'DATE(start_time)';
        }

        return $query->select([
            DB::raw($selectRaw),
            DB::raw('SUM(total_price) as revenue'),
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('AVG(total_price) as avg_booking_value')
        ])
        ->groupBy(DB::raw($groupByRaw))
        ->orderBy('period')
        ->get()
        ->toArray();
    }

    /**
     * Получить тренды количества бронирований
     */
    public function getBookingTrends(array $filters = []): array
    {
        $query = $this->model->newQuery();
        
        $this->applyDateFilters($query, $filters);
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }

        return $query->select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_bookings'),
            DB::raw('COUNT(CASE WHEN status = "completed" THEN 1 END) as completed'),
            DB::raw('COUNT(CASE WHEN status = "cancelled_by_client" THEN 1 END) as cancelled')
        ])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get()
        ->toArray();
    }

    /**
     * Получить распределение по статусам
     */
    public function getStatusDistribution(array $filters = []): array
    {
        $query = $this->model->newQuery();
        
        $this->applyDateFilters($query, $filters);
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }

        return $query->select([
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM bookings), 2) as percentage')
        ])
        ->groupBy('status')
        ->orderByDesc('count')
        ->get()
        ->toArray();
    }

    /**
     * Анализ времени бронирований
     */
    public function getTimeAnalysis(array $filters = []): array
    {
        $query = $this->model->where('status', 'completed');
        
        $this->applyDateFilters($query, $filters);
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }

        $hourlyData = $query->select([
            DB::raw('HOUR(start_time) as hour'),
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('SUM(total_price) as revenue')
        ])
        ->groupBy(DB::raw('HOUR(start_time)'))
        ->orderBy('hour')
        ->get();

        $weeklyData = $query->select([
            DB::raw('DAYOFWEEK(start_time) as day_of_week'),
            DB::raw('COUNT(*) as bookings_count'),
            DB::raw('SUM(total_price) as revenue')
        ])
        ->groupBy(DB::raw('DAYOFWEEK(start_time)'))
        ->orderBy('day_of_week')
        ->get();

        return [
            'hourly_distribution' => $hourlyData->toArray(),
            'weekly_distribution' => $weeklyData->toArray(),
        ];
    }

    /**
     * Метрики производительности
     */
    public function getPerformanceMetrics(array $filters = []): array
    {
        $query = $this->model->newQuery();
        
        $this->applyDateFilters($query, $filters);
        
        if (isset($filters['master_id'])) {
            $query->forMaster($filters['master_id']);
        }

        $metrics = $query->select([
            DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, start_time)) as avg_booking_lead_time'),
            DB::raw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_service_duration'),
            DB::raw('COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, start_time) <= 24 THEN 1 END) as same_day_bookings'),
            DB::raw('COUNT(CASE WHEN TIMESTAMPDIFF(MINUTE, start_time, end_time) > 60 THEN 1 END) as long_sessions')
        ])->first();

        return [
            'avg_booking_lead_time_hours' => round($metrics->avg_booking_lead_time ?? 0, 2),
            'avg_service_duration_minutes' => round($metrics->avg_service_duration ?? 0, 2),
            'same_day_bookings' => $metrics->same_day_bookings ?? 0,
            'long_sessions_count' => $metrics->long_sessions ?? 0,
        ];
    }

    /**
     * Применить фильтры по дате к запросу
     */
    protected function applyDateFilters($query, array $filters): void
    {
        if (isset($filters['date_from'])) {
            $query->where('start_time', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('start_time', '<=', $filters['date_to']);
        }
    }
}