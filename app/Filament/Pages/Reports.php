<?php

namespace App\Filament\Pages;

use App\Domain\Ad\Models\Ad;
use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use App\Domain\Payment\Models\Payment;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\ChartWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Reports extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public function getView(): string
    {
        return 'filament.pages.reports';
    }

    // protected static ?string $navigationGroup = 'Система';

    public static function getNavigationGroup(): ?string
    {
        return 'Система';
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Отчеты и аналитика';
    }

    public function getHeading(): string
    {
        return 'Отчеты и аналитика';
    }

    public function getSubheading(): ?string
    {
        return 'Статистика и аналитика работы платформы';
    }

    public $startDate;
    public $endDate;
    public $reportType = 'overview';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportReport')
                ->label('Экспорт отчета')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Дата начала')
                        ->default(now()->startOfMonth())
                        ->required(),

                    DatePicker::make('end_date')
                        ->label('Дата окончания')
                        ->default(now()->endOfMonth())
                        ->required(),

                    Select::make('format')
                        ->label('Формат экспорта')
                        ->options([
                            'excel' => 'Excel (.xlsx)',
                            'csv' => 'CSV',
                            'pdf' => 'PDF',
                        ])
                        ->default('excel')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Here you would implement export logic
                    Notification::make()
                        ->title('Экспорт запущен')
                        ->body('Отчет будет отправлен на вашу почту в течение нескольких минут.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getOverviewStats(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        return [
            Stat::make('Общий доход', $this->getTotalRevenue($startDate, $endDate))
                ->description('За выбранный период')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Новые пользователи', $this->getNewUsersCount($startDate, $endDate))
                ->description('Регистрации')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Активные объявления', $this->getActiveAdsCount())
                ->description('На данный момент')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('warning'),

            Stat::make('Завершенные бронирования', $this->getCompletedBookingsCount($startDate, $endDate))
                ->description('За период')
                ->descriptionIcon('heroicon-o-calendar-check')
                ->color('primary'),
        ];
    }

    public function getRevenueChartData(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $data = Payment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->map(fn($item) => Carbon::parse($item->date)->format('d.m')),
            'datasets' => [[
                'label' => 'Доход (₽)',
                'data' => $data->pluck('total'),
                'borderColor' => '#4F46E5',
                'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
            ]],
        ];
    }

    public function getUserRegistrationChartData(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $data = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->map(fn($item) => Carbon::parse($item->date)->format('d.m')),
            'datasets' => [[
                'label' => 'Регистрации',
                'data' => $data->pluck('count'),
                'borderColor' => '#10B981',
                'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
            ]],
        ];
    }

    public function getTopMastersData(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        return User::select('users.name', 'users.email')
            ->selectRaw('COUNT(bookings.id) as bookings_count')
            ->selectRaw('SUM(payments.amount) as total_earned')
            ->join('master_profiles', 'users.id', '=', 'master_profiles.user_id')
            ->join('bookings', 'master_profiles.id', '=', 'bookings.master_profile_id')
            ->leftJoin('payments', 'bookings.id', '=', 'payments.booking_id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->where('bookings.status', 'completed')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_earned')
            ->limit(10)
            ->get();
    }

    public function getTopServicesData(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        return DB::table('ads')
            ->select('ads.title')
            ->selectRaw('COUNT(bookings.id) as bookings_count')
            ->selectRaw('SUM(payments.amount) as total_revenue')
            ->join('bookings', 'ads.id', '=', 'bookings.ad_id')
            ->leftJoin('payments', 'bookings.id', '=', 'payments.booking_id')
            ->whereBetween('bookings.created_at', [$startDate, $endDate])
            ->where('bookings.status', 'completed')
            ->where('ads.status', 'active')
            ->groupBy('ads.id', 'ads.title')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
    }

    private function getTotalRevenue(Carbon $startDate, Carbon $endDate): string
    {
        $total = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');

        return number_format($total, 0, ',', ' ') . ' ₽';
    }

    private function getNewUsersCount(Carbon $startDate, Carbon $endDate): string
    {
        $count = User::whereBetween('created_at', [$startDate, $endDate])->count();
        return number_format($count);
    }

    private function getActiveAdsCount(): string
    {
        $count = Ad::where('status', 'active')->count();
        return number_format($count);
    }

    private function getCompletedBookingsCount(Carbon $startDate, Carbon $endDate): string
    {
        $count = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
        return number_format($count);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}