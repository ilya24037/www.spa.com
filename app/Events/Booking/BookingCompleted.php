<?php

namespace App\Events\Booking;

use App\Domain\Booking\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие завершения бронирования
 */
class BookingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для клиента (для запроса отзыва)
            new PrivateChannel('user.' . $this->booking->client_id),
            
            // Приватный канал для мастера (для статистики)
            new PrivateChannel('master.' . $this->booking->master_id),
            
            // Канал присутствия для админов
            new PresenceChannel('admin.bookings'),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'booking.completed';
    }

    /**
     * Данные для трансляции
     */
    public function broadcastWith(): array
    {
        return [
            'booking' => [
                'id' => $this->booking->id,
                'booking_number' => $this->booking->booking_number,
                'status' => $this->booking->status instanceof \App\Enums\BookingStatus 
                    ? $this->booking->status->value 
                    : $this->booking->status,
                'status_label' => $this->booking->status instanceof \App\Enums\BookingStatus 
                    ? $this->booking->status->getLabel() 
                    : $this->booking->status_text,
                'completed_at' => $this->booking->completed_at->toISOString(),
                'start_time' => $this->booking->start_time->toISOString(),
                'end_time' => $this->booking->end_time->toISOString(),
                'total_price' => $this->booking->total_price,
                'duration' => $this->booking->formatted_duration,
            ],
            'service' => [
                'id' => $this->booking->service_id,
                'name' => $this->booking->service?->name,
            ],
            'master' => [
                'id' => $this->booking->master_id,
                'name' => $this->booking->master?->name,
            ],
            'can_review' => $this->booking->can_review,
            'timestamp' => now()->toISOString(),
            'notification' => [
                'title' => 'Услуга завершена!',
                'message' => "Ваша услуга \"{$this->booking->service?->name}\" успешно завершена. " . 
                           "Поделитесь впечатлениями и оставьте отзыв!",
                'type' => 'booking_completed',
                'priority' => 'medium',
                'style' => 'success',
                'actions' => [
                    [
                        'label' => 'Оставить отзыв',
                        'url' => route('reviews.create', ['booking' => $this->booking->id]),
                        'primary' => true,
                    ],
                    [
                        'label' => 'Детали услуги',
                        'url' => route('bookings.show', $this->booking->id),
                    ],
                ],
            ],
            'review_request' => [
                'enabled' => true,
                'delay_minutes' => 30, // Показать запрос отзыва через 30 минут
                'reminder_hours' => [24, 72], // Напоминания через 1 день и 3 дня
            ],
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->booking->is_completed && $this->booking->completed_at !== null;
    }
}