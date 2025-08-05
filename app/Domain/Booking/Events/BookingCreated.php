<?php

namespace App\Domain\Booking\Events;

use App\Domain\Booking\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания нового бронирования
 * Соответствует DDD архитектуре - размещено в Domain\Booking\Events
 */
class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для мастера
            new PrivateChannel('master.' . $this->booking->master_id),
            
            // Приватный канал для клиента  
            new PrivateChannel('user.' . $this->booking->client_id),
            
            // Канал присутствия для админов
            new PresenceChannel('admin.bookings'),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'booking.created';
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
                'type' => $this->booking->type?->value,
                'type_label' => $this->booking->type?->getLabel(),
                'status' => $this->booking->status instanceof \App\Enums\BookingStatus 
                    ? $this->booking->status->value 
                    : $this->booking->status,
                'status_label' => $this->booking->status instanceof \App\Enums\BookingStatus 
                    ? $this->booking->status->getLabel() 
                    : $this->booking->status_text,
                'start_time' => $this->booking->start_time->toISOString(),
                'end_time' => $this->booking->end_time->toISOString(),
                'duration' => $this->booking->formatted_duration,
                'total_price' => $this->booking->total_price,
                'client' => [
                    'id' => $this->booking->client_id,
                    'name' => $this->booking->client_name,
                    'phone' => $this->booking->client_phone,
                ],
                'master' => [
                    'id' => $this->booking->master_id,
                    'name' => $this->booking->master?->name,
                ],
                'service' => [
                    'id' => $this->booking->service_id,
                    'name' => $this->booking->service?->name,
                ],
            ],
            'timestamp' => now()->toISOString(),
            'notification' => [
                'title' => 'Новое бронирование',
                'message' => "Клиент {$this->booking->client_name} забронировал услугу на " . 
                           $this->booking->start_time->format('d.m.Y в H:i'),
                'type' => 'booking_created',
                'priority' => 'high',
            ],
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        // Транслируем только если бронирование активно
        return $this->booking->is_active;
    }
}