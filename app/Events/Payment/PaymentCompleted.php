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
 * Событие успешного завершения платежа
 */
class PaymentCompleted implements ShouldBroadcast
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
        return 'payment.completed';
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
                'confirmed_at' => $this->payment->confirmed_at->toISOString(),
                'processing_time' => $this->payment->processing_time,
            ],
            'user' => [
                'id' => $this->payment->user_id,
                'name' => $this->payment->user->name,
            ],
            'payable' => $this->getPayableData(),
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => 'payment_completed',
                'priority' => 'high',
                'style' => 'success',
                'sound' => true,
                'actions' => $this->getNotificationActions(),
            ],
            'celebration' => [
                'enabled' => $this->shouldCelebrate(),
                'animation' => 'success',
                'duration' => 3000,
            ],
            'rewards' => $this->getRewards(),
            'next_actions' => $this->getNextActions(),
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
            'App\Models\User' => [
                'type' => 'user_balance',
                'id' => $this->payment->payable->id,
                'name' => $this->payment->payable->name,
                'new_balance' => $this->payment->payable->balance?->amount,
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
            \App\Enums\PaymentType::SERVICE_PAYMENT => 'Услуга оплачена!',
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 'Депозит зачислен!',
            \App\Enums\PaymentType::SUBSCRIPTION => 'Подписка активирована!',
            \App\Enums\PaymentType::TOP_UP => 'Баланс пополнен!',
            \App\Enums\PaymentType::WITHDRAWAL => 'Средства выведены',
            default => 'Платеж завершен!',
        };
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        $amount = $this->payment->formatted_amount;

        return match($this->payment->type) {
            \App\Enums\PaymentType::SERVICE_PAYMENT => 
                "Ваш платеж на сумму {$amount} успешно обработан. Услуга подтверждена!",
                
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 
                "Депозит {$amount} зачислен. Ваше бронирование подтверждено!",
                
            \App\Enums\PaymentType::SUBSCRIPTION => 
                "Подписка активирована. Списано {$amount}. Добро пожаловать в премиум!",
                
            \App\Enums\PaymentType::TOP_UP => 
                "Баланс успешно пополнен на {$amount}. Средства уже доступны!",
                
            \App\Enums\PaymentType::WITHDRAWAL => 
                "Запрос на вывод {$amount} обработан. Средства поступят в течение 2-3 дней.",
                
            default => 
                "Ваш платеж на сумму {$amount} успешно завершен!",
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
                if ($this->payment->payable_type === 'App\Models\Booking') {
                    $actions[] = [
                        'label' => 'Детали бронирования',
                        'url' => route('bookings.show', $this->payment->payable_id),
                        'primary' => true,
                    ];
                }
                break;

            case \App\Enums\PaymentType::SUBSCRIPTION:
                $actions[] = [
                    'label' => 'Премиум возможности',
                    'url' => route('premium.features'),
                    'primary' => true,
                ];
                break;

            case \App\Enums\PaymentType::TOP_UP:
                $actions[] = [
                    'label' => 'Мой баланс',
                    'url' => route('profile.balance'),
                    'primary' => true,
                ];
                break;
        }

        // Чек/квитанция
        $actions[] = [
            'label' => 'Скачать чек',
            'url' => route('payments.receipt', $this->payment->id),
            'download' => true,
        ];

        return $actions;
    }

    /**
     * Должны ли показать анимацию празднования
     */
    protected function shouldCelebrate(): bool
    {
        // Показываем анимацию для:
        // - Первого платежа пользователя
        // - Крупных сумм
        // - Активации подписки

        if ($this->isFirstPayment()) {
            return true;
        }

        if ($this->payment->amount >= 10000) {
            return true;
        }

        if ($this->payment->type === \App\Enums\PaymentType::SUBSCRIPTION) {
            return true;
        }

        return false;
    }

    /**
     * Проверить, первый ли это платеж пользователя
     */
    protected function isFirstPayment(): bool
    {
        return Payment::where('user_id', $this->payment->user_id)
            ->where('status', \App\Enums\PaymentStatus::COMPLETED)
            ->where('id', '!=', $this->payment->id)
            ->doesntExist();
    }

    /**
     * Получить награды/бонусы
     */
    protected function getRewards(): array
    {
        $rewards = [];

        // Бонус за первый платеж
        if ($this->isFirstPayment()) {
            $rewards[] = [
                'type' => 'first_payment_bonus',
                'title' => 'Бонус за первый платеж!',
                'description' => 'Вы получили 500 бонусных рублей',
                'amount' => 500,
                'icon' => '🎁',
            ];
        }

        // Кэшбэк за крупный платеж
        if ($this->payment->amount >= 5000) {
            $cashback = round($this->payment->amount * 0.02, 2); // 2% кэшбэк
            $rewards[] = [
                'type' => 'cashback',
                'title' => '2% кэшбэк',
                'description' => "Начислено {$cashback} руб. на баланс",
                'amount' => $cashback,
                'icon' => '💰',
            ];
        }

        return $rewards;
    }

    /**
     * Получить следующие действия
     */
    protected function getNextActions(): array
    {
        return match($this->payment->type) {
            \App\Enums\PaymentType::SERVICE_PAYMENT => [
                'Дождитесь подтверждения от мастера',
                'Подготовьтесь к сеансу массажа',
                'В случае вопросов обращайтесь в поддержку',
            ],
            
            \App\Enums\PaymentType::BOOKING_DEPOSIT => [
                'Ваше бронирование подтверждено',
                'Мастер свяжется с вами для уточнения деталей',
                'Оставшуюся сумму можно доплатить после услуги',
            ],
            
            \App\Enums\PaymentType::SUBSCRIPTION => [
                'Исследуйте новые премиум возможности',
                'Получите доступ к эксклюзивным мастерам',
                'Пользуйтесь расширенными фильтрами поиска',
            ],
            
            \App\Enums\PaymentType::TOP_UP => [
                'Средства доступны для использования',
                'Оплачивайте услуги с баланса',
                'Получайте бонусы за активность',
            ],
            
            default => [
                'Операция успешно завершена',
                'Проверьте детали в личном кабинете',
            ],
        };
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return $this->payment->status === \App\Enums\PaymentStatus::COMPLETED;
    }

    /**
     * Приоритетная очередь для важных платежей
     */
    public function broadcastQueue(): string
    {
        if ($this->payment->amount > 50000) {
            return 'high-priority-broadcasts';
        }
        
        return 'broadcasts';
    }
}