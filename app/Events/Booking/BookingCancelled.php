<?php

namespace App\Events\Booking;

use App\Domain\Booking\Models\Booking;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие отмены бронирования
 */
class BookingCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Booking $booking,
        public User $cancelledBy,
        public float $cancellationFee = 0
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        $channels = [
            // Канал присутствия для админов
            new PresenceChannel('admin.bookings'),
        ];

        // Уведомляем противоположную сторону
        if ($this->cancelledBy->id === $this->booking->client_id) {
            // Отменил клиент - уведомляем мастера
            $channels[] = new PrivateChannel('master.' . $this->booking->master_id);
        } else {
            // Отменил мастер - уведомляем клиента
            $channels[] = new PrivateChannel('user.' . $this->booking->client_id);
        }

        return $channels;
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'booking.cancelled';
    }

    /**
     * Данные для трансляции
     */
    public function broadcastWith(): array
    {
        $isCancelledByClient = $this->cancelledBy->id === $this->booking->client_id;
        $isCancelledByMaster = $this->cancelledBy->id === $this->booking->master_id;

        // Определяем заголовок и сообщение в зависимости от того, кто отменил
        if ($isCancelledByClient) {
            $title = 'Клиент отменил бронирование';
            $message = "Клиент {$this->booking->client_name} отменил бронирование #{$this->booking->booking_number}";
        } elseif ($isCancelledByMaster) {
            $title = 'Мастер отменил бронирование';
            $message = "Ваше бронирование #{$this->booking->booking_number} отменено мастером";
        } else {
            $title = 'Бронирование отменено';
            $message = "Бронирование #{$this->booking->booking_number} отменено администрацией";
        }

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
                'cancelled_at' => $this->booking->cancelled_at->toISOString(),
                'cancellation_reason' => $this->booking->cancellation_reason,
                'start_time' => $this->booking->start_time->toISOString(),
                'total_price' => $this->booking->total_price,
            ],
            'cancelled_by' => [
                'id' => $this->cancelledBy->id,
                'name' => $this->cancelledBy->name,
                'role' => $isCancelledByClient ? 'client' : ($isCancelledByMaster ? 'master' : 'admin'),
            ],
            'cancellation' => [
                'fee' => $this->cancellationFee,
                'fee_formatted' => number_format($this->cancellationFee, 0, ',', ' ') . ' руб.',
                'has_fee' => $this->cancellationFee > 0,
                'refund_info' => $this->getRefundInfo(),
            ],
            'timestamp' => now()->toISOString(),
            'notification' => [
                'title' => $title,
                'message' => $message,
                'type' => 'booking_cancelled',
                'priority' => 'high',
                'style' => 'warning',
                'details' => [
                    'Время бронирования' => $this->booking->start_time->format('d.m.Y в H:i'),
                    'Причина отмены' => $this->booking->cancellation_reason,
                ] + ($this->cancellationFee > 0 ? [
                    'Штраф за отмену' => number_format($this->cancellationFee, 0, ',', ' ') . ' руб.'
                ] : []),
            ],
        ];
    }

    /**
     * Получить информацию о возврате
     */
    protected function getRefundInfo(): array
    {
        $paidAmount = $this->booking->paid_amount ?? 0;
        
        if ($paidAmount <= 0) {
            return [
                'type' => 'no_payment',
                'message' => 'Оплата не производилась'
            ];
        }

        $refundAmount = max(0, $paidAmount - $this->cancellationFee);

        if ($refundAmount <= 0) {
            return [
                'type' => 'no_refund',
                'message' => 'Возврат не производится - весь платеж удержан в качестве штрафа'
            ];
        }

        return [
            'type' => 'refund_processed',
            'amount' => $refundAmount,
            'amount_formatted' => number_format($refundAmount, 0, ',', ' ') . ' руб.',
            'message' => "Возврат {$refundAmount} руб. будет зачислен в течение 3-5 рабочих дней"
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->booking->is_cancelled;
    }
}