<?php

namespace App\Filament\Resources\MasterProfileResource\Pages;

use App\Filament\Resources\MasterProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMasterProfile extends CreateRecord
{
    protected static string $resource = MasterProfileResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}