<?php

namespace App\Domain\Payment\DTOs;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\Currency;

class PaymentData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $payableType,
        public readonly int $payableId,
        public readonly float $amount,
        public readonly Currency $currency,
        public readonly PaymentMethod $method,
        public readonly PaymentStatus $status,
        public readonly ?string $transactionId,
        public readonly ?string $description,
        public readonly ?array $paymentData,
        public readonly ?string $failureReason,
        public readonly ?string $paidAt,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            payableType: $data['payable_type'],
            payableId: $data['payable_id'],
            amount: (float) $data['amount'],
            currency: isset($data['currency']) ? Currency::from($data['currency']) : Currency::RUB,
            method: PaymentMethod::from($data['method']),
            status: isset($data['status']) ? PaymentStatus::from($data['status']) : PaymentStatus::PENDING,
            transactionId: $data['transaction_id'] ?? null,
            description: $data['description'] ?? null,
            paymentData: $data['payment_data'] ?? null,
            failureReason: $data['failure_reason'] ?? null,
            paidAt: $data['paid_at'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'user_id' => $this->userId,
            'payable_type' => $this->payableType,
            'payable_id' => $this->payableId,
            'amount' => $this->amount,
            'currency' => $this->currency->value,
            'method' => $this->method->value,
            'status' => $this->status->value,
            'transaction_id' => $this->transactionId,
            'description' => $this->description,
            'payment_data' => $this->paymentData,
            'failure_reason' => $this->failureReason,
            'paid_at' => $this->paidAt,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' ' . $this->currency->symbol();
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }
}