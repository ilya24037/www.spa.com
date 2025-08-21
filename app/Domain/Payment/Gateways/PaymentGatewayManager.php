<?php

namespace App\Domain\Payment\Gateways;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\DTOs\PaymentResultDTO;

/**
 * Менеджер платежных шлюзов
 * Управляет выбором и переключением между различными платежными провайдерами
 */
class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentGateway> Зарегистрированные шлюзы
     */
    private array $gateways = [];

    /**
     * @var string Шлюз по умолчанию
     */
    private string $defaultGateway = 'stripe';

    public function __construct(array $gateways = [])
    {
        $this->gateways = $gateways;
    }

    /**
     * Получить шлюз по имени
     */
    public function gateway(string $name): PaymentGateway
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Gateway '{$name}' not registered");
        }

        return $this->gateways[$name];
    }

    /**
     * Получить шлюз по умолчанию
     */
    public function defaultGateway(): PaymentGateway
    {
        return $this->gateway($this->defaultGateway);
    }

    /**
     * Зарегистрировать шлюз
     */
    public function register(string $name, PaymentGateway $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    /**
     * Установить шлюз по умолчанию
     */
    public function setDefault(string $name): void
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Gateway '{$name}' not registered");
        }

        $this->defaultGateway = $name;
    }

    /**
     * Получить список доступных шлюзов
     */
    public function available(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Получить информацию о всех шлюзах
     */
    public function getAll(): array
    {
        $result = [];
        
        foreach ($this->gateways as $name => $gateway) {
            $result[$name] = [
                'name' => $name,
                'display_name' => $gateway->getDisplayName(),
                'is_available' => $gateway->isAvailable(),
                'supported_currencies' => $gateway->getSupportedCurrencies(),
                'supports_refunds' => $gateway->supportsRefunds(),
                'minimum_amount' => $gateway->getMinimumAmount(),
                'maximum_amount' => $gateway->getMaximumAmount(),
            ];
        }

        return $result;
    }

    /**
     * Выбрать лучший шлюз для платежа
     */
    public function selectBestGateway(Payment $payment): PaymentGateway
    {
        // Простая логика выбора - можно расширить
        foreach ($this->gateways as $name => $gateway) {
            if ($gateway->isAvailable() && 
                $gateway->supportsCurrency($payment->currency) &&
                $payment->amount >= $gateway->getMinimumAmount() &&
                $payment->amount <= $gateway->getMaximumAmount()) {
                return $gateway;
            }
        }

        // Если ни один не подходит, используем шлюз по умолчанию
        return $this->defaultGateway();
    }

    /**
     * Обработать платеж через подходящий шлюз
     */
    public function processPayment(Payment $payment): PaymentResultDTO
    {
        $gateway = $this->selectBestGateway($payment);
        return $gateway->process($payment);
    }

    /**
     * Проверить статус платежа
     */
    public function checkPaymentStatus(Payment $payment): array
    {
        if (!$payment->gateway) {
            throw new \InvalidArgumentException('Payment gateway not specified');
        }

        $gateway = $this->gateway($payment->gateway);
        return $gateway->checkPaymentStatus($payment);
    }

    /**
     * Возврат платежа
     */
    public function refundPayment(Payment $payment, ?float $amount = null): PaymentResultDTO
    {
        if (!$payment->gateway) {
            throw new \InvalidArgumentException('Payment gateway not specified');
        }

        $gateway = $this->gateway($payment->gateway);
        return $gateway->refund($payment, $amount);
    }

    /**
     * Отмена платежа
     */
    public function cancelPayment(Payment $payment, ?string $reason = null): PaymentResultDTO
    {
        if (!$payment->gateway) {
            throw new \InvalidArgumentException('Payment gateway not specified');
        }

        $gateway = $this->gateway($payment->gateway);
        return $gateway->cancel($payment, $reason);
    }

    /**
     * Обработать webhook
     */
    public function handleWebhook(string $gatewayName, array $data): bool
    {
        $gateway = $this->gateway($gatewayName);
        return $gateway->handleWebhook($data);
    }

    /**
     * Получить доступные методы оплаты
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        
        foreach ($this->gateways as $name => $gateway) {
            if ($gateway->isAvailable()) {
                $methods[] = [
                    'gateway' => $name,
                    'display_name' => $gateway->getDisplayName(),
                    'currencies' => $gateway->getSupportedCurrencies(),
                    'min_amount' => $gateway->getMinimumAmount(),
                    'max_amount' => $gateway->getMaximumAmount(),
                ];
            }
        }

        return $methods;
    }

    /**
     * Рассчитать комиссию для платежа
     */
    public function calculateFee(Payment $payment): float
    {
        $gateway = $this->selectBestGateway($payment);
        return $gateway->calculateFee($payment->amount, $payment->currency);
    }

    /**
     * Получить статистику по всем шлюзам
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        foreach ($this->gateways as $name => $gateway) {
            $stats[$name] = [
                'available' => $gateway->isAvailable(),
                'test_mode' => $gateway->isTestMode(),
            ];
        }

        return $stats;
    }
}