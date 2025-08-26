<?php

namespace App\Domain\Booking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Изменение статуса бронирования
 * Для уведомления других доменов об изменениях
 */
class BookingStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $bookingId ID бронирования
     * @param int $clientId ID клиента
     * @param int $masterId ID мастера
     * @param string $oldStatus Предыдущий статус
     * @param string $newStatus Новый статус
     * @param array|null $metadata Дополнительные данные
     */
    public function __construct(
        public readonly int $bookingId,
        public readonly int $clientId,
        public readonly int $masterId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Проверить, было ли бронирование подтверждено
     */
    public function wasConfirmed(): bool
    {
        return $this->oldStatus === 'pending' && $this->newStatus === 'confirmed';
    }

    /**
     * Проверить, было ли бронирование отменено
     */
    public function wasCancelled(): bool
    {
        return $this->newStatus === 'cancelled';
    }

    /**
     * Проверить, было ли бронирование завершено
     */
    public function wasCompleted(): bool
    {
        return $this->newStatus === 'completed';
    }
}