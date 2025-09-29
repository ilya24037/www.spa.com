<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Domain\Ad\Enums\AdStatus;

class ListAds extends ListRecords
{
    protected static string $resource = AdResource::class;

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
                ->badge(AdResource::getModel()::count()),
                
            'pending' => Tab::make('На модерации')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AdStatus::PENDING_MODERATION->value))
                ->badge(AdResource::getModel()::where('status', AdStatus::PENDING_MODERATION->value)->count())
                ->badgeColor('warning'),
                
            'active' => Tab::make('Активные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AdStatus::ACTIVE->value))
                ->badge(AdResource::getModel()::where('status', AdStatus::ACTIVE->value)->count())
                ->badgeColor('success'),
                
            'rejected' => Tab::make('Отклоненные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AdStatus::REJECTED->value))
                ->badge(AdResource::getModel()::where('status', AdStatus::REJECTED->value)->count())
                ->badgeColor('danger'),
                
            'blocked' => Tab::make('Заблокированные')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AdStatus::BLOCKED->value))
                ->badge(AdResource::getModel()::where('status', AdStatus::BLOCKED->value)->count())
                ->badgeColor('danger'),
                
            'archived' => Tab::make('Архив')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AdStatus::ARCHIVED->value))
                ->badge(AdResource::getModel()::where('status', AdStatus::ARCHIVED->value)->count()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'pending';
    }
}