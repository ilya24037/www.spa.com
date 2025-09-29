<?php

namespace App\Filament\Widgets;

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\Review\Models\Review;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Booking\Models\Booking;
use App\Domain\Ad\Models\Complaint;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected function getStats(): array
    {
        // Получаем данные для графиков за последние 7 дней
        $lastWeekUsers = $this->getLastWeekData('users');
        $lastWeekAds = $this->getLastWeekData('ads');
        $lastWeekBookings = $this->getLastWeekData('bookings');
        
        // Считаем изменения за день
        $newUsersToday = User::whereDate('created_at', today())->count();
        $newUsersYesterday = User::whereDate('created_at', today()->subDay())->count();
        $usersTrend = $this->calculateTrend($newUsersToday, $newUsersYesterday);
        
        // Активные объявления
        $activeAds = Ad::where('status', AdStatus::ACTIVE->value)->count();
        $pendingModeration = Ad::where('status', AdStatus::PENDING_MODERATION->value)->count();
        
        // Бронирования
        $bookingsToday = Booking::whereDate('created_at', today())->count();
        $bookingsYesterday = Booking::whereDate('created_at', today()->subDay())->count();
        $bookingsTrend = $this->calculateTrend($bookingsToday, $bookingsYesterday);
        
        // Жалобы
        $openComplaints = Complaint::where('status', 'pending')->count();
        
        return [
            // 1. Пользователи
            Stat::make('Всего пользователей', User::count())
                ->description($newUsersToday . ' новых сегодня')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($usersTrend >= 0 ? 'success' : 'danger')
                ->chart($lastWeekUsers)
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/users'),

            // 2. На модерации (самое важное!)
            Stat::make('На модерации', $pendingModeration)
                ->description($pendingModeration > 0 ? 'Требуют внимания!' : 'Всё проверено')
                ->descriptionIcon($pendingModeration > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($pendingModeration > 0 ? 'warning' : 'success')
                ->chart([2, 1, 3, 2, 4, 1, 2, $pendingModeration])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/ads?tableFilters[status][values][0]=' . AdStatus::PENDING_MODERATION->value),

            // 3. Активные объявления
            Stat::make('Активные объявления', $activeAds)
                ->description('Опубликовано на платформе')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('primary')
                ->chart($lastWeekAds)
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/ads'),

            // 4. Бронирования сегодня
            Stat::make('Бронирований сегодня', $bookingsToday)
                ->description($bookingsTrend >= 0 ? '+' . $bookingsTrend . '% к вчера' : $bookingsTrend . '% к вчера')
                ->descriptionIcon($bookingsTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($bookingsTrend >= 0 ? 'success' : 'warning')
                ->chart($lastWeekBookings)
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/bookings'),

            // 5. Активные мастера
            Stat::make('Активные мастера', MasterProfile::where('is_published', true)->count())
                ->description('Работают на платформе')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/master-profiles'),

            // 6. Открытые жалобы
            Stat::make('Жалобы', $openComplaints)
                ->description($openComplaints > 0 ? 'Требуют рассмотрения' : 'Нет активных')
                ->descriptionIcon($openComplaints > 0 ? 'heroicon-m-flag' : 'heroicon-m-check')
                ->color($openComplaints > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/complaints'),
        ];
    }
    
    /**
     * Получить данные за последнюю неделю для графика
     */
    protected function getLastWeekData(string $model): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            switch ($model) {
                case 'users':
                    $count = User::whereDate('created_at', $date)->count();
                    break;
                case 'ads':
                    $count = Ad::whereDate('created_at', $date)->count();
                    break;
                case 'bookings':
                    $count = Booking::whereDate('created_at', $date)->count();
                    break;
                default:
                    $count = 0;
            }
            $data[] = $count;
        }
        return $data;
    }
    
    /**
     * Рассчитать тренд в процентах
     */
    protected function calculateTrend(int $today, int $yesterday): int
    {
        if ($yesterday == 0) {
            return $today > 0 ? 100 : 0;
        }
        return round((($today - $yesterday) / $yesterday) * 100);
    }

    protected string|null $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}
