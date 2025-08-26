<?php

namespace App\Domain\Notification\DTOs;

/**
 * DTO для отправки уведомления
 */
class SendNotificationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly string $channel,
        public readonly string $title,
        public readonly string $message,
        public readonly ?array $data = null,
        public readonly ?string $actionUrl = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            type: $data['type'],
            channel: $data['channel'],
            title: $data['title'],
            message: $data['message'],
            data: $data['data'] ?? null,
            actionUrl: $data['action_url'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type,
            'channel' => $this->channel,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'action_url' => $this->actionUrl,
        ];
    }
}