<?php

namespace App\Filament\Resources\ComplaintResource\Pages;

use App\Filament\Resources\ComplaintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListComplaints extends ListRecords
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все')
                ->badge(ComplaintResource::getModel()::count()),

            'pending' => Tab::make('Ожидают рассмотрения')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(ComplaintResource::getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),

            'resolved' => Tab::make('Разрешенные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'resolved'))
                ->badge(ComplaintResource::getModel()::where('status', 'resolved')->count())
                ->badgeColor('success'),

            'rejected' => Tab::make('Отклоненные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(ComplaintResource::getModel()::where('status', 'rejected')->count()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }
}