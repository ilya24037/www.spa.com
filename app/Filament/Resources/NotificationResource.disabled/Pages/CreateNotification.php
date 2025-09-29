<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Устанавливаем время отправки "сейчас" если не запланирована отправка
        if (empty($data['scheduled_at'])) {
            $data['scheduled_at'] = now();
        }

        return $data;
    }
}