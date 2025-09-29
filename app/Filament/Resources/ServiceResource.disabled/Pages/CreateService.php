<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Автоматически генерируем slug если не указан
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Проверяем уникальность slug
        if (!empty($data['slug'])) {
            $originalSlug = $data['slug'];
            $counter = 1;

            while (\App\Domain\Service\Models\Service::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        // Автоматически заполняем meta_title если не указан
        if (empty($data['meta_title']) && !empty($data['name'])) {
            $data['meta_title'] = $data['name'];
        }

        return $data;
    }
}