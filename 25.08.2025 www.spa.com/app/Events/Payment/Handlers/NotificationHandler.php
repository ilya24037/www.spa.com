<?php

namespace App\Events\Payment\Handlers;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentType;

/**
 * Обработчик уведомлений для события неудачного платежа
 */
class NotificationHandler
{
    /**
     * Получить заголовок уведомления
     */
    public function getNotificationTitle(Payment $payment): string
    {
        return match($payment->type) {
            PaymentType::SERVICE_PAYMENT => 'Ошибка оплаты услуги',
            PaymentType::BOOKING_DEPOSIT => 'Ошибка оплаты депозита',
            PaymentType::SUBSCRIPTION => 'Ошибка оплаты подписки',
            PaymentType::TOP_UP => 'Ошибка пополнения баланса',
            PaymentType::WITHDRAWAL => 'Ошибка вывода средств',
            default => 'Ошибка платежа',
        };
    }

    /**
     * Получить сообщение уведомления
     */
    public function getNotificationMessage(Payment $payment, ?string $reason = null): string
    {
        $amount = $payment->formatted_amount;
        $baseMessage = "Не удалось обработать платеж на сумму {$amount}.";
        
        $readableReason = $this->getReadableFailureReason($reason ?? $payment->notes);
        if ($readableReason) {
            $baseMessage .= " Причина: {$readableReason}";
        }

        $urgencyMessage = $this->getUrgencyMessage($payment);
        if ($urgencyMessage) {
            $baseMessage .= " {$urgencyMessage}";
        }

        return $baseMessage;
    }

    /**
     * Получить читаемую причину ошибки
     */
    public function getReadableFailureReason(?string $reason): ?string
    {
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
            'authentication_failed' => 'ошибка аутентификации',
            'invalid_cvv' => 'неверный CVV код',
            'card_not_supported' => 'карта не поддерживается',
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
    public function getUrgencyMessage(Payment $payment): ?string
    {
        if ($payment->payable_type === 'App\Models\Booking') {
            $booking = $payment->payable;
            
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
    public function getNotificationActions(Payment $payment, ErrorDetailsHandler $errorHandler): array
    {
        $actions = [];

        // Повторить попытку
        if ($errorHandler->canRetry($payment)) {
            $actions[] = [
                'label' => 'Повторить попытку',
                'url' => route('payments.retry', $payment->id),
                'primary' => true,
            ];
        }

        // Выбрать другой способ оплаты
        $actions[] = [
            'label' => 'Другой способ оплаты',
            'url' => route('payments.method', $payment->id),
        ];

        // Детали платежа
        $actions[] = [
            'label' => 'Детали платежа',
            'url' => route('payments.show', $payment->id),
        ];

        // Обратиться в поддержку
        $actions[] = [
            'label' => 'Связаться с поддержкой',
            'url' => route('support.create', ['payment_id' => $payment->id]),
        ];

        // Специфичные действия для бронирований
        if ($payment->payable_type === 'App\Models\Booking') {
            $actions[] = [
                'label' => 'Детали бронирования',
                'url' => route('bookings.show', $payment->payable_id),
            ];
        }

        return $actions;
    }

    /**
     * Получить информацию для поддержки
     */
    public function getSupportInfo(): array
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
                    'phone' => config('support.phone', '+7 (800) 555-0123'),
                    'hours' => config('support.hours', '9:00 - 21:00'),
                ],
                [
                    'type' => 'email',
                    'label' => 'Email поддержка',
                    'email' => config('support.email', 'payments@spa.com'),
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
}