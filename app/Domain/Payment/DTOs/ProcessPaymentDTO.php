<?php

namespace App\Domain\Payment\DTOs;

/**
 * DTO для обработки платежа
 */
class ProcessPaymentDTO
{
    public function __construct(
        public readonly int $paymentId,
        public readonly string $status,
        public readonly ?string $transactionId = null,
        public readonly ?array $gatewayResponse = null,
        public readonly ?string $errorMessage = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            paymentId: $data['payment_id'],
            status: $data['status'],
            transactionId: $data['transaction_id'] ?? null,
            gatewayResponse: $data['gateway_response'] ?? null,
            errorMessage: $data['error_message'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'status' => $this->status,
            'transaction_id' => $this->transactionId,
            'gateway_response' => $this->gatewayResponse,
            'error_message' => $this->errorMessage,
        ];
    }
}