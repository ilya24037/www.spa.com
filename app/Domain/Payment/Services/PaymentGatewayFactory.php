<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\PaymentProcessorInterface;
use App\Enums\PaymentMethod;
use InvalidArgumentException;

/**
 * Фабрика для создания процессоров платежей
 */
class PaymentGatewayFactory
{
    private array $processors = [];

    /**
     * Регистрировать процессор платежей
     */
    public function register(PaymentMethod $method, string $processorClass): void
    {
        if (!is_subclass_of($processorClass, PaymentProcessorInterface::class)) {
            throw new InvalidArgumentException(
                "Processor must implement PaymentProcessorInterface"
            );
        }

        $this->processors[$method->value] = $processorClass;
    }

    /**
     * Создать процессор для указанного метода платежа
     */
    public function create(PaymentMethod $method): PaymentProcessorInterface
    {
        if (!isset($this->processors[$method->value])) {
            throw new InvalidArgumentException(
                "No processor registered for payment method: {$method->value}"
            );
        }

        $processorClass = $this->processors[$method->value];
        return app($processorClass);
    }

    /**
     * Проверить, поддерживается ли метод платежа
     */
    public function supports(PaymentMethod $method): bool
    {
        return isset($this->processors[$method->value]);
    }

    /**
     * Получить все поддерживаемые методы платежа
     */
    public function getSupportedMethods(): array
    {
        return array_keys($this->processors);
    }
}