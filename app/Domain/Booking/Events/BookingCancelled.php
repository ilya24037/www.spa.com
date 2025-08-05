<?php

namespace App\Domain\Booking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Отмена бронирования
 * Для уведомления пользователей и мастеров
 */
class BookingCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $bookingId ID бронирования
     * @param int $clientId ID клиента
     * @param int $masterId ID мастера
     * @param int $cancelledBy ID пользователя, который отменил
     * @param string|null $reason Причина отмены
     * @param array $bookingData Данные бронирования для уведомлений
     */
    public function __construct(
        public readonly int $bookingId,
        public readonly int $clientId,
        public readonly int $masterId,
        public readonly int $cancelledBy,
        public readonly ?string $reason = null,
        public readonly array $bookingData = []
    ) {}

    /**
     * Проверить, отменил ли клиент
     */
    public function wasCancelledByClient(): bool
    {
        return $this->cancelledBy === $this->clientId;
    }

    /**
     * Проверить, отменил ли мастер
     */
    public function wasCancelledByMaster(): bool
    {
        return $this->cancelledBy === $this->masterId;
    }

    /**
     * Получить данные для уведомлений
     */
    public function getNotificationData(): array
    {
        return [
            'booking_id' => $this->bookingId,
            'reason' => $this->reason,
            'cancelled_by_role' => $this->wasCancelledByClient() ? 'client' : 'master',
            'booking_data' => $this->bookingData,
        ];
    }
}