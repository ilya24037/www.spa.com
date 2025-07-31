<?php

namespace App\Events\Booking;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие переноса бронирования
 */
class BookingRescheduled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?Carbon $oldStartTime = null,
        public ?Carbon $oldEndTime = null
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для клиента
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
        return 'booking.rescheduled';
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
            ],
            'schedule' => [
                'old' => [
                    'start_time' => $this->oldStartTime?->toISOString(),
                    'end_time' => $this->oldEndTime?->toISOString(),
                    'date' => $this->oldStartTime?->format('d.m.Y'),
                    'time' => $this->oldStartTime?->format('H:i') . ' - ' . $this->oldEndTime?->format('H:i'),
                ],
                'new' => [
                    'start_time' => $this->booking->start_time->toISOString(),
                    'end_time' => $this->booking->end_time->toISOString(),
                    'date' => $this->booking->start_time->format('d.m.Y'),
                    'time' => $this->booking->start_time->format('H:i') . ' - ' . $this->booking->end_time->format('H:i'),
                ],
            ],
            'timestamp' => now()->toISOString(),
            'notification' => [
                'title' => 'Бронирование перенесено',
                'message' => $this->getNotificationMessage(),
                'type' => 'booking_rescheduled',
                'priority' => 'high',
                'style' => 'info',
                'details' => [
                    'Номер бронирования' => $this->booking->booking_number,
                    'Было' => $this->oldStartTime?->format('d.m.Y в H:i'),
                    'Стало' => $this->booking->start_time->format('d.m.Y в H:i'),
                    'Услуга' => $this->booking->service?->name,
                ],
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
            'calendar_update' => [
                'remove_old' => [
                    'start' => $this->oldStartTime?->toISOString(),
                    'end' => $this->oldEndTime?->toISOString(),
                ],
                'add_new' => [
                    'id' => $this->booking->id,
                    'title' => $this->booking->service?->name ?? 'Услуга',
                    'start' => $this->booking->start_time->toISOString(),
                    'end' => $this->booking->end_time->toISOString(),
                    'status' => $this->booking->status instanceof \App\Enums\BookingStatus 
                        ? $this->booking->status->value 
                        : $this->booking->status,
                ],
            ],
        ];
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        if (!$this->oldStartTime) {
            return "Время бронирования #{$this->booking->booking_number} изменено на " .
                   $this->booking->start_time->format('d.m.Y в H:i');
        }

        $oldDate = $this->oldStartTime->format('d.m.Y');
        $newDate = $this->booking->start_time->format('d.m.Y');
        
        if ($oldDate === $newDate) {
            // Изменилось только время в тот же день
            return "Время бронирования #{$this->booking->booking_number} изменено с " .
                   $this->oldStartTime->format('H:i') . ' на ' . $this->booking->start_time->format('H:i') .
                   " ({$newDate})";
        } else {
            // Изменилась дата
            return "Бронирование #{$this->booking->booking_number} перенесено с " .
                   $this->oldStartTime->format('d.m.Y в H:i') . ' на ' . 
                   $this->booking->start_time->format('d.m.Y в H:i');
        }
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->oldStartTime !== null && 
               !$this->oldStartTime->equalTo($this->booking->start_time);
    }
}