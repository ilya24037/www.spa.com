<?php

namespace App\Filament\Widgets;

use App\Domain\Ad\Models\Ad;
use App\Enums\AdStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdsChart extends ChartWidget
{
    protected static ?string $heading = 'Статистика объявлений за последние 30 дней';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $activeData = [];
        $newData = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d.m');

            // Новые объявления за день
            $newData[] = Ad::whereDate('created_at', $date)->count();

            // Активные объявления на конец дня
            $activeData[] = Ad::where('status', AdStatus::ACTIVE->value)
                ->whereDate('created_at', '<=', $date)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Новые объявления',
                    'data' => $newData,
                    'backgroundColor' => 'rgba(79, 70, 229, 0.3)',
                    'borderColor' => 'rgb(79, 70, 229)',
                ],
                [
                    'label' => 'Активные объявления',
                    'data' => $activeData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.3)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}