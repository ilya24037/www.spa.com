<?php

namespace App\Events\Payment\Handlers;

use App\Domain\Payment\Models\Payment;
use App\Enums\PaymentMethod;

/**
 * Обработчик деталей ошибок платежа
 */
class ErrorDetailsHandler
{
    /**
     * Получить детали ошибки
     */
    public function getErrorDetails(Payment $payment, ?string $reason = null): array
    {
        return [
            'error_code' => $this->getErrorCode($payment, $reason),
            'error_category' => $this->getErrorCategory($payment, $reason),
            'is_retryable' => $this->canRetry($payment, $reason),
            'estimated_retry_time' => $this->getEstimatedRetryTime($payment, $reason),
            'user_actionable' => $this->isUserActionable($payment, $reason),
        ];
    }

    /**
     * Получить код ошибки
     */
    public function getErrorCode(Payment $payment, ?string $reason = null): string
    {
        $effectiveReason = $reason ?? $payment->notes ?? 'unknown';
        return md5($effectiveReason);
    }

    /**
     * Получить категорию ошибки
     */
    public function getErrorCategory(Payment $payment, ?string $reason = null): string
    {
        $effectiveReason = strtolower($reason ?? $payment->notes ?? '');
        
        if (str_contains($effectiveReason, 'insufficient') || str_contains($effectiveReason, 'balance')) {
            return 'insufficient_funds';
        }
        
        if (str_contains($effectiveReason, 'card') || str_contains($effectiveReason, 'expired') || str_contains($effectiveReason, 'invalid')) {
            return 'card_issue';
        }
        
        if (str_contains($effectiveReason, 'network') || str_contains($effectiveReason, 'timeout') || str_contains($effectiveReason, 'connection')) {
            return 'network_issue';
        }
        
        if (str_contains($effectiveReason, 'bank') || str_contains($effectiveReason, 'declined')) {
            return 'bank_declined';
        }

        if (str_contains($effectiveReason, 'limit')) {
            return 'limit_exceeded';
        }

        if (str_contains($effectiveReason, '3ds') || str_contains($effectiveReason, 'authentication')) {
            return 'authentication_failed';
        }
        
        return 'technical_error';
    }

    /**
     * Можно ли повторить попытку
     */
    public function canRetry(Payment $payment, ?string $reason = null): bool
    {
        $category = $this->getErrorCategory($payment, $reason);
        
        // Можно повторить для сетевых ошибок и технических проблем
        return in_array($category, ['network_issue', 'technical_error', 'authentication_failed']);
    }

    /**
     * Получить время для повторной попытки
     */
    public function getEstimatedRetryTime(Payment $payment, ?string $reason = null): ?string
    {
        if (!$this->canRetry($payment, $reason)) {
            return null;
        }
        
        $category = $this->getErrorCategory($payment, $reason);
        
        return match($category) {
            'network_issue' => '5 минут',
            'technical_error' => '15 минут',
            'authentication_failed' => '2 минуты',
            default => '10 минут',
        };
    }

    /**
     * Требует ли ошибка действий от пользователя
     */
    public function isUserActionable(Payment $payment, ?string $reason = null): bool
    {
        $category = $this->getErrorCategory($payment, $reason);
        
        return in_array($category, ['insufficient_funds', 'card_issue', 'limit_exceeded']);
    }

    /**
     * Получить варианты повтора
     */
    public function getRetryOptions(Payment $payment, ?string $reason = null): array
    {
        if (!$this->canRetry($payment, $reason)) {
            return [];
        }

        $category = $this->getErrorCategory($payment, $reason);
        $intervalMinutes = match($category) {
            'network_issue' => 5,
            'authentication_failed' => 2,
            default => 10,
        };

        return [
            'automatic_retry' => [
                'enabled' => true,
                'attempts' => 3,
                'interval_minutes' => $intervalMinutes,
                'next_attempt_at' => now()->addMinutes($intervalMinutes)->toISOString(),
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
    public function getAlternativeMethods(Payment $payment): array
    {
        $currentMethod = $payment->method;
        $amount = $payment->amount;
        
        $availableMethods = PaymentMethod::getAvailableForAmount($amount);
        
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
}