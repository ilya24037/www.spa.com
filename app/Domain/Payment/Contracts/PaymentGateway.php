<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;

/**
 * Интерфейс для платежных шлюзов
 */
interface PaymentGateway
{
    /**
     * Создать платеж
     */
    public function createPayment(Payment $payment): array;

    /**
     * Проверить статус платежа
     */
    public function checkPaymentStatus(Payment $payment): array;

    /**
     * Обработать webhook
     */
    public function handleWebhook(array $data): bool;

    /**
     * Выполнить возврат
     */
    public function refund(Payment $payment, ?float $amount = null): PaymentResultDTO;

    /**
     * Валидировать webhook
     */
    public function validateWebhook(array $data, array $headers = []): bool;

    /**
     * Получить комиссию
     */
    public function calculateFee(float $amount, string $currency = 'RUB'): float;

    /**
     * Проверить доступность шлюза
     */
    public function isAvailable(): bool;

    /**
     * Получить поддерживаемые валюты
     */
    public function getSupportedCurrencies(): array;

    /**
     * Поддерживает ли возвраты
     */
    public function supportsRefunds(): bool;

    /**
     * Поддерживает ли частичные возвраты
     */
    public function supportsPartialRefunds(): bool;
}