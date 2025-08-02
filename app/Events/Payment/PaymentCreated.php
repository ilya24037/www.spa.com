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
 * Событие создания платежа
 */
class PaymentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment
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
        return 'payment.created';
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
                'fee' => $this->payment->fee,
                'total_amount' => $this->payment->total_amount,
                'type' => $this->payment->type->value,
                'type_label' => $this->payment->type->getLabel(),
                'method' => $this->payment->method->value,
                'method_label' => $this->payment->method->getLabel(),
                'description' => $this->payment->description,
                'created_at' => $this->payment->created_at->toISOString(),
                'expires_in' => $this->payment->expires_in,
            ],
            'user' => [
                'id' => $this->payment->user_id,
                'name' => $this->payment->user->name,
            ],
            'payable' => $this->getPayableData(),
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => 'payment_created',
                'priority' => $this->getNotificationPriority(),
                'style' => 'info',
                'actions' => $this->getNotificationActions(),
            ],
            'metadata' => [
                'requires_action' => $this->requiresUserAction(),
                'next_steps' => $this->getNextSteps(),
                'payment_url' => $this->getPaymentUrl(),
            ],
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
            ],
            'App\Models\User' => [
                'type' => 'user',
                'id' => $this->payment->payable->id,
                'name' => $this->payment->payable->name,
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
            \App\Enums\PaymentType::SERVICE_PAYMENT => 'Создан платеж за услугу',
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 'Создан депозитный платеж',
            \App\Enums\PaymentType::SUBSCRIPTION => 'Создан платеж подписки',
            \App\Enums\PaymentType::TOP_UP => 'Создан платеж пополнения',
            \App\Enums\PaymentType::WITHDRAWAL => 'Создан запрос на вывод',
            default => 'Создан новый платеж',
        };
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        $amount = $this->payment->formatted_amount;
        $method = $this->payment->method->getLabel();

        return match($this->payment->type) {
            \App\Enums\PaymentType::SERVICE_PAYMENT => 
                "Создан платеж на сумму {$amount} за услугу. Способ оплаты: {$method}",
                
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 
                "Создан депозитный платеж на сумму {$amount}. Способ оплаты: {$method}",
                
            \App\Enums\PaymentType::SUBSCRIPTION => 
                "Создан платеж подписки на сумму {$amount}. Способ оплаты: {$method}",
                
            \App\Enums\PaymentType::TOP_UP => 
                "Создан платеж пополнения баланса на сумму {$amount}. Способ оплаты: {$method}",
                
            \App\Enums\PaymentType::WITHDRAWAL => 
                "Создан запрос на вывод средств на сумму {$amount}",
                
            default => 
                "Создан платеж на сумму {$amount}. Способ оплаты: {$method}",
        };
    }

    /**
     * Получить приоритет уведомления
     */
    protected function getNotificationPriority(): string
    {
        if ($this->payment->amount > 50000) {
            return 'high';
        }

        if ($this->payment->type === \App\Enums\PaymentType::WITHDRAWAL) {
            return 'high';
        }

        return 'medium';
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

        // Действие для оплаты (если требуется)
        if ($this->requiresUserAction()) {
            $actions[] = [
                'label' => 'Перейти к оплате',
                'url' => $this->getPaymentUrl(),
                'primary' => true,
            ];
        }

        // Действие отмены (если возможно)
        if ($this->payment->isCancellable()) {
            $actions[] = [
                'label' => 'Отменить',
                'url' => route('payments.cancel', $this->payment->id),
                'confirm' => true,
                'style' => 'secondary',
            ];
        }

        // Связанные действия
        if ($this->payment->payable_type === 'App\Models\Booking') {
            $actions[] = [
                'label' => 'Детали бронирования',
                'url' => route('bookings.show', $this->payment->payable_id),
            ];
        }

        return $actions;
    }

    /**
     * Требует ли платеж действий от пользователя
     */
    protected function requiresUserAction(): bool
    {
        return $this->payment->status === \App\Enums\PaymentStatus::PENDING &&
               $this->payment->method->requiresConfirmation();
    }

    /**
     * Получить следующие шаги
     */
    protected function getNextSteps(): array
    {
        if (!$this->requiresUserAction()) {
            return ['Платеж будет обработан автоматически'];
        }

        return match($this->payment->method) {
            \App\Enums\PaymentMethod::CASH => [
                'Подготовьте точную сумму к встрече с мастером',
                'Оплатите услугу наличными при получении',
            ],
            \App\Enums\PaymentMethod::TRANSFER => [
                'Выполните банковский перевод по указанным реквизитам',
                'Сохраните чек об оплате',
                'Платеж будет подтвержден в течение суток',
            ],
            default => [
                'Завершите оплату в безопасной форме',
                'После подтверждения вы получите уведомление',
            ],
        };
    }

    /**
     * Получить URL для завершения оплаты
     */
    protected function getPaymentUrl(): string
    {
        return route('payments.process', $this->payment->id);
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return true; // Всегда транслируем создание платежа
    }

    /**
     * Данные для очереди (если событие обрабатывается асинхронно)
     */
    public function broadcastQueue(): string
    {
        return 'broadcasts';
    }

    /**
     * Задержка трансляции
     */
    public function broadcastAfter(): ?\DateTime
    {
        // Немедленная трансляция для важных платежей
        if ($this->payment->amount > 100000) {
            return null;
        }

        // Небольшая задержка для остальных
        return now()->addSeconds(2);
    }
}