<?php

namespace App\Domain\User\DTOs;

use App\Enums\UserRole;
use App\Enums\UserStatus;

class UserData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly ?string $password,
        public readonly UserRole $role,
        public readonly UserStatus $status,
        public readonly ?string $name,
        public readonly ?string $phone,
        public readonly ?string $avatarUrl,
        public readonly ?string $emailVerifiedAt,
        public readonly ?UserProfileData $profile,
        public readonly ?UserSettingsData $settings,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            email: $data['email'],
            password: $data['password'] ?? null,
            role: isset($data['role']) ? UserRole::from($data['role']) : UserRole::CLIENT,
            status: isset($data['status']) ? UserStatus::from($data['status']) : UserStatus::PENDING,
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            avatarUrl: $data['avatar_url'] ?? null,
            emailVerifiedAt: $data['email_verified_at'] ?? null,
            profile: isset($data['profile']) ? UserProfileData::fromArray($data['profile']) : null,
            settings: isset($data['settings']) ? UserSettingsData::fromArray($data['settings']) : null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatarUrl,
            'email_verified_at' => $this->emailVerifiedAt,
            'profile' => $this->profile?->toArray(),
            'settings' => $this->settings?->toArray(),
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }
}