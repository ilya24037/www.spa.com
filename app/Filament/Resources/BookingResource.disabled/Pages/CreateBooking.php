<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Автоматически генерируем номер бронирования если не указан
        if (empty($data['booking_number'])) {
            $data['booking_number'] = 'BOOK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        }

        // Рассчитываем время окончания если не указано
        if (empty($data['end_time']) && !empty($data['start_time']) && !empty($data['duration_minutes'])) {
            $startTime = \Carbon\Carbon::parse($data['booking_date'] . ' ' . $data['start_time']);
            $data['end_time'] = $startTime->addMinutes($data['duration_minutes'])->format('H:i:s');
        }

        return $data;
    }
}