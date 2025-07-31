<?php

namespace App\Events\Booking;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие подтверждения бронирования
 */
class BookingConfirmed implements ShouldBroadcast
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
            // Приватный канал для клиента (главное уведомление)
            new PrivateChannel('user.' . $this->booking->client_id),
            
            // Приватный канал для мастера
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
        return 'booking.confirmed';
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
                'confirmed_at' => $this->booking->confirmed_at->toISOString(),
                'start_time' => $this->booking->start_time->toISOString(),
                'end_time' => $this->booking->end_time->toISOString(),
                'master_address' => $this->booking->master_address,
                'master_phone' => $this->booking->master_phone,
                'client_address' => $this->booking->client_address,
                'meeting_link' => $this->booking->meeting_link,
                'equipment_required' => $this->booking->equipment_required,
                'internal_notes' => $this->booking->internal_notes,
            ],
            'timestamp' => now()->toISOString(),
            'notification' => [
                'title' => 'Бронирование подтверждено!',
                'message' => "Ваше бронирование #{$this->booking->booking_number} подтверждено на " . 
                           $this->booking->start_time->format('d.m.Y в H:i'),
                'type' => 'booking_confirmed',
                'priority' => 'high',
                'actions' => [
                    [
                        'label' => 'Детали',
                        'url' => route('bookings.show', $this->booking->id),
                    ],
                    [
                        'label' => 'Отменить',
                        'url' => route('bookings.cancel', $this->booking->id),
                        'confirm' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->booking->confirmed_at !== null;
    }
}