<?php

namespace App\Filament\Widgets;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdsChart extends ChartWidget
{
    protected ?string $heading = 'Статистика объявлений';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'За 7 дней',
            '30' => 'За 30 дней',
            '90' => 'За 3 месяца',
        ];
    }

    protected function getData(): array
    {
        $activeData = [];
        $newData = [];
        $labels = [];

        $days = (int) $this->filter;

        for ($i = $days - 1; $i >= 0; $i--) {
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

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
}