<?php

namespace App\Domain\Payment\DTOs;

/**
 * DTO для данных webhook
 */
class WebhookDataDTO
{
    public function __construct(
        public readonly string $gateway,
        public readonly string $eventType,
        public readonly ?string $paymentId,
        public readonly ?string $externalId,
        public readonly ?string $status,
        public readonly ?float $amount,
        public readonly ?string $currency,
        public readonly array $metadata,
        public readonly array $rawData,
        public readonly ?string $signature = null,
        public readonly ?string $timestamp = null
    ) {}

    /**
     * Создать из данных запроса
     */
    public static function fromRequest(string $gateway, array $data): self
    {
        return match($gateway) {
            'stripe' => self::fromStripeData($data),
            'yookassa' => self::fromYooKassaData($data),
            'paypal' => self::fromPayPalData($data),
            default => self::fromGenericData($gateway, $data),
        };
    }

    /**
     * Создать из данных Stripe
     */
    protected static function fromStripeData(array $data): self
    {
        $object = $data['data']['object'] ?? [];
        
        return new self(
            gateway: 'stripe',
            eventType: $data['type'] ?? '',
            paymentId: $object['metadata']['payment_id'] ?? null,
            externalId: $object['id'] ?? null,
            status: $object['status'] ?? null,
            amount: isset($object['amount']) ? $object['amount'] / 100 : null,
            currency: $object['currency'] ?? 'usd',
            metadata: $object['metadata'] ?? [],
            rawData: $data
        );
    }

    /**
     * Создать из данных YooKassa
     */
    protected static function fromYooKassaData(array $data): self
    {
        $object = $data['object'] ?? [];
        
        return new self(
            gateway: 'yookassa',
            eventType: $data['event'] ?? '',
            paymentId: $object['metadata']['payment_id'] ?? null,
            externalId: $object['id'] ?? null,
            status: $object['status'] ?? null,
            amount: isset($object['amount']['value']) ? (float) $object['amount']['value'] : null,
            currency: $object['amount']['currency'] ?? 'RUB',
            metadata: $object['metadata'] ?? [],
            rawData: $data
        );
    }

    /**
     * Создать из данных PayPal
     */
    protected static function fromPayPalData(array $data): self
    {
        return new self(
            gateway: 'paypal',
            eventType: $data['event_type'] ?? '',
            paymentId: $data['resource']['custom_id'] ?? null,
            externalId: $data['resource']['id'] ?? null,
            status: $data['resource']['status'] ?? null,
            amount: isset($data['resource']['amount']['value']) ? (float) $data['resource']['amount']['value'] : null,
            currency: $data['resource']['amount']['currency_code'] ?? 'USD',
            metadata: $data['resource']['custom'] ?? [],
            rawData: $data
        );
    }

    /**
     * Создать из общих данных
     */
    protected static function fromGenericData(string $gateway, array $data): self
    {
        return new self(
            gateway: $gateway,
            eventType: $data['event'] ?? $data['type'] ?? 'unknown',
            paymentId: $data['payment_id'] ?? $data['metadata']['payment_id'] ?? null,
            externalId: $data['id'] ?? $data['external_id'] ?? null,
            status: $data['status'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? 'RUB',
            metadata: $data['metadata'] ?? [],
            rawData: $data
        );
    }

    /**
     * Проверить, является ли событие успешным платежом
     */
    public function isSuccessfulPayment(): bool
    {
        return in_array($this->eventType, [
            'payment.succeeded',
            'payment_intent.succeeded',
            'charge.succeeded',
            'PAYMENT.CAPTURE.COMPLETED',
        ]) || ($this->status === 'succeeded' || $this->status === 'completed');
    }

    /**
     * Проверить, является ли событие неудачным платежом
     */
    public function isFailedPayment(): bool
    {
        return in_array($this->eventType, [
            'payment.failed',
            'payment_intent.payment_failed',
            'charge.failed',
            'PAYMENT.CAPTURE.DENIED',
        ]) || ($this->status === 'failed' || $this->status === 'canceled');
    }

    /**
     * Проверить, является ли событие возвратом
     */
    public function isRefund(): bool
    {
        return in_array($this->eventType, [
            'refund.succeeded',
            'charge.refunded',
            'PAYMENT.CAPTURE.REFUNDED',
        ]);
    }
}