<?php

namespace App\Domain\Ad\DTOs;

use App\Enums\Currency;
use App\Enums\PriceType;

class AdPricingData
{
    public function __construct(
        public readonly float $price,
        public readonly ?float $discountPrice,
        public readonly Currency $currency,
        public readonly PriceType $priceType,
        public readonly ?float $minOrderAmount,
        public readonly ?array $additionalPrices,
        public readonly ?bool $negotiable
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            price: (float) $data['price'],
            discountPrice: isset($data['discount_price']) ? (float) $data['discount_price'] : null,
            currency: isset($data['currency']) ? Currency::from($data['currency']) : Currency::RUB,
            priceType: isset($data['price_type']) ? PriceType::from($data['price_type']) : PriceType::FIXED,
            minOrderAmount: isset($data['min_order_amount']) ? (float) $data['min_order_amount'] : null,
            additionalPrices: $data['additional_prices'] ?? null,
            negotiable: $data['negotiable'] ?? false
        );
    }

    public function toArray(): array
    {
        $data = [
            'price' => $this->price,
            'discount_price' => $this->discountPrice,
            'currency' => $this->currency->value,
            'price_type' => $this->priceType->value,
            'min_order_amount' => $this->minOrderAmount,
            'negotiable' => $this->negotiable,
        ];
        
        // Добавляем дополнительные цены если они есть
        if ($this->additionalPrices !== null) {
            $data['additional_prices'] = $this->additionalPrices;
        }
        
        return array_filter($data, fn($value) => $value !== null);
    }
}