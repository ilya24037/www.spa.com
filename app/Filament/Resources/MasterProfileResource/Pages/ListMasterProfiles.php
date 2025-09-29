<?php

namespace App\Filament\Resources\MasterProfileResource\Pages;

use App\Filament\Resources\MasterProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\MasterStatus;

class ListMasterProfiles extends ListRecords
{
    protected static string $resource = MasterProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Все')
                ->badge(MasterProfileResource::getModel()::count()),

            'pending' => Tab::make('На модерации')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MasterStatus::PENDING->value))
                ->badge(MasterProfileResource::getModel()::where('status', MasterStatus::PENDING->value)->count())
                ->badgeColor('warning'),

            'active' => Tab::make('Активные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MasterStatus::ACTIVE->value))
                ->badge(MasterProfileResource::getModel()::where('status', MasterStatus::ACTIVE->value)->count())
                ->badgeColor('success'),

            'blocked' => Tab::make('Заблокированные')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [
                    MasterStatus::BLOCKED->value,
                    MasterStatus::SUSPENDED->value
                ]))
                ->badge(MasterProfileResource::getModel()::whereIn('status', [
                    MasterStatus::BLOCKED->value,
                    MasterStatus::SUSPENDED->value
                ])->count())
                ->badgeColor('danger'),

            'premium' => Tab::make('Премиум')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_premium', true))
                ->badge(MasterProfileResource::getModel()::where('is_premium', true)->count())
                ->badgeColor('warning'),

            'verified' => Tab::make('Верифицированные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_verified', true))
                ->badge(MasterProfileResource::getModel()::where('is_verified', true)->count()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }
}