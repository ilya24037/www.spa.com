<?php

namespace App\Filament\Resources\MasterProfileResource\Pages;

use App\Filament\Resources\MasterProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMasterProfile extends ViewRecord
{
    protected static string $resource = MasterProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}