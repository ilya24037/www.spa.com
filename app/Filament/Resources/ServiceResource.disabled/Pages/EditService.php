<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Обновляем slug при изменении названия
        if (!empty($data['name']) && (empty($data['slug']) || $data['slug'] !== Str::slug($data['name']))) {
            $baseSlug = Str::slug($data['name']);
            $data['slug'] = $baseSlug;

            // Проверяем уникальность slug, исключая текущую запись
            $counter = 1;
            while (\App\Domain\Service\Models\Service::where('slug', $data['slug'])
                   ->where('id', '!=', $this->record->id)
                   ->exists()) {
                $data['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        return $data;
    }
}