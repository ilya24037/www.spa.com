<?php

namespace App\Domain\Master\DTOs;

use App\Enums\Currency;

class MasterServiceData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $serviceId,
        public readonly string $serviceName,
        public readonly float $price,
        public readonly ?float $discountPrice,
        public readonly Currency $currency,
        public readonly int $duration,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly ?array $additionalInfo
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            serviceId: $data['service_id'],
            serviceName: $data['service_name'],
            price: (float) $data['price'],
            discountPrice: isset($data['discount_price']) ? (float) $data['discount_price'] : null,
            currency: isset($data['currency']) ? Currency::from($data['currency']) : Currency::RUB,
            duration: (int) $data['duration'],
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
            additionalInfo: $data['additional_info'] ?? null
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'service_id' => $this->serviceId,
            'service_name' => $this->serviceName,
            'price' => $this->price,
            'discount_price' => $this->discountPrice,
            'currency' => $this->currency->value,
            'duration' => $this->duration,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'additional_info' => $this->additionalInfo,
        ], fn($value) => $value !== null);
    }

    public function getFormattedPrice(): string
    {
        $price = $this->discountPrice ?? $this->price;
        return number_format($price, 0, ',', ' ') . ' ' . $this->currency->symbol();
    }

    public function getFormattedDuration(): string
    {
        if ($this->duration < 60) {
            return $this->duration . ' мин';
        }
        
        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;
        
        if ($minutes === 0) {
            return $hours . ' ч';
        }
        
        return $hours . ' ч ' . $minutes . ' мин';
    }
}