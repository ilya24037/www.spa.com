<?php

namespace App\Filament\Resources\StopWordResource\Pages;

use App\Filament\Resources\StopWordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStopWord extends EditRecord
{
    protected static string $resource = StopWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn () => \App\Domain\Moderation\Models\StopWord::clearCache()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        
        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Стоп-слово обновлено';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}