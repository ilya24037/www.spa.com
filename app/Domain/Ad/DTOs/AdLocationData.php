<?php

namespace App\Domain\Ad\DTOs;

class AdLocationData
{
    public function __construct(
        public readonly string $city,
        public readonly ?string $district = null,
        public readonly ?string $metroStation = null,
        public readonly ?string $address = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly bool $homeService = false,
        public readonly bool $salonService = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            city: $data['city'],
            district: $data['district'] ?? null,
            metroStation: $data['metro_station'] ?? null,
            address: $data['address'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            homeService: $data['home_service'] ?? false,
            salonService: $data['salon_service'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'district' => $this->district,
            'metro_station' => $this->metroStation,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'home_service' => $this->homeService,
            'salon_service' => $this->salonService,
        ];
    }
}