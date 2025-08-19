<?php

namespace App\Domain\Ad\DTOs;

/**
 * DTO для представления объявления на главной странице
 */
class AdHomePageDTO
{
    public int $id;
    public string $name;
    public string $photo;
    public float $rating;
    public int $reviews_count;
    public float $price_from;
    public array $services;
    public ?string $district;
    public ?string $metro;
    public ?string $address;
    public ?float $lat;
    public ?float $lng;
    public ?array $geo;
    public int $experience_years;
    public bool $is_verified;
    public bool $is_premium;
    
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'] ?? 'Мастер';
        $this->photo = $data['photo'] ?? '/images/no-photo.svg';
        $this->rating = $data['rating'] ?? 4.5;
        $this->reviews_count = $data['reviews_count'] ?? 0;
        $this->price_from = $data['price_from'] ?? 2000;
        $this->services = $data['services'] ?? ['Классический массаж'];
        $this->district = $data['district'] ?? null;
        $this->metro = $data['metro'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->lat = $data['lat'] ?? null;
        $this->lng = $data['lng'] ?? null;
        $this->geo = $data['geo'] ?? null;
        $this->experience_years = $data['experience_years'] ?? 1;
        $this->is_verified = $data['is_verified'] ?? false;
        $this->is_premium = $data['is_premium'] ?? false;
    }
    
    /**
     * Преобразование в массив для JSON
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo' => $this->photo,
            'rating' => $this->rating,
            'reviews_count' => $this->reviews_count,
            'price_from' => $this->price_from,
            'services' => $this->services,
            'district' => $this->district,
            'metro' => $this->metro,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'geo' => $this->geo,
            'experience_years' => $this->experience_years,
            'is_verified' => $this->is_verified,
            'is_premium' => $this->is_premium,
        ];
    }
    
    /**
     * Создание из модели Ad
     * 
     * @param \App\Domain\Ad\Models\Ad $ad
     * @param array $additionalData
     * @return self
     */
    public static function fromAd($ad, array $additionalData = []): self
    {
        $data = array_merge([
            'id' => $ad->id,
            'name' => $ad->title,
            'address' => $ad->address,
        ], $additionalData);
        
        return new self($data);
    }
}