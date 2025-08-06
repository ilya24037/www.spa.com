<?php

namespace App\Domain\Payment\Services\Gateways;

use App\Domain\Payment\Models\Payment;

/**
 * Интерфейс платежного шлюза
 */
interface PaymentGatewayInterface
{
    /**
     * Создать платеж
     */
    public function createPayment(Payment $payment): array;

    /**
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool;

    /**
     * Проверить статус платежа
     */
    public function checkStatus(Payment $payment): array;
}