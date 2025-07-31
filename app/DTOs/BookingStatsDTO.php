<?php

namespace App\DTOs;

/**
 * DTO для статистики бронирований
 */
class BookingStatsDTO
{
    public function __construct(
        public readonly int $total_bookings = 0,
        public readonly int $pending_bookings = 0,
        public readonly int $confirmed_bookings = 0,
        public readonly int $in_progress_bookings = 0,
        public readonly int $completed_bookings = 0,
        public readonly int $cancelled_bookings = 0,
        public readonly int $active_bookings = 0,
        public readonly float $total_revenue = 0.0,
        public readonly float $average_booking_value = 0.0,
        public readonly float $conversion_rate = 0.0,
        public readonly float $cancellation_rate = 0.0,
        public readonly array $revenue_by_month = [],
        public readonly array $bookings_by_status = [],
        public readonly array $bookings_by_type = [],
        public readonly array $popular_services = [],
        public readonly array $top_clients = [],
        public readonly array $daily_stats = [],
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            total_bookings: $data['total_bookings'] ?? 0,
            pending_bookings: $data['pending_bookings'] ?? 0,
            confirmed_bookings: $data['confirmed_bookings'] ?? 0,
            in_progress_bookings: $data['in_progress_bookings'] ?? 0,
            completed_bookings: $data['completed_bookings'] ?? 0,
            cancelled_bookings: $data['cancelled_bookings'] ?? 0,
            active_bookings: $data['active_bookings'] ?? 0,
            total_revenue: (float) ($data['total_revenue'] ?? 0),
            average_booking_value: (float) ($data['average_booking_value'] ?? 0),
            conversion_rate: (float) ($data['conversion_rate'] ?? 0),
            cancellation_rate: (float) ($data['cancellation_rate'] ?? 0),
            revenue_by_month: $data['revenue_by_month'] ?? [],
            bookings_by_status: $data['bookings_by_status'] ?? [],
            bookings_by_type: $data['bookings_by_type'] ?? [],
            popular_services: $data['popular_services'] ?? [],
            top_clients: $data['top_clients'] ?? [],
            daily_stats: $data['daily_stats'] ?? [],
        );
    }

    /**
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        return [
            'total_bookings' => $this->total_bookings,
            'pending_bookings' => $this->pending_bookings,
            'confirmed_bookings' => $this->confirmed_bookings,
            'in_progress_bookings' => $this->in_progress_bookings,
            'completed_bookings' => $this->completed_bookings,
            'cancelled_bookings' => $this->cancelled_bookings,
            'active_bookings' => $this->active_bookings,
            'total_revenue' => $this->total_revenue,
            'average_booking_value' => $this->average_booking_value,
            'conversion_rate' => $this->conversion_rate,
            'cancellation_rate' => $this->cancellation_rate,
            'revenue_by_month' => $this->revenue_by_month,
            'bookings_by_status' => $this->bookings_by_status,
            'bookings_by_type' => $this->bookings_by_type,
            'popular_services' => $this->popular_services,
            'top_clients' => $this->top_clients,
            'daily_stats' => $this->daily_stats,
        ];
    }

    /**
     * Получить основные метрики
     */
    public function getMainMetrics(): array
    {
        return [
            'total_bookings' => $this->total_bookings,
            'completed_bookings' => $this->completed_bookings,
            'total_revenue' => $this->total_revenue,
            'average_booking_value' => $this->average_booking_value,
            'conversion_rate' => $this->conversion_rate,
            'cancellation_rate' => $this->cancellation_rate,
        ];
    }

    /**
     * Получить распределение по статусам
     */
    public function getStatusDistribution(): array
    {
        return [
            'pending' => $this->pending_bookings,
            'confirmed' => $this->confirmed_bookings,
            'in_progress' => $this->in_progress_bookings,
            'completed' => $this->completed_bookings,
            'cancelled' => $this->cancelled_bookings,
        ];
    }

    /**
     * Получить распределение по статусам в процентах
     */
    public function getStatusDistributionPercent(): array
    {
        if ($this->total_bookings === 0) {
            return array_fill_keys(['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'], 0);
        }

        return [
            'pending' => round(($this->pending_bookings / $this->total_bookings) * 100, 1),
            'confirmed' => round(($this->confirmed_bookings / $this->total_bookings) * 100, 1),
            'in_progress' => round(($this->in_progress_bookings / $this->total_bookings) * 100, 1),
            'completed' => round(($this->completed_bookings / $this->total_bookings) * 100, 1),
            'cancelled' => round(($this->cancelled_bookings / $this->total_bookings) * 100, 1),
        ];
    }

    /**
     * Получить успешность бронирований
     */
    public function getSuccessRate(): float
    {
        if ($this->total_bookings === 0) {
            return 0;
        }

        return round(($this->completed_bookings / $this->total_bookings) * 100, 2);
    }

    /**
     * Получить активность (активные бронирования / общее количество)
     */
    public function getActivityRate(): float
    {
        if ($this->total_bookings === 0) {
            return 0;
        }

        return round(($this->active_bookings / $this->total_bookings) * 100, 2);
    }

    /**
     * Получить средний доход в день
     */
    public function getAverageDailyRevenue(): float
    {
        if (empty($this->daily_stats)) {
            return 0;
        }

        $totalRevenue = array_sum(array_column($this->daily_stats, 'revenue'));
        return round($totalRevenue / count($this->daily_stats), 2);
    }

    /**
     * Получить среднее количество бронирований в день
     */
    public function getAverageDailyBookings(): float
    {
        if (empty($this->daily_stats)) {
            return 0;
        }

        $totalBookings = array_sum(array_column($this->daily_stats, 'bookings'));
        return round($totalBookings / count($this->daily_stats), 1);
    }

    /**
     * Получить топ услуги по количеству
     */
    public function getTopServicesByCount(int $limit = 5): array
    {
        return array_slice(
            array_slice($this->popular_services, 0, $limit),
            0,
            $limit
        );
    }

    /**
     * Получить топ услуги по выручке
     */
    public function getTopServicesByRevenue(int $limit = 5): array
    {
        $services = $this->popular_services;
        usort($services, fn($a, $b) => ($b['revenue'] ?? 0) <=> ($a['revenue'] ?? 0));
        
        return array_slice($services, 0, $limit);
    }

    /**
     * Получить топ клиентов
     */
    public function getTopClients(int $limit = 5): array
    {
        return array_slice($this->top_clients, 0, $limit);
    }

    /**
     * Получить тренд по месяцам
     */
    public function getMonthlyTrend(): array
    {
        if (count($this->revenue_by_month) < 2) {
            return ['trend' => 'stable', 'growth' => 0];
        }

        $months = array_values($this->revenue_by_month);
        $lastMonth = end($months);
        $prevMonth = prev($months);

        if ($prevMonth == 0) {
            $growth = $lastMonth > 0 ? 100 : 0;
        } else {
            $growth = round((($lastMonth - $prevMonth) / $prevMonth) * 100, 1);
        }

        $trend = $growth > 10 ? 'growing' : ($growth < -10 ? 'declining' : 'stable');

        return [
            'trend' => $trend,
            'growth' => $growth,
            'last_month' => $lastMonth,
            'prev_month' => $prevMonth,
        ];
    }

    /**
     * Получить производительность (KPI)
     */
    public function getKPIs(): array
    {
        return [
            'total_bookings' => [
                'value' => $this->total_bookings,
                'label' => 'Всего бронирований',
                'type' => 'number',
            ],
            'conversion_rate' => [
                'value' => $this->conversion_rate,
                'label' => 'Конверсия',
                'type' => 'percent',
            ],
            'total_revenue' => [
                'value' => $this->total_revenue,
                'label' => 'Общая выручка',
                'type' => 'currency',
            ],
            'average_booking_value' => [
                'value' => $this->average_booking_value,
                'label' => 'Средний чек',
                'type' => 'currency',
            ],
            'cancellation_rate' => [
                'value' => $this->cancellation_rate,
                'label' => 'Процент отмен',
                'type' => 'percent',
            ],
            'success_rate' => [
                'value' => $this->getSuccessRate(),
                'label' => 'Успешность',
                'type' => 'percent',
            ],
        ];
    }

    /**
     * Получить краткое резюме
     */
    public function getSummary(): string
    {
        $summary = "За период: {$this->total_bookings} бронирований на сумму " . number_format($this->total_revenue, 0, ',', ' ') . " руб.";
        
        if ($this->completed_bookings > 0) {
            $summary .= " Выполнено: {$this->completed_bookings} ({$this->getSuccessRate()}%).";
        }
        
        if ($this->cancellation_rate > 0) {
            $summary .= " Отменено: {$this->cancellation_rate}%.";
        }

        return $summary;
    }

    /**
     * Проверить есть ли данные
     */
    public function hasData(): bool
    {
        return $this->total_bookings > 0;
    }

    /**
     * Проверить есть ли выручка
     */
    public function hasRevenue(): bool
    {
        return $this->total_revenue > 0;
    }

    /**
     * Получить цветовую схему для графиков
     */
    public function getChartColors(): array
    {
        return [
            'pending' => '#F59E0B',     // amber
            'confirmed' => '#3B82F6',   // blue
            'in_progress' => '#8B5CF6', // violet
            'completed' => '#10B981',   // green
            'cancelled' => '#EF4444',   // red
        ];
    }
}