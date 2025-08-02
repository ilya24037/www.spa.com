<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;

/**
 * Интерфейс для процессоров платежей
 */
interface PaymentProcessorInterface
{
    /**
     * Обработать платеж
     */
    public function process(Payment $payment): PaymentResultDTO;

    /**
     * Подтвердить платеж
     */
    public function confirm(Payment $payment, array $data): PaymentResultDTO;

    /**
     * Отменить платеж
     */
    public function cancel(Payment $payment, ?string $reason = null): PaymentResultDTO;

    /**
     * Вернуть платеж
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResultDTO;

    /**
     * Получить статус платежа
     */
    public function getStatus(Payment $payment): string;

    /**
     * Проверить, поддерживается ли валюта
     */
    public function supportsCurrency(string $currency): bool;

    /**
     * Получить минимальную сумму платежа
     */
    public function getMinimumAmount(): float;

    /**
     * Получить максимальную сумму платежа
     */
    public function getMaximumAmount(): float;
}