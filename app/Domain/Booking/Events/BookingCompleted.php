<?php

namespace App\Domain\Booking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;  
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: Завершение бронирования
 * Для уведомлений и начисления статистики
 */
class BookingCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param int $bookingId ID бронирования
     * @param int $clientId ID клиента
     * @param int $masterId ID мастера
     * @param array $completionData Данные завершения (оценка, отзыв и т.д.)
     */
    public function __construct(
        public readonly int $bookingId,
        public readonly int $clientId,
        public readonly int $masterId,
        public readonly array $completionData = []
    ) {}

    /**
     * Получить оценку, если есть
     */
    public function getRating(): ?int
    {
        return $this->completionData['rating'] ?? null;
    }

    /**
     * Получить текст отзыва, если есть
     */
    public function getReviewText(): ?string
    {
        return $this->completionData['review'] ?? null;
    }

    /**
     * Проверить, есть ли отзыв
     */
    public function hasReview(): bool
    {
        return !empty($this->completionData['review']);
    }

    /**
     * Получить стоимость услуги
     */
    public function getServiceCost(): ?float
    {
        return $this->completionData['cost'] ?? null;
    }

    /**
     * Получить данные для статистики
     */
    public function getStatisticsData(): array
    {
        return [
            'master_id' => $this->masterId,
            'client_id' => $this->clientId,
            'rating' => $this->getRating(),
            'cost' => $this->getServiceCost(),
            'has_review' => $this->hasReview(),
            'completed_at' => now(),
        ];
    }
}