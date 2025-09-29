<?php

namespace App\Filament\Widgets;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Models\Complaint;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\AdStatus;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Всего пользователей', User::count())
                ->description('Новых сегодня: ' . User::whereDate('created_at', today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart($this->getUserChart()),

            Stat::make('Активных объявлений', Ad::where('status', AdStatus::ACTIVE->value)->count())
                ->description('На модерации: ' . Ad::where('status', AdStatus::PENDING_MODERATION->value)->count())
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Мастеров', MasterProfile::where('status', MasterStatus::ACTIVE->value)->count())
                ->description('На проверке: ' . MasterProfile::where('status', MasterStatus::PENDING->value)->count())
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Жалоб на рассмотрении', Complaint::where('status', 'pending')->count())
                ->description('Всего жалоб: ' . Complaint::count())
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(Complaint::where('status', 'pending')->count() > 0 ? 'warning' : 'gray'),
        ];
    }

    protected function getUserChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = User::whereDate('created_at', today()->subDays($i))->count();
        }
        return $data;
    }
}