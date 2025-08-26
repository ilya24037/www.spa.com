<?php

namespace App\Domain\Booking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Запрос на создание бронирования
 * Заменяет прямое обращение к $user->bookings()->create()
 */
class BookingRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $clientId ID клиента
     * @param int $masterId ID мастера
     * @param array $bookingData Данные бронирования
     */
    public function __construct(
        public readonly int $clientId,
        public readonly int $masterId,
        public readonly array $bookingData
    ) {}

    /**
     * Получить данные для создания бронирования
     */
    public function getBookingData(): array
    {
        return array_merge($this->bookingData, [
            'client_id' => $this->clientId,
            'master_id' => $this->masterId,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }
}