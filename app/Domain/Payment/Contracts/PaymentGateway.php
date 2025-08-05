<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;

/**
 * Расширенный интерфейс для платежных шлюзов
 * 
 * Каждый платежный шлюз должен реализовать этот интерфейс
 * для обеспечения единообразной работы с различными провайдерами
 */
interface PaymentGateway extends PaymentProcessorInterface
{
    /**
     * Создать платеж в платежном шлюзе
     * 
     * @param Payment $payment
     * @return array Данные для редиректа или отображения формы оплаты
     */
    public function createPayment(Payment $payment): array;

    /**
     * Обработать webhook от платежного шлюза
     * 
     * @param array $data Данные из webhook
     * @return bool Успешность обработки
     */
    public function handleWebhook(array $data): bool;

    /**
     * Проверить статус платежа во внешней системе
     * 
     * @param Payment $payment
     * @return array Статус и дополнительная информация
     */
    public function checkPaymentStatus(Payment $payment): array;

    /**
     * Валидировать данные webhook
     * 
     * @param array $data
     * @param array $headers
     * @return bool
     */
    public function validateWebhook(array $data, array $headers = []): bool;

    /**
     * Получить URL для редиректа после создания платежа
     * 
     * @param Payment $payment
     * @param array $response Ответ от createPayment
     * @return string|null
     */
    public function getRedirectUrl(Payment $payment, array $response): ?string;

    /**
     * Получить данные для отображения формы оплаты
     * 
     * @param Payment $payment
     * @param array $response Ответ от createPayment
     * @return array
     */
    public function getPaymentFormData(Payment $payment, array $response): array;

    /**
     * Получить идентификатор шлюза
     * 
     * @return string
     */
    public function getGatewayId(): string;

    /**
     * Получить название шлюза для отображения
     * 
     * @return string
     */
    public function getDisplayName(): string;

    /**
     * Проверить, поддерживает ли шлюз возвраты
     * 
     * @return bool
     */
    public function supportsRefunds(): bool;

    /**
     * Проверить, поддерживает ли шлюз частичные возвраты
     * 
     * @return bool
     */
    public function supportsPartialRefunds(): bool;

    /**
     * Получить поддерживаемые валюты
     * 
     * @return array
     */
    public function getSupportedCurrencies(): array;

    /**
     * Проверить, требуется ли 3D Secure
     * 
     * @param Payment $payment
     * @return bool
     */
    public function requires3DSecure(Payment $payment): bool;

    /**
     * Получить комиссию шлюза
     * 
     * @param float $amount
     * @param string $currency
     * @return float
     */
    public function calculateFee(float $amount, string $currency = 'RUB'): float;

    /**
     * Проверить доступность шлюза
     * 
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Получить тестовый режим
     * 
     * @return bool
     */
    public function isTestMode(): bool;
}