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
 * Событие напоминания о бронировании
 */
class BookingReminder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public int $hoursUntilStart
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для клиента (основное напоминание)
            new PrivateChannel('user.' . $this->booking->client_id),
            
            // Приватный канал для мастера (для подготовки)
            new PrivateChannel('master.' . $this->booking->master_id),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'booking.reminder';
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
                'start_time' => $this->booking->start_time->toISOString(),
                'end_time' => $this->booking->end_time->toISOString(),
                'duration' => $this->booking->formatted_duration,
                'type' => $this->booking->type?->value,
                'type_label' => $this->booking->type?->getLabel(),
                'client_address' => $this->booking->client_address,
                'master_address' => $this->booking->master_address,
                'master_phone' => $this->booking->master_phone,
                'meeting_link' => $this->booking->meeting_link,
                'equipment_required' => $this->booking->equipment_required,
                'notes' => $this->booking->notes,
            ],
            'service' => [
                'id' => $this->booking->service_id,
                'name' => $this->booking->service?->name,
            ],
            'master' => [
                'id' => $this->booking->master_id,
                'name' => $this->booking->master?->name,
                'phone' => $this->booking->master_phone,
            ],
            'reminder' => [
                'hours_until_start' => $this->hoursUntilStart,
                'minutes_until_start' => $this->hoursUntilStart * 60 - $this->booking->start_time->diffInMinutes(now()),
                'type' => $this->getReminderType(),
                'urgency' => $this->getUrgency(),
            ],
            'timestamp' => now()->toISOString(),
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => 'booking_reminder',
                'priority' => $this->getNotificationPriority(),
                'style' => $this->getNotificationStyle(),
                'persistent' => $this->hoursUntilStart <= 1, // Не скрывать автоматически если до услуги <= 1 часа
                'sound' => $this->hoursUntilStart <= 1,
                'actions' => $this->getNotificationActions(),
            ],
        ];
    }

    /**
     * Получить тип напоминания
     */
    protected function getReminderType(): string
    {
        if ($this->hoursUntilStart >= 24) {
            return 'day_before';
        } elseif ($this->hoursUntilStart >= 4) {
            return 'hours_before';
        } elseif ($this->hoursUntilStart >= 1) {
            return 'hour_before';
        } else {
            return 'urgent';
        }
    }

    /**
     * Получить уровень срочности
     */
    protected function getUrgency(): string
    {
        if ($this->hoursUntilStart <= 0.25) { // 15 минут или меньше
            return 'critical';
        } elseif ($this->hoursUntilStart <= 1) {
            return 'high';
        } elseif ($this->hoursUntilStart <= 4) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Получить заголовок уведомления
     */
    protected function getNotificationTitle(): string
    {
        return match($this->getReminderType()) {
            'day_before' => 'Напоминание: завтра у вас услуга',
            'hours_before' => 'Напоминание: услуга через ' . $this->hoursUntilStart . ' ч.',
            'hour_before' => 'Услуга через час',
            'urgent' => 'Услуга скоро начнется!',
            default => 'Напоминание о услуге',
        };
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        $serviceName = $this->booking->service?->name ?? 'Услуга';
        $masterName = $this->booking->master?->name ?? 'Мастер';
        $startTime = $this->booking->start_time->format('d.m.Y в H:i');

        $baseMessage = "\"{$serviceName}\" у мастера {$masterName} - {$startTime}";

        return match($this->getReminderType()) {
            'day_before' => "Завтра {$baseMessage}",
            'hours_before' => "Через {$this->hoursUntilStart} ч. {$baseMessage}",
            'hour_before' => "Через час {$baseMessage}",
            'urgent' => "Скоро начнется {$baseMessage}",
            default => $baseMessage,
        };
    }

    /**
     * Получить приоритет уведомления
     */
    protected function getNotificationPriority(): string
    {
        return match($this->getUrgency()) {
            'critical', 'high' => 'high',
            'medium' => 'medium',
            default => 'low',
        };
    }

    /**
     * Получить стиль уведомления
     */
    protected function getNotificationStyle(): string
    {
        return match($this->getUrgency()) {
            'critical' => 'error',
            'high' => 'warning',
            default => 'info',
        };
    }

    /**
     * Получить действия для уведомления
     */
    protected function getNotificationActions(): array
    {
        $actions = [
            [
                'label' => 'Детали',
                'url' => route('bookings.show', $this->booking->id),
            ],
        ];

        // Действия в зависимости от типа бронирования
        if ($this->booking->type) {
            switch ($this->booking->type) {
                case \App\Enums\BookingType::OUTCALL:
                    if ($this->booking->client_address) {
                        $actions[] = [
                            'label' => 'Маршрут',
                            'url' => 'https://maps.google.com/maps?q=' . urlencode($this->booking->client_address),
                            'external' => true,
                        ];
                    }
                    break;

                case \App\Enums\BookingType::INCALL:
                    if ($this->booking->master_address) {
                        $actions[] = [
                            'label' => 'Адрес салона',
                            'url' => 'https://maps.google.com/maps?q=' . urlencode($this->booking->master_address),
                            'external' => true,
                        ];
                    }
                    break;

                case \App\Enums\BookingType::ONLINE:
                    if ($this->booking->meeting_link) {
                        $actions[] = [
                            'label' => 'Подключиться',
                            'url' => $this->booking->meeting_link,
                            'external' => true,
                            'primary' => $this->hoursUntilStart <= 0.25, // Выделить если до начала 15 мин или меньше
                        ];
                    }
                    break;
            }
        }

        // Кнопка связи с мастером
        if ($this->booking->master_phone) {
            $actions[] = [
                'label' => 'Позвонить',
                'url' => 'tel:' . $this->booking->master_phone,
            ];
        }

        // Возможность отмены (если до услуги больше 2 часов)
        if ($this->hoursUntilStart > 2 && $this->booking->can_be_cancelled) {
            $actions[] = [
                'label' => 'Отменить',
                'url' => route('bookings.cancel', $this->booking->id),
                'confirm' => true,
                'style' => 'secondary',
            ];
        }

        return $actions;
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->booking->status instanceof \App\Enums\BookingStatus 
            ? $this->booking->status->needsReminder()
            : $this->booking->status === 'confirmed';
    }
}