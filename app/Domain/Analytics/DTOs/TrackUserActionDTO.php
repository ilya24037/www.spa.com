<?php

namespace App\Domain\Analytics\DTOs;

/**
 * DTO для отслеживания действия пользователя
 */
class TrackUserActionDTO
{
    public function __construct(
        public string $actionType,
        public ?int $userId = null,
        public ?string $sessionId = null,
        public ?string $actionableType = null,
        public ?int $actionableId = null,
        public array $properties = [],
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $referrerUrl = null,
        public ?string $currentUrl = null,
        public bool $isConversion = false,
        public float $conversionValue = 0.0
    ) {}

    /**
     * Создать из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            actionType: $data['action_type'],
            userId: $data['user_id'] ?? null,
            sessionId: $data['session_id'] ?? null,
            actionableType: $data['actionable_type'] ?? null,
            actionableId: $data['actionable_id'] ?? null,
            properties: $data['properties'] ?? [],
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            referrerUrl: $data['referrer_url'] ?? null,
            currentUrl: $data['current_url'] ?? null,
            isConversion: $data['is_conversion'] ?? false,
            conversionValue: $data['conversion_value'] ?? 0.0
        );
    }

    /**
     * Создать из HTTP запроса
     */
    public static function fromRequest(
        \Illuminate\Http\Request $request, 
        string $actionType,
        array $additionalData = []
    ): self {
        return new self(
            actionType: $actionType,
            userId: $additionalData['user_id'] ?? auth()->id(),
            sessionId: $request->session()->getId(),
            actionableType: $additionalData['actionable_type'] ?? null,
            actionableId: $additionalData['actionable_id'] ?? null,
            properties: $additionalData['properties'] ?? [],
            ipAddress: $request->ip(),
            userAgent: $request->headers->get('User-Agent'),
            referrerUrl: $request->headers->get('referer'),
            currentUrl: $request->fullUrl(),
            isConversion: $additionalData['is_conversion'] ?? false,
            conversionValue: $additionalData['conversion_value'] ?? 0.0
        );
    }

    /**
     * Создать конверсионное действие
     */
    public static function createConversion(
        string $actionType,
        float $value,
        array $data = []
    ): self {
        $dto = self::fromArray(array_merge($data, ['action_type' => $actionType]));
        $dto->isConversion = true;
        $dto->conversionValue = $value;
        return $dto;
    }

    /**
     * Преобразовать в массив для сохранения
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'action_type' => $this->actionType,
            'actionable_type' => $this->actionableType,
            'actionable_id' => $this->actionableId,
            'properties' => $this->properties,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'referrer_url' => $this->referrerUrl,
            'current_url' => $this->currentUrl,
            'is_conversion' => $this->isConversion,
            'conversion_value' => $this->conversionValue,
            'performed_at' => now(),
        ];
    }

    /**
     * Добавить свойство
     */
    public function addProperty(string $key, $value): self
    {
        $this->properties[$key] = $value;
        return $this;
    }

    /**
     * Установить множество свойств
     */
    public function setProperties(array $properties): self
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }

    /**
     * Пометить как конверсию
     */
    public function markAsConversion(float $value = 0.0): self
    {
        $this->isConversion = true;
        $this->conversionValue = $value;
        return $this;
    }
}