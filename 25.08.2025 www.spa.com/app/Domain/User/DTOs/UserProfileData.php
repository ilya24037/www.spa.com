<?php

namespace App\Domain\User\DTOs;

class UserProfileData
{
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $middleName,
        public readonly ?string $birthDate,
        public readonly ?string $gender,
        public readonly ?string $city,
        public readonly ?string $address,
        public readonly ?string $bio,
        public readonly ?array $socialLinks,
        public readonly ?array $interests,
        public readonly ?string $preferredLanguage
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            middleName: $data['middle_name'] ?? null,
            birthDate: $data['birth_date'] ?? null,
            gender: $data['gender'] ?? null,
            city: $data['city'] ?? null,
            address: $data['address'] ?? null,
            bio: $data['bio'] ?? null,
            socialLinks: $data['social_links'] ?? null,
            interests: $data['interests'] ?? null,
            preferredLanguage: $data['preferred_language'] ?? 'ru'
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'birth_date' => $this->birthDate,
            'gender' => $this->gender,
            'city' => $this->city,
            'address' => $this->address,
            'bio' => $this->bio,
            'social_links' => $this->socialLinks,
            'interests' => $this->interests,
            'preferred_language' => $this->preferredLanguage,
        ], fn($value) => $value !== null);
    }

    public function getFullName(): string
    {
        $parts = array_filter([
            $this->firstName,
            $this->middleName,
            $this->lastName
        ]);
        
        return implode(' ', $parts) ?: 'Пользователь';
    }
}