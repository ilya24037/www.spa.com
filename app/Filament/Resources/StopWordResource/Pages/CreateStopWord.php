<?php

namespace App\Filament\Resources\StopWordResource\Pages;

use App\Filament\Resources\StopWordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStopWord extends CreateRecord
{
    protected static string $resource = StopWordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Стоп-слово добавлено';
    }
}