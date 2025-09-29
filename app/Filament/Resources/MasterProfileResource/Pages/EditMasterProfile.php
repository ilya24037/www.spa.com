<?php

namespace App\Filament\Resources\MasterProfileResource\Pages;

use App\Filament\Resources\MasterProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterProfile extends EditRecord
{
    protected static string $resource = MasterProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}