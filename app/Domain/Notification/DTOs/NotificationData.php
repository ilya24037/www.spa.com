<?php

namespace App\Domain\Notification\DTOs;

use App\Enums\NotificationType;
use App\Enums\NotificationChannel;

class NotificationData
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly NotificationType $type,
        public readonly string $title,
        public readonly string $message,
        public readonly ?array $data,
        public readonly array $channels,
        public readonly ?string $actionUrl,
        public readonly ?string $actionText,
        public readonly ?string $icon,
        public readonly ?string $readAt,
        public readonly ?string $sentAt,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            userId: $data['user_id'],
            type: NotificationType::from($data['type']),
            title: $data['title'],
            message: $data['message'],
            data: $data['data'] ?? null,
            channels: array_map(
                fn($channel) => NotificationChannel::from($channel),
                $data['channels'] ?? [NotificationChannel::DATABASE->value]
            ),
            actionUrl: $data['action_url'] ?? null,
            actionText: $data['action_text'] ?? null,
            icon: $data['icon'] ?? null,
            readAt: $data['read_at'] ?? null,
            sentAt: $data['sent_at'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type->value,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'channels' => array_map(fn($channel) => $channel->value, $this->channels),
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'icon' => $this->icon,
            'read_at' => $this->readAt,
            'sent_at' => $this->sentAt,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function shouldSendEmail(): bool
    {
        return in_array(NotificationChannel::EMAIL, $this->channels);
    }

    public function shouldSendSms(): bool
    {
        return in_array(NotificationChannel::SMS, $this->channels);
    }

    public function shouldSendPush(): bool
    {
        return in_array(NotificationChannel::PUSH, $this->channels);
    }
}