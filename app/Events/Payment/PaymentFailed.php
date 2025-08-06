<?php

namespace App\Events\Payment;

use App\Domain\Payment\Models\Payment;
use App\Events\Payment\Handlers\PaymentDataHandler;
use App\Events\Payment\Handlers\NotificationHandler;
use App\Events\Payment\Handlers\ErrorDetailsHandler;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие неудачного платежа
 */
class PaymentFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected PaymentDataHandler $dataHandler;
    protected NotificationHandler $notificationHandler;
    protected ErrorDetailsHandler $errorHandler;

    public function __construct(
        public Payment $payment,
        public ?string $reason = null
    ) {
        $this->dataHandler = new PaymentDataHandler();
        $this->notificationHandler = new NotificationHandler();
        $this->errorHandler = new ErrorDetailsHandler();
    }

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
            ...$this->dataHandler->getMasterChannels($this->payment),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'payment.failed';
    }

    /**
     * Данные для трансляции
     */
    public function broadcastWith(): array
    {
        return [
            'payment' => $this->getPaymentData(),
            'user' => $this->getUserData(),
            'payable' => $this->dataHandler->getPayableData($this->payment),
            'notification' => $this->getNotificationData(),
            'error_details' => $this->errorHandler->getErrorDetails($this->payment, $this->reason),
            'retry_options' => $this->errorHandler->getRetryOptions($this->payment, $this->reason),
            'alternative_methods' => $this->errorHandler->getAlternativeMethods($this->payment),
            'support_info' => $this->notificationHandler->getSupportInfo(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить данные платежа
     */
    protected function getPaymentData(): array
    {
        return [
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
            'failed_at' => $this->payment->failed_at?->toISOString(),
            'failure_reason' => $this->reason ?? $this->payment->notes,
        ];
    }

    /**
     * Получить данные пользователя
     */
    protected function getUserData(): array
    {
        return [
            'id' => $this->payment->user_id,
            'name' => $this->payment->user->name,
        ];
    }

    /**
     * Получить данные уведомления
     */
    protected function getNotificationData(): array
    {
        return [
            'title' => $this->notificationHandler->getNotificationTitle($this->payment),
            'message' => $this->notificationHandler->getNotificationMessage($this->payment, $this->reason),
            'type' => 'payment_failed',
            'priority' => 'high',
            'style' => 'error',
            'persistent' => true, // Не скрывать автоматически
            'actions' => $this->notificationHandler->getNotificationActions($this->payment, $this->errorHandler),
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return in_array($this->payment->status, [
            \App\Enums\PaymentStatus::FAILED,
            \App\Enums\PaymentStatus::CANCELLED,
            \App\Enums\PaymentStatus::EXPIRED,
        ]);
    }

    /**
     * Высокий приоритет для критичных ошибок
     */
    public function broadcastQueue(): string
    {
        if ($this->payment->payable_type === 'App\Models\Booking') {
            $booking = $this->payment->payable;
            if ($this->dataHandler->isBookingAtRisk($booking)) {
                return 'critical-broadcasts';
            }
        }
        
        return 'broadcasts';
    }
}