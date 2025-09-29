<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Рассчитываем время окончания если изменились время начала или длительность
        if (!empty($data['start_time']) && !empty($data['duration_minutes'])) {
            $startTime = \Carbon\Carbon::parse($data['booking_date'] . ' ' . $data['start_time']);
            $data['end_time'] = $startTime->addMinutes($data['duration_minutes'])->format('H:i:s');
        }

        return $data;
    }
}