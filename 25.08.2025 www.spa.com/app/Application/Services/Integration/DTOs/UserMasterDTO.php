<?php

namespace App\Application\Services\Integration\DTOs;

/**
 * DTO для передачи данных между User и Master доменами
 * Обеспечивает типизацию и валидацию данных интеграции
 */
class UserMasterDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $city,
        public readonly array $services,
        public readonly ?string $description = null,
        public readonly ?int $experience = null,
        public readonly ?array $workZones = null,
        public readonly ?array $schedule = null,
        public readonly ?array $pricing = null,
        public readonly ?array $media = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly bool $isMain = false,
        public readonly array $metadata = []
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            name: $data['name'],
            city: $data['city'],
            services: $data['services'],
            description: $data['description'] ?? null,
            experience: $data['experience'] ?? null,
            workZones: $data['work_zones'] ?? null,
            schedule: $data['schedule'] ?? null,
            pricing: $data['pricing'] ?? null,
            media: $data['media'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            isMain: $data['is_main'] ?? false,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Преобразовать в массив для создания профиля мастера
     */
    public function toMasterProfileArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'city' => $this->city,
            'services' => $this->services,
            'description' => $this->description,
            'experience' => $this->experience,
            'work_zones' => $this->workZones,
            'schedule' => $this->schedule,
            'pricing' => $this->pricing,
            'media' => $this->media,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_main' => $this->isMain,
            'status' => 'pending',
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Валидировать данные DTO
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->userId <= 0) {
            $errors[] = 'Invalid user ID';
        }

        if (empty(trim($this->name))) {
            $errors[] = 'Name is required';
        }

        if (empty(trim($this->city))) {
            $errors[] = 'City is required';
        }

        if (empty($this->services)) {
            $errors[] = 'At least one service is required';
        }

        if ($this->experience !== null && $this->experience < 0) {
            $errors[] = 'Experience cannot be negative';
        }

        if ($this->phone !== null && !$this->validatePhone($this->phone)) {
            $errors[] = 'Invalid phone format';
        }

        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        return $errors;
    }

    /**
     * Проверить валидность DTO
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Валидировать номер телефона
     */
    private function validatePhone(string $phone): bool
    {
        // Простая валидация российского номера
        $cleanPhone = preg_replace('/[^+\d]/', '', $phone);
        return preg_match('/^(\+7|8)\d{10}$/', $cleanPhone);
    }

    /**
     * Получить краткое описание профиля
     */
    public function getSummary(): string
    {
        $servicesCount = count($this->services);
        $servicesText = $servicesCount === 1 ? 'услуга' : ($servicesCount < 5 ? 'услуги' : 'услуг');
        
        $summary = "{$this->name} из {$this->city} ({$servicesCount} {$servicesText})";
        
        if ($this->experience) {
            $summary .= ", опыт {$this->experience} лет";
        }

        return $summary;
    }

    /**
     * Получить список услуг как строку
     */
    public function getServicesString(): string
    {
        return implode(', ', $this->services);
    }

    /**
     * Проверить предоставляет ли мастер конкретную услугу
     */
    public function providesService(string $service): bool
    {
        return in_array($service, $this->services);
    }
}