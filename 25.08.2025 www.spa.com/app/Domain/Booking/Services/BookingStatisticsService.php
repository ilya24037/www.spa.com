<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Repositories\BookingRepository;
use App\Domain\User\Models\User;
use Carbon\Carbon;

/**
 * Сервис статистики бронирований
 */
class BookingStatisticsService
{
    public function __construct(
        private BookingRepository $bookingRepository
    ) {}

    /**
     * Получить статистику бронирований
     */
    public function getBookingStats(User $user, string $period = 'month'): array
    {
        $startDate = $this->getStartDateForPeriod($period);
        
        $stats = [
            'total' => $this->bookingRepository->countForUserInPeriod($user, $startDate),
            'completed' => $this->bookingRepository->countByStatusForUserInPeriod(
                $user, 'completed', $startDate
            ),
            'cancelled' => $this->bookingRepository->countByStatusForUserInPeriod(
                $user, 'cancelled', $startDate
            ),
            'upcoming' => $this->bookingRepository->countUpcomingForUser($user),
            'revenue' => $this->calculateRevenue($user, $startDate),
            'popular_services' => $this->getPopularServices($user, $startDate),
            'peak_hours' => $this->getPeakHours($user, $startDate),
            'client_retention' => $this->getClientRetentionRate($user, $startDate),
        ];

        if ($user->isMaster()) {
            $stats['average_rating'] = $this->bookingRepository->getAverageRatingForMaster(
                $user, $startDate
            );
            $stats['completion_rate'] = $this->calculateCompletionRate($user, $startDate);
        }

        return $stats;
    }

    /**
     * Получить доход за период
     */
    private function calculateRevenue(User $user, Carbon $startDate): float
    {
        return $this->bookingRepository->calculateRevenueForUserInPeriod($user, $startDate);
    }

    /**
     * Получить популярные услуги
     */
    private function getPopularServices(User $user, Carbon $startDate): array
    {
        return $this->bookingRepository->getPopularServicesForUserInPeriod($user, $startDate, 5);
    }

    /**
     * Получить пиковые часы
     */
    private function getPeakHours(User $user, Carbon $startDate): array
    {
        return $this->bookingRepository->getPeakHoursForUserInPeriod($user, $startDate);
    }

    /**
     * Получить процент завершенных бронирований
     */
    private function calculateCompletionRate(User $user, Carbon $startDate): float
    {
        $total = $this->bookingRepository->countForUserInPeriod($user, $startDate);
        $completed = $this->bookingRepository->countByStatusForUserInPeriod(
            $user, 'completed', $startDate
        );
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }

    /**
     * Получить процент удержания клиентов
     */
    private function getClientRetentionRate(User $user, Carbon $startDate): float
    {
        if (!$user->isMaster()) {
            return 0;
        }
        
        return $this->bookingRepository->calculateClientRetentionRate($user, $startDate);
    }

    /**
     * Получить начальную дату для периода
     */
    private function getStartDateForPeriod(string $period): Carbon
    {
        return match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }
}