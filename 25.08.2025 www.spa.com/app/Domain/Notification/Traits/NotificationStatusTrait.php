<?php

namespace App\Domain\Notification\Traits;

use App\Enums\NotificationStatus;
use App\Enums\NotificationChannel;
use Carbon\Carbon;

/**
 * Трейт для проверки состояния и действий с уведомлениями
 */
trait NotificationStatusTrait
{
    /**
     * Проверки состояния
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function isPending(): bool
    {
        return $this->status === NotificationStatus::PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === NotificationStatus::SENT;
    }

    public function isDelivered(): bool
    {
        return $this->status === NotificationStatus::DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this->status === NotificationStatus::FAILED;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function canRetry(): bool
    {
        return $this->status->canRetry() && 
               $this->retry_count < $this->max_retries;
    }

    public function shouldSendViaChannel(NotificationChannel $channel): bool
    {
        return in_array($channel->value, $this->channels ?? []);
    }

    /**
     * Действия с уведомлением
     */
    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
            'status' => NotificationStatus::READ,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'sent_at' => now(),
            'status' => NotificationStatus::SENT,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'delivered_at' => now(),
            'status' => NotificationStatus::DELIVERED,
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => NotificationStatus::FAILED,
            'metadata' => array_merge($this->metadata ?? [], [
                'failure_reason' => $reason,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        
        if ($this->retry_count >= $this->max_retries) {
            $this->markAsFailed('Max retries exceeded');
        }
    }

    public function schedule(Carbon $datetime): void
    {
        $this->update([
            'scheduled_at' => $datetime,
            'status' => NotificationStatus::PENDING,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => NotificationStatus::CANCELLED,
        ]);
    }
}