<?php

namespace App\Events\Payment;

use App\Domain\Payment\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие отмены платежа
 */
class PaymentCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public ?string $reason = null
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для пользователя
            new PrivateChannel('user.' . $this->payment->user_id),
            
            // Канал присутствия для админов
            new PresenceChannel('admin.payments'),
            
            // Приватный канал для мастера (если платеж связан с бронированием)
            ...$this->getMasterChannels(),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'payment.cancelled';
    }

    /**
     * Данные для трансляции
     */
    public function broadcastWith(): array
    {
        return [
            'payment' => [
                'id' => $this->payment->id,
                'payment_number' => $this->payment->payment_number,
                'status' => $this->payment->status->value,
                'status_label' => $this->payment->status->getLabel(),
                'amount' => $this->payment->amount,
                'formatted_amount' => $this->payment->formatted_amount,
                'type' => $this->payment->type->value,
                'type_label' => $this->payment->type->getLabel(),
                'method' => $this->payment->method->value,
                'method_label' => $this->payment->method->getLabel(),
                'cancelled_at' => $this->payment->cancelled_at?->toISOString(),
                'cancellation_reason' => $this->reason,
            ],
            'user' => [
                'id' => $this->payment->user_id,
                'name' => $this->payment->user->name,
            ],
            'payable' => $this->getPayableData(),
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => 'payment_cancelled',
                'priority' => 'medium',
                'style' => 'warning',
                'sound' => false,
                'actions' => $this->getNotificationActions(),
            ],
            'next_actions' => $this->getNextActions(),
            'refund_info' => $this->getRefundInfo(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить каналы для мастера
     */
    protected function getMasterChannels(): array
    {
        if ($this->payment->payable_type === 'App\Models\Booking') {
            $booking = $this->payment->payable;
            if ($booking && $booking->master_id) {
                return [new PrivateChannel('master.' . $booking->master_id)];
            }
        }
        
        return [];
    }

    /**
     * Получить данные связанной сущности
     */
    protected function getPayableData(): ?array
    {
        if (!$this->payment->payable) {
            return null;
        }

        return match($this->payment->payable_type) {
            'App\Models\Booking' => [
                'type' => 'booking',
                'id' => $this->payment->payable->id,
                'booking_number' => $this->payment->payable->booking_number ?? null,
                'service_name' => $this->payment->payable->service?->name,
                'start_time' => $this->payment->payable->start_time?->toISOString(),
                'master_name' => $this->payment->payable->master?->name,
                'status' => $this->payment->payable->status,
            ],
            default => [
                'type' => 'other',
                'id' => $this->payment->payable->id,
            ],
        };
    }

    /**
     * Получить заголовок уведомления
     */
    protected function getNotificationTitle(): string
    {
        return match($this->payment->type) {
            \App\Enums\PaymentType::SERVICE_PAYMENT => 'Платеж за услугу отменен',
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 'Депозит отменен',
            \App\Enums\PaymentType::SUBSCRIPTION => 'Подписка отменена',
            \App\Enums\PaymentType::TOP_UP => 'Пополнение отменено',
            default => 'Платеж отменен',
        };
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        $amount = $this->payment->formatted_amount;
        $reason = $this->reason ? " Причина: {$this->reason}" : '';

        return match($this->payment->type) {
            \App\Enums\PaymentType::SERVICE_PAYMENT => 
                "Платеж за услугу на сумму {$amount} был отменен.{$reason} Средства будут возвращены в течение 5-10 рабочих дней.",
                
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 
                "Депозит {$amount} отменен.{$reason} Бронирование также отменено.",
                
            \App\Enums\PaymentType::SUBSCRIPTION => 
                "Платеж за подписку {$amount} отменен.{$reason} Подписка не активирована.",
                
            \App\Enums\PaymentType::TOP_UP => 
                "Пополнение баланса на {$amount} отменено.{$reason}",
                
            default => 
                "Платеж на сумму {$amount} был отменен.{$reason}",
        };
    }

    /**
     * Получить действия для уведомления
     */
    protected function getNotificationActions(): array
    {
        $actions = [
            [
                'label' => 'Детали платежа',
                'url' => route('payments.show', $this->payment->id),
            ],
        ];

        // Специфичные действия в зависимости от типа
        switch ($this->payment->type) {
            case \App\Enums\PaymentType::SERVICE_PAYMENT:
            case \App\Enums\PaymentType::BOOKING_DEPOSIT:
                $actions[] = [
                    'label' => 'Найти другого мастера',
                    'url' => route('masters.index'),
                    'primary' => true,
                ];
                break;

            case \App\Enums\PaymentType::SUBSCRIPTION:
                $actions[] = [
                    'label' => 'Выбрать план снова',
                    'url' => route('subscription.plans'),
                    'primary' => true,
                ];
                break;

            case \App\Enums\PaymentType::TOP_UP:
                $actions[] = [
                    'label' => 'Попробовать снова',
                    'url' => route('profile.balance.top-up'),
                    'primary' => true,
                ];
                break;
        }

        // Поддержка
        $actions[] = [
            'label' => 'Связаться с поддержкой',
            'url' => route('support.chat'),
        ];

        return $actions;
    }

    /**
     * Получить следующие действия
     */
    protected function getNextActions(): array
    {
        return match($this->payment->type) {
            \App\Enums\PaymentType::SERVICE_PAYMENT => [
                'Средства будут возвращены автоматически',
                'Найдите другого мастера в каталоге',
                'При проблемах обращайтесь в поддержку',
            ],
            
            \App\Enums\PaymentType::BOOKING_DEPOSIT => [
                'Бронирование автоматически отменено',
                'Депозит будет возвращен в течение 5-10 дней',
                'Вы можете забронировать другое время',
            ],
            
            \App\Enums\PaymentType::SUBSCRIPTION => [
                'Подписка не активирована',
                'Вы можете попробовать оплатить снова',
                'Или выбрать другой план подписки',
            ],
            
            \App\Enums\PaymentType::TOP_UP => [
                'Баланс не изменился',
                'Попробуйте другой способ оплаты',
                'Или обратитесь в поддержку',
            ],
            
            default => [
                'Средства будут возвращены',
                'Попробуйте оплатить снова',
                'Или свяжитесь с поддержкой',
            ],
        };
    }

    /**
     * Получить информацию о возврате
     */
    protected function getRefundInfo(): array
    {
        return [
            'automatic' => true,
            'processing_time' => '5-10 рабочих дней',
            'method' => 'На исходный способ оплаты',
            'amount' => $this->payment->amount,
            'formatted_amount' => $this->payment->formatted_amount,
            'fee_refunded' => true,
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->payment->status === \App\Enums\PaymentStatus::CANCELLED;
    }

    /**
     * Обычная очередь для отмененных платежей
     */
    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }
}