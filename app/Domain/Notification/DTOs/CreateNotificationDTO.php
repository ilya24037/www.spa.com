<?php

namespace App\Domain\Notification\DTOs;

use App\Enums\NotificationType;
use App\Enums\NotificationChannel;
use Carbon\Carbon;

/**
 * DTO для создания уведомления
 */
class CreateNotificationDTO
{
    public function __construct(
        public int $userId,
        public NotificationType $type,
        public string $title,
        public string $message,
        public array $data = [],
        public ?array $channels = null,
        public ?string $notifiableType = null,
        public ?int $notifiableId = null,
        public ?Carbon $scheduledAt = null,
        public ?Carbon $expiresAt = null,
        public string $priority = 'medium',
        public ?string $groupKey = null,
        public ?string $template = null,
        public string $locale = 'ru',
        public int $maxRetries = 3,
        public array $metadata = [],
    ) {}

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            type: NotificationType::from($data['type']),
            title: $data['title'],
            message: $data['message'],
            data: $data['data'] ?? [],
            channels: isset($data['channels']) 
                ? array_map(fn($c) => NotificationChannel::from($c), $data['channels'])
                : null,
            notifiableType: $data['notifiable_type'] ?? null,
            notifiableId: $data['notifiable_id'] ?? null,
            scheduledAt: isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            priority: $data['priority'] ?? 'medium',
            groupKey: $data['group_key'] ?? null,
            template: $data['template'] ?? null,
            locale: $data['locale'] ?? 'ru',
            maxRetries: $data['max_retries'] ?? 3,
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Создать для бронирования
     */
    public static function forBooking(
        int $userId,
        NotificationType $type,
        int $bookingId,
        string $title = null,
        string $message = null,
        array $data = []
    ): self {
        return new self(
            userId: $userId,
            type: $type,
            title: $title ?: $type->getTitle(),
            message: $message ?: $type->getDefaultMessage(),
            data: array_merge($data, ['booking_id' => $bookingId]),
            notifiableType: 'App\\Models\\Booking',
            notifiableId: $bookingId,
            priority: $type->getPriority(),
        );
    }

    /**
     * Создать для платежа
     */
    public static function forPayment(
        int $userId,
        NotificationType $type,
        int $paymentId,
        string $title = null,
        string $message = null,
        array $data = []
    ): self {
        return new self(
            userId: $userId,
            type: $type,
            title: $title ?: $type->getTitle(),
            message: $message ?: $type->getDefaultMessage(),
            data: array_merge($data, ['payment_id' => $paymentId]),
            notifiableType: 'App\\Models\\Payment',
            notifiableId: $paymentId,
            priority: $type->getPriority(),
        );
    }

    /**
     * Создать для объявления
     */
    public static function forAd(
        int $userId,
        NotificationType $type,
        int $adId,
        string $title = null,
        string $message = null,
        array $data = []
    ): self {
        return new self(
            userId: $userId,
            type: $type,
            title: $title ?: $type->getTitle(),
            message: $message ?: $type->getDefaultMessage(),
            data: array_merge($data, ['ad_id' => $adId]),
            notifiableType: 'App\\Domain\\Ad\\Models\\Ad',
            notifiableId: $adId,
            priority: $type->getPriority(),
        );
    }

    /**
     * Создать системное уведомление
     */
    public static function system(
        string $title,
        string $message,
        array $userIds = [],
        array $data = []
    ): self {
        return new self(
            userId: 0,
            type: NotificationType::SYSTEM_UPDATE,
            title: $title,
            message: $message,
            data: array_merge($data, ['user_ids' => $userIds]),
            priority: 'high',
        );
    }

    /**
     * Создать промо уведомление
     */
    public static function promo(
        string $title,
        string $message,
        array $userIds = [],
        array $data = [],
        Carbon $expiresAt = null
    ): self {
        return new self(
            userId: 0,
            type: NotificationType::PROMO_NEW,
            title: $title,
            message: $message,
            data: array_merge($data, ['user_ids' => $userIds]),
            expiresAt: $expiresAt ?: now()->addDays(7),
            priority: 'low',
        );
    }

    /**
     * Установить каналы доставки
     */
    public function withChannels(array $channels): self
    {
        $clone = clone $this;
        $clone->channels = $channels;
        return $clone;
    }

    /**
     * Запланировать на определенное время
     */
    public function scheduleAt(Carbon $datetime): self
    {
        $clone = clone $this;
        $clone->scheduledAt = $datetime;
        return $clone;
    }

    /**
     * Установить время истечения
     */
    public function expiresAt(Carbon $datetime): self
    {
        $clone = clone $this;
        $clone->expiresAt = $datetime;
        return $clone;
    }

    /**
     * Установить приоритет
     */
    public function withPriority(string $priority): self
    {
        $clone = clone $this;
        $clone->priority = $priority;
        return $clone;
    }

    /**
     * Установить группу
     */
    public function withGroup(string $groupKey): self
    {
        $clone = clone $this;
        $clone->groupKey = $groupKey;
        return $clone;
    }

    /**
     * Добавить данные
     */
    public function withData(array $data): self
    {
        $clone = clone $this;
        $clone->data = array_merge($this->data, $data);
        return $clone;
    }

    /**
     * Добавить метаданные
     */
    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = array_merge($this->metadata, $metadata);
        return $clone;
    }

    /**
     * Преобразовать в массив
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'channels' => $this->channels,
            'notifiable_type' => $this->notifiableType,
            'notifiable_id' => $this->notifiableId,
            'scheduled_at' => $this->scheduledAt,
            'expires_at' => $this->expiresAt,
            'priority' => $this->priority,
            'group_key' => $this->groupKey,
            'template' => $this->template,
            'locale' => $this->locale,
            'max_retries' => $this->maxRetries,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Валидация данных
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->title)) {
            $errors[] = 'Title is required';
        }

        if (empty($this->message)) {
            $errors[] = 'Message is required';
        }

        if ($this->userId < 0) {
            $errors[] = 'Valid user ID is required';
        }

        if ($this->scheduledAt && $this->scheduledAt->isPast()) {
            $errors[] = 'Scheduled time cannot be in the past';
        }

        if ($this->expiresAt && $this->expiresAt->isPast()) {
            $errors[] = 'Expiration time cannot be in the past';
        }

        if ($this->scheduledAt && $this->expiresAt && $this->scheduledAt->isAfter($this->expiresAt)) {
            $errors[] = 'Scheduled time cannot be after expiration time';
        }

        if (!in_array($this->priority, ['low', 'medium', 'high'])) {
            $errors[] = 'Priority must be one of: low, medium, high';
        }

        if ($this->maxRetries < 0 || $this->maxRetries > 10) {
            $errors[] = 'Max retries must be between 0 and 10';
        }

        return $errors;
    }

    /**
     * Проверить валидность
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}