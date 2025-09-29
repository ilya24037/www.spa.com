<?php

namespace App\Filament\Resources\StopWordResource\Widgets;

use App\Domain\Moderation\Models\StopWord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StopWordStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalWords = StopWord::count();
        $activeWords = StopWord::where('is_active', true)->count();
        $totalHits = StopWord::sum('hits_count');
        $highFalsePositive = StopWord::whereRaw('(false_positives * 100.0 / NULLIF(hits_count, 0)) > 30')
            ->where('hits_count', '>', 0)
            ->count();

        return [
            Stat::make('Всего слов', $totalWords)
                ->description('В базе данных')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
                
            Stat::make('Активных', $activeWords)
                ->description(round(($activeWords / max($totalWords, 1)) * 100) . '% от общего')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Срабатываний', number_format($totalHits))
                ->description('Всего заблокировано')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color('warning'),
                
            Stat::make('Требуют проверки', $highFalsePositive)
                ->description('Много ложных срабатываний')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($highFalsePositive > 0 ? 'danger' : 'success'),
        ];
    }
}