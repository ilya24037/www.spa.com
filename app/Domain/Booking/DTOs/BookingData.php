<?php

namespace App\Domain\Booking\DTOs;

use App\Enums\BookingStatus;
use App\Enums\Currency;

class BookingData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $masterId,
        public readonly int $clientId,
        public readonly array $serviceIds,
        public readonly string $date,
        public readonly string $timeStart,
        public readonly string $timeEnd,
        public readonly BookingStatus $status,
        public readonly float $totalPrice,
        public readonly Currency $currency,
        public readonly ?string $clientName,
        public readonly ?string $clientPhone,
        public readonly ?string $clientEmail,
        public readonly ?string $address,
        public readonly ?string $comment,
        public readonly ?string $cancelReason,
        public readonly ?string $masterComment,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            masterId: $data['master_id'],
            clientId: $data['client_id'],
            serviceIds: $data['service_ids'] ?? [],
            date: $data['date'],
            timeStart: $data['time_start'],
            timeEnd: $data['time_end'],
            status: isset($data['status']) ? BookingStatus::from($data['status']) : BookingStatus::PENDING,
            totalPrice: (float) $data['total_price'],
            currency: isset($data['currency']) ? Currency::from($data['currency']) : Currency::RUB,
            clientName: $data['client_name'] ?? null,
            clientPhone: $data['client_phone'] ?? null,
            clientEmail: $data['client_email'] ?? null,
            address: $data['address'] ?? null,
            comment: $data['comment'] ?? null,
            cancelReason: $data['cancel_reason'] ?? null,
            masterComment: $data['master_comment'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'master_id' => $this->masterId,
            'client_id' => $this->clientId,
            'service_ids' => $this->serviceIds,
            'date' => $this->date,
            'time_start' => $this->timeStart,
            'time_end' => $this->timeEnd,
            'status' => $this->status->value,
            'total_price' => $this->totalPrice,
            'currency' => $this->currency->value,
            'client_name' => $this->clientName,
            'client_phone' => $this->clientPhone,
            'client_email' => $this->clientEmail,
            'address' => $this->address,
            'comment' => $this->comment,
            'cancel_reason' => $this->cancelReason,
            'master_comment' => $this->masterComment,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function getFormattedPrice(): string
    {
        return number_format($this->totalPrice, 0, ',', ' ') . ' ' . $this->currency->symbol();
    }

    public function getDateTime(): \DateTime
    {
        return new \DateTime($this->date . ' ' . $this->timeStart);
    }

    public function getDuration(): int
    {
        $start = new \DateTime($this->date . ' ' . $this->timeStart);
        $end = new \DateTime($this->date . ' ' . $this->timeEnd);
        return ($end->getTimestamp() - $start->getTimestamp()) / 60;
    }
}