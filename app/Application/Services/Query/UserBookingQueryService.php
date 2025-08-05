<?php

namespace App\Application\Services\Query;

use App\Domain\Booking\Contracts\BookingQueryInterface;
use App\Domain\User\Contracts\UserQueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Сервис запросов для User-Booking интеграции
 * Реализует CQRS паттерн для чтения междоменных данных
 */
class UserBookingQueryService
{
    public function __construct(
        private BookingQueryInterface $bookingQuery,
        private UserQueryInterface $userQuery
    ) {}

    /**
     * Получить бронирования пользователя с пагинацией и фильтрами
     */
    public function getUserBookingsPaginated(
        int $userId, 
        array $filters = [], 
        int $perPage = 15
    ): LengthAwarePaginator {
        // Добавляем фильтр по пользователю
        $filters['client_id'] = $userId;
        
        return $this->bookingQuery->searchBookings($filters)->paginate($perPage);
    }

    /**
     * Получить активных клиентов с их статистикой бронирований
     */
    public function getActiveClientsWithBookingStats(int $limit = 100): array
    {
        $activeClients = $this->bookingQuery->getActiveClients($limit);
        $result = [];

        foreach ($activeClients as $client) {
            $bookingStats = $this->getClientBookingAnalytics($client->id);
            
            $result[] = [
                'user' => $client,
                'booking_stats' => $bookingStats
            ];
        }

        return $result;
    }

    /**
     * Получить аналитику бронирований клиента
     */
    public function getClientBookingAnalytics(int $userId): array
    {
        $criteria = ['client_id' => $userId];
        $allBookings = $this->bookingQuery->searchBookings($criteria);

        $analytics = [
            'total_bookings' => $allBookings->count(),
            'completed_bookings' => $allBookings->where('status', 'completed')->count(),
            'cancelled_bookings' => $allBookings->where('status', 'cancelled')->count(),
            'pending_bookings' => $allBookings->where('status', 'pending')->count(),
            'total_spent' => $allBookings->where('status', 'completed')->sum('price'),
            'average_rating' => $allBookings->where('status', 'completed')->avg('rating'),
            'favorite_services' => $this->getFavoriteServices($allBookings),
            'booking_frequency' => $this->calculateBookingFrequency($allBookings),
        ];

        return $analytics;
    }

    /**
     * Получить популярные услуги среди пользователей
     */
    public function getPopularServicesAmongUsers(array $userFilters = [], int $limit = 10): array
    {
        // Получаем активных пользователей по фильтрам
        $users = $this->userQuery->getActiveUsers();
        
        if (!empty($userFilters)) {
            $users = $this->userQuery->searchUsers('', $userFilters);
        }

        $userIds = $users->pluck('id')->toArray();
        
        // Получаем популярные услуги среди этих пользователей
        return $this->bookingQuery->getPopularServices($limit);
    }

    /**
     * Получить отчет по бронированиям пользователей за период
     */
    public function getUserBookingReport(string $period, array $userFilters = []): array
    {
        $filters = array_merge($userFilters, ['period' => $period]);
        
        return [
            'period' => $period,
            'bookings_by_period' => $this->bookingQuery->getBookingsByPeriod($period, $filters),
            'new_users_with_bookings' => $this->getNewUsersWithBookings($period),
            'returning_clients' => $this->getReturningClients($period),
            'average_booking_value' => $this->getAverageBookingValue($period, $filters),
        ];
    }

    /**
     * Получить предстоящие бронирования с информацией о пользователях
     */
    public function getUpcomingBookingsWithUsers(int $days = 7): Collection
    {
        $upcomingBookings = $this->bookingQuery->getUpcomingBookings($days);
        
        // Загружаем информацию о пользователях
        $userIds = $upcomingBookings->pluck('client_id')->unique();
        $users = collect();
        
        foreach ($userIds as $userId) {
            $user = $this->userQuery->searchUsers('', ['id' => $userId])->first();
            if ($user) {
                $users->put($userId, $user);
            }
        }

        // Обогащаем бронирования данными пользователей
        return $upcomingBookings->map(function ($booking) use ($users) {
            $booking->client = $users->get($booking->client_id);
            return $booking;
        });
    }

    /**
     * Получить любимые услуги пользователя
     */
    private function getFavoriteServices(Collection $bookings): array
    {
        return $bookings
            ->groupBy('service_type')
            ->map(fn($group) => $group->count())
            ->sortDesc()
            ->take(5)
            ->toArray();
    }

    /**
     * Рассчитать частоту бронирований (дней между бронированиями)
     */
    private function calculateBookingFrequency(Collection $bookings): ?float
    {
        $completedBookings = $bookings
            ->where('status', 'completed')
            ->sortBy('completed_at');

        if ($completedBookings->count() < 2) {
            return null;
        }

        $intervals = [];
        $previous = null;

        foreach ($completedBookings as $booking) {
            if ($previous) {
                $interval = $booking->created_at->diffInDays($previous->created_at);
                $intervals[] = $interval;
            }
            $previous = $booking;
        }

        return !empty($intervals) ? array_sum($intervals) / count($intervals) : null;
    }

    /**
     * Получить новых пользователей с бронированиями за период
     */
    private function getNewUsersWithBookings(string $period): array
    {
        $periodDays = match($period) {
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            'year' => 365,
            default => 30
        };

        $newUsers = $this->userQuery->getNewUsers($periodDays);
        $usersWithBookings = [];

        foreach ($newUsers as $user) {
            $bookings = $this->bookingQuery->searchBookings(['client_id' => $user->id]);
            if ($bookings->isNotEmpty()) {
                $usersWithBookings[] = [
                    'user' => $user,
                    'first_booking' => $bookings->sortBy('created_at')->first(),
                    'bookings_count' => $bookings->count()
                ];
            }
        }

        return $usersWithBookings;
    }

    /**
     * Получить возвращающихся клиентов за период
     */
    private function getReturningClients(string $period): array
    {
        $periodDays = match($period) {
            'week' => 7,
            'month' => 30,
            'quarter' => 90,
            'year' => 365,
            default => 30
        };

        $criteria = [
            'status' => 'completed',
            'created_at_from' => now()->subDays($periodDays)
        ];

        $recentBookings = $this->bookingQuery->searchBookings($criteria);
        
        $clientBookingCounts = $recentBookings
            ->groupBy('client_id')
            ->filter(fn($bookings) => $bookings->count() > 1)
            ->map(fn($bookings) => $bookings->count());

        return [
            'returning_clients_count' => $clientBookingCounts->count(),
            'total_repeat_bookings' => $clientBookingCounts->sum(),
            'average_bookings_per_returning_client' => $clientBookingCounts->avg()
        ];
    }

    /**
     * Получить среднюю стоимость бронирования за период
     */
    private function getAverageBookingValue(string $period, array $filters): float
    {
        $bookings = $this->bookingQuery->getBookingsByPeriod($period, $filters);
        
        if (empty($bookings)) {
            return 0.0;
        }

        $totalValue = array_sum(array_column($bookings, 'total_price'));
        $totalCount = array_sum(array_column($bookings, 'bookings_count'));

        return $totalCount > 0 ? $totalValue / $totalCount : 0.0;
    }
}