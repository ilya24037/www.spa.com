<?php

namespace App\DTOs\Payment;

use App\Enums\PaymentType;
use App\Enums\PaymentMethod;

/**
 * DTO для создания платежа
 */
class CreatePaymentDTO
{
    public function __construct(
        public int $userId,
        public float $amount,
        public PaymentType $type,
        public PaymentMethod $method,
        public string $currency = 'RUB',
        public ?string $description = null,
        public ?string $payableType = null,
        public ?int $payableId = null,
        public array $metadata = [],
        public ?float $fee = null,
        public ?string $externalId = null,
        public ?string $gateway = null,
        public array $gatewayData = [],
    ) {}

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            amount: (float) $data['amount'],
            type: PaymentType::from($data['type']),
            method: PaymentMethod::from($data['method']),
            currency: $data['currency'] ?? 'RUB',
            description: $data['description'] ?? null,
            payableType: $data['payable_type'] ?? null,
            payableId: $data['payable_id'] ?? null,
            metadata: $data['metadata'] ?? [],
            fee: isset($data['fee']) ? (float) $data['fee'] : null,
            externalId: $data['external_id'] ?? null,
            gateway: $data['gateway'] ?? null,
            gatewayData: $data['gateway_data'] ?? [],
        );
    }

    /**
     * Создать для оплаты услуги
     */
    public static function forService(
        int $userId,
        int $bookingId,
        float $amount,
        PaymentMethod $method,
        ?string $description = null
    ): self {
        return new self(
            userId: $userId,
            amount: $amount,
            type: PaymentType::SERVICE_PAYMENT,
            method: $method,
            description: $description ?? 'Оплата массажной услуги',
            payableType: 'App\Models\Booking',
            payableId: $bookingId,
        );
    }

    /**
     * Создать для депозитного платежа
     */
    public static function forDeposit(
        int $userId,
        int $bookingId,
        float $amount,
        PaymentMethod $method,
        ?string $description = null
    ): self {
        return new self(
            userId: $userId,
            amount: $amount,
            type: PaymentType::BOOKING_DEPOSIT,
            method: $method,
            description: $description ?? 'Депозит за бронирование',
            payableType: 'App\Models\Booking',
            payableId: $bookingId,
        );
    }

    /**
     * Создать для пополнения баланса
     */
    public static function forTopUp(
        int $userId,
        float $amount,
        PaymentMethod $method,
        ?string $description = null
    ): self {
        return new self(
            userId: $userId,
            amount: $amount,
            type: PaymentType::TOP_UP,
            method: $method,
            description: $description ?? 'Пополнение баланса',
            payableType: 'App\Models\User',
            payableId: $userId,
        );
    }

    /**
     * Создать для подписки
     */
    public static function forSubscription(
        int $userId,
        int $subscriptionId,
        float $amount,
        PaymentMethod $method,
        ?string $description = null
    ): self {
        return new self(
            userId: $userId,
            amount: $amount,
            type: PaymentType::SUBSCRIPTION,
            method: $method,
            description: $description ?? 'Оплата подписки',
            payableType: 'App\Models\Subscription',
            payableId: $subscriptionId,
        );
    }

    /**
     * Создать для вывода средств
     */
    public static function forWithdrawal(
        int $userId,
        float $amount,
        PaymentMethod $method,
        array $withdrawalData = [],
        ?string $description = null
    ): self {
        return new self(
            userId: $userId,
            amount: $amount,
            type: PaymentType::WITHDRAWAL,
            method: $method,
            description: $description ?? 'Вывод средств',
            payableType: 'App\Models\User',
            payableId: $userId,
            metadata: array_merge(['withdrawal_data' => $withdrawalData], []),
        );
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'type' => $this->type,
            'method' => $this->method,
            'currency' => $this->currency,
            'description' => $this->description,
            'payable_type' => $this->payableType,
            'payable_id' => $this->payableId,
            'metadata' => $this->metadata,
            'fee' => $this->fee,
            'external_id' => $this->externalId,
            'gateway' => $this->gateway,
            'gateway_data' => $this->gatewayData,
        ];
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->amount <= 0) {
            $errors[] = 'Amount must be greater than 0';
        }

        if (!$this->method->isAvailableForAmount($this->amount)) {
            $errors[] = "Payment method {$this->method->value} is not available for amount {$this->amount}";
        }

        if (!in_array($this->method, $this->type->getAvailablePaymentMethods())) {
            $errors[] = "Payment method {$this->method->value} is not available for type {$this->type->value}";
        }

        if (!in_array($this->currency, $this->method->getSupportedCurrencies())) {
            $errors[] = "Currency {$this->currency} is not supported by method {$this->method->value}";
        }

        return $errors;
    }

    /**
     * Проверить валидность
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Получить общую сумму с комиссией
     */
    public function getTotalAmount(): float
    {
        $fee = $this->fee ?? $this->method->calculateFee($this->amount);
        return $this->amount + $fee;
    }

    /**
     * Получить комиссию
     */
    public function getFee(): float
    {
        return $this->fee ?? $this->method->calculateFee($this->amount);
    }

    /**
     * Установить метаданные
     */
    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = array_merge($this->metadata, $metadata);
        return $clone;
    }

    /**
     * Установить внешний ID
     */
    public function withExternalId(string $externalId): self
    {
        $clone = clone $this;
        $clone->externalId = $externalId;
        return $clone;
    }

    /**
     * Установить шлюз
     */
    public function withGateway(string $gateway, array $gatewayData = []): self
    {
        $clone = clone $this;
        $clone->gateway = $gateway;
        $clone->gatewayData = $gatewayData;
        return $clone;
    }

    /**
     * Установить комиссию
     */
    public function withFee(float $fee): self
    {
        $clone = clone $this;
        $clone->fee = $fee;
        return $clone;
    }
}