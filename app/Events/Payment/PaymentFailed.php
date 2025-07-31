<?php

namespace App\Events\Payment;

use App\Models\Payment;
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
        return 'payment.failed';
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
                'failed_at' => $this->payment->failed_at?->toISOString(),
                'failure_reason' => $this->reason ?? $this->payment->notes,
            ],
            'user' => [
                'id' => $this->payment->user_id,
                'name' => $this->payment->user->name,
            ],
            'payable' => $this->getPayableData(),
            'notification' => [
                'title' => $this->getNotificationTitle(),
                'message' => $this->getNotificationMessage(),
                'type' => 'payment_failed',
                'priority' => 'high',
                'style' => 'error',
                'persistent' => true, // Не скрывать автоматически
                'actions' => $this->getNotificationActions(),
            ],
            'error_details' => $this->getErrorDetails(),
            'retry_options' => $this->getRetryOptions(),
            'alternative_methods' => $this->getAlternativeMethods(),
            'support_info' => $this->getSupportInfo(),
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
                'at_risk' => $this->isBookingAtRisk(),
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
            \App\Enums\PaymentType::SERVICE_PAYMENT => 'Ошибка оплаты услуги',
            \App\Enums\PaymentType::BOOKING_DEPOSIT => 'Ошибка оплаты депозита',
            \App\Enums\PaymentType::SUBSCRIPTION => 'Ошибка оплаты подписки',
            \App\Enums\PaymentType::TOP_UP => 'Ошибка пополнения баланса',
            \App\Enums\PaymentType::WITHDRAWAL => 'Ошибка вывода средств',
            default => 'Ошибка платежа',
        };
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        $amount = $this->payment->formatted_amount;
        $baseMessage = "Не удалось обработать платеж на сумму {$amount}.";
        
        $reason = $this->getReadableFailureReason();
        if ($reason) {
            $baseMessage .= " Причина: {$reason}";
        }

        $urgencyMessage = $this->getUrgencyMessage();
        if ($urgencyMessage) {
            $baseMessage .= " {$urgencyMessage}";
        }

        return $baseMessage;
    }

    /**
     * Получить читаемую причину ошибки
     */
    protected function getReadableFailureReason(): ?string
    {
        $reason = $this->reason ?? $this->payment->notes;
        
        if (!$reason) {
            return null;
        }

        // Маппинг технических ошибок на понятные пользователю
        $errorMap = [
            'insufficient_funds' => 'недостаточно средств на счете',
            'invalid_card' => 'неверные данные карты',
            'expired_card' => 'карта просрочена',
            'blocked_card' => 'карта заблокирована',
            'limit_exceeded' => 'превышен лимит',
            'network_error' => 'временные технические проблемы',
            'gateway_timeout' => 'превышено время ожидания',
            'declined_by_bank' => 'отказ банка',
            '3ds_failed' => 'ошибка 3D Secure',
        ];

        $lowerReason = strtolower($reason);
        
        foreach ($errorMap as $key => $translation) {
            if (str_contains($lowerReason, $key)) {
                return $translation;
            }
        }

        return 'техническая ошибка';
    }

    /**
     * Получить сообщение о срочности
     */
    protected function getUrgencyMessage(): ?string
    {
        if ($this->payment->payable_type === 'App\Models\Booking') {
            $booking = $this->payment->payable;
            
            if ($booking && $booking->start_time) {
                $hoursUntilStart = now()->diffInHours($booking->start_time, false);
                
                if ($hoursUntilStart > 0 && $hoursUntilStart <= 24) {
                    return "Услуга через {$hoursUntilStart} ч. - требуется срочная оплата!";
                } elseif ($hoursUntilStart <= 0) {
                    return "Время услуги уже наступило!";
                }
            }
        }

        return null;
    }

    /**
     * Получить действия для уведомления
     */
    protected function getNotificationActions(): array
    {
        $actions = [];

        // Повторить попытку
        if ($this->canRetry()) {
            $actions[] = [
                'label' => 'Повторить попытку',
                'url' => route('payments.retry', $this->payment->id),
                'primary' => true,
            ];
        }

        // Выбрать другой способ оплаты
        $actions[] = [
            'label' => 'Другой способ оплаты',
            'url' => route('payments.method', $this->payment->id),
        ];

        // Детали платежа
        $actions[] = [
            'label' => 'Детали платежа',
            'url' => route('payments.show', $this->payment->id),
        ];

        // Обратиться в поддержку
        $actions[] = [
            'label' => 'Связаться с поддержкой',
            'url' => route('support.create', ['payment_id' => $this->payment->id]),
        ];

        // Специфичные действия для бронирований
        if ($this->payment->payable_type === 'App\Models\Booking') {
            $actions[] = [
                'label' => 'Детали бронирования',
                'url' => route('bookings.show', $this->payment->payable_id),
            ];
        }

        return $actions;
    }

    /**
     * Получить детали ошибки
     */
    protected function getErrorDetails(): array
    {
        return [
            'error_code' => $this->getErrorCode(),
            'error_category' => $this->getErrorCategory(),
            'is_retryable' => $this->canRetry(),
            'estimated_retry_time' => $this->getEstimatedRetryTime(),
            'user_actionable' => $this->isUserActionable(),
        ];
    }

    /**
     * Получить код ошибки
     */
    protected function getErrorCode(): string
    {
        $reason = $this->reason ?? $this->payment->notes ?? 'unknown';
        return md5($reason);
    }

    /**
     * Получить категорию ошибки
     */
    protected function getErrorCategory(): string
    {
        $reason = strtolower($this->reason ?? $this->payment->notes ?? '');
        
        if (str_contains($reason, 'insufficient') || str_contains($reason, 'balance')) {
            return 'insufficient_funds';
        }
        
        if (str_contains($reason, 'card') || str_contains($reason, 'expired') || str_contains($reason, 'invalid')) {
            return 'card_issue';
        }
        
        if (str_contains($reason, 'network') || str_contains($reason, 'timeout') || str_contains($reason, 'connection')) {
            return 'network_issue';
        }
        
        if (str_contains($reason, 'bank') || str_contains($reason, 'declined')) {
            return 'bank_declined';
        }
        
        return 'technical_error';
    }

    /**
     * Можно ли повторить попытку
     */
    protected function canRetry(): bool
    {
        $category = $this->getErrorCategory();
        
        // Можно повторить для сетевых ошибок и технических проблем
        return in_array($category, ['network_issue', 'technical_error']);
    }

    /**
     * Получить время для повторной попытки
     */
    protected function getEstimatedRetryTime(): ?string
    {
        if (!$this->canRetry()) {
            return null;
        }
        
        return match($this->getErrorCategory()) {
            'network_issue' => '5 минут',
            'technical_error' => '15 минут',
            default => '10 минут',
        };
    }

    /**
     * Требует ли ошибка действий от пользователя
     */
    protected function isUserActionable(): bool
    {
        $category = $this->getErrorCategory();
        
        return in_array($category, ['insufficient_funds', 'card_issue']);
    }

    /**
     * Получить варианты повтора
     */
    protected function getRetryOptions(): array
    {
        if (!$this->canRetry()) {
            return [];
        }

        return [
            'automatic_retry' => [
                'enabled' => true,
                'attempts' => 3,
                'interval_minutes' => 5,
                'next_attempt_at' => now()->addMinutes(5)->toISOString(),
            ],
            'manual_retry' => [
                'enabled' => true,
                'cooldown_minutes' => 1,
            ],
        ];
    }

    /**
     * Получить альтернативные способы оплаты
     */
    protected function getAlternativeMethods(): array
    {
        $currentMethod = $this->payment->method;
        $amount = $this->payment->amount;
        
        $availableMethods = \App\Enums\PaymentMethod::getAvailableForAmount($amount);
        
        // Исключаем текущий метод
        $alternatives = array_filter($availableMethods, fn($method) => $method !== $currentMethod);
        
        return array_map(function($method) {
            return [
                'method' => $method->value,
                'label' => $method->getLabel(),
                'description' => $method->getDescription(),
                'icon' => $method->getIcon(),
                'processing_time' => $method->getProcessingTime(),
                'is_instant' => $method->isInstant(),
            ];
        }, array_slice($alternatives, 0, 3)); // Показываем только 3 альтернативы
    }

    /**
     * Получить информацию для поддержки
     */
    protected function getSupportInfo(): array
    {
        return [
            'contact_methods' => [
                [
                    'type' => 'chat',
                    'label' => 'Чат с поддержкой',
                    'url' => route('support.chat'),
                    'available_24_7' => true,
                ],
                [
                    'type' => 'phone',
                    'label' => 'Горячая линия',
                    'phone' => '+7 (800) 555-0123',
                    'hours' => '9:00 - 21:00',
                ],
                [
                    'type' => 'email',
                    'label' => 'Email поддержка',
                    'email' => 'payments@spa.com',
                    'response_time' => '30 минут',
                ],
            ],
            'helpful_links' => [
                [
                    'title' => 'Частые вопросы об оплате',
                    'url' => route('help.payments'),
                ],
                [
                    'title' => 'Что делать если платеж не прошел',
                    'url' => route('help.payment-failed'),
                ],
            ],
        ];
    }

    /**
     * Находится ли бронирование под угрозой отмены
     */
    protected function isBookingAtRisk(): bool
    {
        if ($this->payment->payable_type !== 'App\Models\Booking') {
            return false;
        }

        $booking = $this->payment->payable;
        if (!$booking || !$booking->start_time) {
            return false;
        }

        // Бронирование под угрозой если до начала меньше 4 часов
        return now()->diffInHours($booking->start_time, false) <= 4;
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
        if ($this->isBookingAtRisk()) {
            return 'critical-broadcasts';
        }
        
        return 'broadcasts';
    }
}