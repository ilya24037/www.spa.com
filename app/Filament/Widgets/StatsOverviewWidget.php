<?php

namespace App\Filament\Widgets;

use App\Domain\User\Models\User;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\Review\Models\Review;
use App\Domain\Master\Models\MasterProfile;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Всего пользователей', User::count())
                ->description('Зарегистрированные пользователи')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/users'),

            Stat::make('Активные объявления', Ad::where('status', AdStatus::ACTIVE->value)->count())
                ->description('Опубликованные объявления')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('primary')
                ->chart([4, 6, 5, 8, 3, 5, 6, 7])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/ads'),

            Stat::make('На модерации', Ad::where('status', AdStatus::PENDING_MODERATION->value)->count())
                ->description('Ожидают проверки')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([2, 1, 3, 2, 4, 1, 2, 3])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/ads?tableFilters[status][values][0]=' . AdStatus::PENDING_MODERATION->value),

            Stat::make('Активные мастера', MasterProfile::where('status', 'approved')->count())
                ->description('Верифицированные специалисты')
                ->descriptionIcon('heroicon-m-check')
                ->color('success')
                ->chart([3, 4, 3, 5, 4, 6, 5, 4])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/master-profiles'),

            Stat::make('Отзывы сегодня', Review::whereDate('created_at', today())->count())
                ->description('Новые отзывы за день')
                ->descriptionIcon('heroicon-m-star')
                ->color('info')
                ->chart([1, 2, 1, 3, 2, 4, 2, 1])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/reviews'),

            Stat::make('Средний рейтинг', number_format(
                Review::where('status', 'approved')->avg('rating') ?: 0,
                1
            ))
                ->description('Общая оценка платформы')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger')
                ->chart([4.2, 4.3, 4.1, 4.4, 4.2, 4.5, 4.3, 4.4])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ])
                ->url('/admin/reviews'),
        ];
    }

    protected string|null $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}
