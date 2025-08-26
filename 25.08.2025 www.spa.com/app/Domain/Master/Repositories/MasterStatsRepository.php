<?php

namespace App\Domain\Master\Repositories;

use App\Domain\Master\Models\MasterProfile;
use App\Enums\MasterLevel;
use Illuminate\Support\Facades\DB;

/**
 * Репозиторий для статистики и аналитики мастеров
 */
class MasterStatsRepository
{
    private MasterProfile $model;

    public function __construct()
    {
        $this->model = new MasterProfile();
    }

    /**
     * Получить статистику мастера
     */
    public function getMasterStats(int $masterId): array
    {
        $master = $this->model->find($masterId);
        
        if (!$master) {
            return [];
        }

        return [
            'total_bookings' => $master->bookings()->count(),
            'completed_bookings' => $master->bookings()
                ->where('status', 'completed')->count(),
            'total_revenue' => $master->bookings()
                ->where('status', 'completed')->sum('total_price'),
            'average_rating' => $master->user ? $master->user->getAverageRating() : 0,
            'total_reviews' => $master->user ? $master->user->getReceivedReviewsCount() : 0,
            'profile_views' => $master->views_count,
            'repeat_clients' => $this->getRepeatClientsCount($masterId),
            'services_count' => $master->services()->count(),
            'photos_count' => $master->photos()->count(),
        ];
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(MasterProfile $master): void
    {
        $master->increment('views_count');
    }

    /**
     * Обновить рейтинг мастера
     */
    public function updateRating(MasterProfile $master): void
    {
        $avgRating = $master->user ? $master->user->getAverageRating() : 0;
        $reviewsCount = $master->user ? $master->user->getReceivedReviewsCount() : 0;
        
        $master->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }

    /**
     * Обновить уровень мастера
     */
    public function updateLevel(MasterProfile $master): void
    {
        $level = MasterLevel::determineLevel(
            $master->experience_years,
            $master->rating,
            $master->reviews_count
        );

        if ($master->level !== $level) {
            $master->update(['level' => $level]);
        }
    }

    /**
     * Получить количество постоянных клиентов
     */
    public function getRepeatClientsCount(int $masterId): int
    {
        return DB::table('bookings')
            ->where('master_id', $masterId)
            ->where('status', 'completed')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    /**
     * Получить детальную статистику по бронированиям
     */
    public function getBookingStats(int $masterId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $stats = DB::table('bookings')
            ->where('master_id', $masterId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_bookings,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_bookings,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_bookings,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_bookings,
                SUM(CASE WHEN status = "completed" THEN total_price ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = "completed" THEN total_price ELSE NULL END) as avg_booking_price
            ')
            ->first();

        return [
            'period_days' => $days,
            'total_bookings' => $stats->total_bookings ?? 0,
            'completed_bookings' => $stats->completed_bookings ?? 0,
            'cancelled_bookings' => $stats->cancelled_bookings ?? 0,
            'pending_bookings' => $stats->pending_bookings ?? 0,
            'total_revenue' => $stats->total_revenue ?? 0,
            'avg_booking_price' => round($stats->avg_booking_price ?? 0, 2),
            'completion_rate' => $stats->total_bookings > 0 
                ? round(($stats->completed_bookings / $stats->total_bookings) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Получить топ мастеров по доходам
     */
    public function getTopMastersByRevenue(int $limit = 10, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return DB::table('bookings')
            ->join('master_profiles', 'bookings.master_id', '=', 'master_profiles.id')
            ->join('users', 'master_profiles.user_id', '=', 'users.id')
            ->where('bookings.status', 'completed')
            ->where('bookings.created_at', '>=', $startDate)
            ->select([
                'master_profiles.id',
                'master_profiles.display_name',
                'users.name as user_name',
                DB::raw('SUM(bookings.total_price) as total_revenue'),
                DB::raw('COUNT(bookings.id) as completed_bookings'),
                DB::raw('AVG(bookings.total_price) as avg_booking_price')
            ])
            ->groupBy('master_profiles.id', 'master_profiles.display_name', 'users.name')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Получить статистику просмотров по периодам
     */
    public function getViewsStats(int $masterId, int $days = 30): array
    {
        // Если есть таблица для отслеживания просмотров по датам
        // В противном случае возвращаем текущий счетчик
        $master = $this->model->find($masterId);
        
        return [
            'total_views' => $master ? $master->views_count : 0,
            'period_days' => $days,
            // TODO: Добавить daily_views если есть отдельная таблица статистики
        ];
    }

    /**
     * Получить конверсию профиля
     */
    public function getProfileConversion(int $masterId, int $days = 30): array
    {
        $master = $this->model->find($masterId);
        if (!$master) {
            return [];
        }

        $bookingStats = $this->getBookingStats($masterId, $days);
        $views = $master->views_count; // За весь период

        $conversion = $views > 0 ? ($bookingStats['total_bookings'] / $views) * 100 : 0;

        return [
            'total_views' => $views,
            'total_bookings' => $bookingStats['total_bookings'],
            'conversion_rate' => round($conversion, 2),
            'period_days' => $days,
        ];
    }

    /**
     * Получить статистику по уровням мастеров
     */
    public function getLevelStats(): array
    {
        return $this->model->selectRaw('
                level,
                COUNT(*) as count,
                AVG(rating) as avg_rating,
                AVG(experience_years) as avg_experience
            ')
            ->groupBy('level')
            ->orderBy('level')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->level => [
                    'count' => $item->count,
                    'avg_rating' => round($item->avg_rating, 2),
                    'avg_experience' => round($item->avg_experience, 1),
                ]];
            })
            ->toArray();
    }

    /**
     * Получить рейтинговую статистику
     */
    public function getRatingDistribution(): array
    {
        return $this->model->selectRaw('
                CASE 
                    WHEN rating >= 4.5 THEN "excellent"
                    WHEN rating >= 4.0 THEN "good" 
                    WHEN rating >= 3.5 THEN "average"
                    WHEN rating >= 3.0 THEN "below_average"
                    ELSE "poor"
                END as rating_category,
                COUNT(*) as count
            ')
            ->groupBy(DB::raw('CASE 
                WHEN rating >= 4.5 THEN "excellent"
                WHEN rating >= 4.0 THEN "good" 
                WHEN rating >= 3.5 THEN "average"
                WHEN rating >= 3.0 THEN "below_average"
                ELSE "poor"
            END'))
            ->pluck('count', 'rating_category')
            ->toArray();
    }
}