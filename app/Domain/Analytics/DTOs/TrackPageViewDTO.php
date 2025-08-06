<?php

namespace App\Domain\Analytics\DTOs;

/**
 * DTO для отслеживания просмотра страницы
 */
class TrackPageViewDTO
{
    public function __construct(
        public ?int $userId = null,
        public ?string $sessionId = null,
        public ?string $viewableType = null,
        public ?int $viewableId = null,
        public ?string $url = null,
        public ?string $title = null,
        public ?string $referrer = null,
        public ?string $userAgent = null,
        public ?string $ipAddress = null,
        public array $metadata = []
    ) {}

    /**
     * Создать из массива данных
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? null,
            sessionId: $data['session_id'] ?? null,
            viewableType: $data['viewable_type'] ?? null,
            viewableId: $data['viewable_id'] ?? null,
            url: $data['url'] ?? null,
            title: $data['title'] ?? null,
            referrer: $data['referrer'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Создать из HTTP запроса
     */
    public static function fromRequest(\Illuminate\Http\Request $request, array $additionalData = []): self
    {
        return new self(
            userId: $additionalData['user_id'] ?? auth()->id(),
            sessionId: $request->session()->getId(),
            viewableType: $additionalData['viewable_type'] ?? null,
            viewableId: $additionalData['viewable_id'] ?? null,
            url: $request->fullUrl(),
            title: $additionalData['title'] ?? null,
            referrer: $request->headers->get('referer'),
            userAgent: $request->headers->get('User-Agent'),
            ipAddress: $request->ip(),
            metadata: $additionalData['metadata'] ?? []
        );
    }

    /**
     * Преобразовать в массив для сохранения
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'viewable_type' => $this->viewableType,
            'viewable_id' => $this->viewableId,
            'url' => $this->url,
            'title' => $this->title,
            'referrer' => $this->referrer,
            'user_agent' => $this->userAgent,
            'ip_address' => $this->ipAddress,
            'metadata' => $this->metadata,
            'viewed_at' => now(),
        ];
    }
}