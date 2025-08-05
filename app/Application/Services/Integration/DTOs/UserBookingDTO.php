<?php

namespace App\Application\Services\Integration\DTOs;

/**
 * DTO для передачи данных между User и Booking доменами
 * Обеспечивает типизацию и валидацию данных интеграции
 */
class UserBookingDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $masterId,
        public readonly string $serviceType,
        public readonly string $scheduledAt,
        public readonly ?float $price = null,
        public readonly ?int $duration = null,
        public readonly ?string $notes = null,
        public readonly ?string $location = null,
        public readonly array $additionalServices = [],
        public readonly array $metadata = []
    ) {}

    /**
     * Создать DTO из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? $data['client_id'],
            masterId: $data['master_id'],
            serviceType: $data['service_type'],
            scheduledAt: $data['scheduled_at'],
            price: $data['price'] ?? null,
            duration: $data['duration'] ?? null,
            notes: $data['notes'] ?? null,
            location: $data['location'] ?? null,
            additionalServices: $data['additional_services'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Преобразовать в массив для создания бронирования
     */
    public function toBookingArray(): array
    {
        return [
            'client_id' => $this->userId,
            'master_id' => $this->masterId,
            'service_type' => $this->serviceType,
            'scheduled_at' => $this->scheduledAt,
            'price' => $this->price,
            'duration' => $this->duration,
            'notes' => $this->notes,
            'location' => $this->location,
            'additional_services' => $this->additionalServices,
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

        if ($this->masterId <= 0) {
            $errors[] = 'Invalid master ID';
        }

        if (empty($this->serviceType)) {
            $errors[] = 'Service type is required';
        }

        if (empty($this->scheduledAt)) {
            $errors[] = 'Scheduled time is required';
        }

        if ($this->price !== null && $this->price < 0) {
            $errors[] = 'Price cannot be negative';
        }

        if ($this->duration !== null && $this->duration <= 0) {
            $errors[] = 'Duration must be positive';
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
     * Получить краткое описание бронирования
     */
    public function getSummary(): string
    {
        $summary = "{$this->serviceType} на {$this->scheduledAt}";
        
        if ($this->price) {
            $summary .= " за {$this->price} руб.";
        }

        if ($this->duration) {
            $summary .= " ({$this->duration} мин.)";
        }

        return $summary;
    }
}