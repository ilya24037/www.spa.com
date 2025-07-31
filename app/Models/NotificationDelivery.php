<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель доставки уведомлений по каналам
 */
class NotificationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'channel',
        'status',
        'sent_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'external_id',
        'recipient',
        'content',
        'metadata',
        'retry_count',
        'max_retries',
    ];

    protected $casts = [
        'channel' => NotificationChannel::class,
        'status' => NotificationStatus::class,
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'content' => 'array',
        'metadata' => 'array',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    protected $attributes = [
        'status' => NotificationStatus::PENDING,
        'retry_count' => 0,
        'max_retries' => 3,
    ];

    /**
     * Связь с уведомлением
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Скоупы для запросов
     */
    public function scopeByChannel($query, NotificationChannel $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, NotificationStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', NotificationStatus::PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', NotificationStatus::SENT);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', NotificationStatus::DELIVERED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', NotificationStatus::FAILED);
    }

    public function scopeCanRetry($query)
    {
        return $query->where('status', NotificationStatus::FAILED)
                    ->whereColumn('retry_count', '<', 'max_retries');
    }

    /**
     * Проверки состояния
     */
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

    public function canRetry(): bool
    {
        return $this->status->canRetry() && 
               $this->retry_count < $this->max_retries;
    }

    /**
     * Действия с доставкой
     */
    public function markAsSent(string $externalId = null): void
    {
        $this->update([
            'status' => NotificationStatus::SENT,
            'sent_at' => now(),
            'external_id' => $externalId,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => NotificationStatus::DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => NotificationStatus::FAILED,
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        
        if ($this->retry_count >= $this->max_retries) {
            $this->markAsFailed('Max retries exceeded');
        } else {
            $this->update(['status' => NotificationStatus::PENDING]);
        }
    }

    /**
     * Получить время обработки
     */
    public function getProcessingTime(): ?int
    {
        if ($this->sent_at && $this->created_at) {
            return $this->sent_at->diffInSeconds($this->created_at);
        }
        
        return null;
    }

    public function getDeliveryTime(): ?int
    {
        if ($this->delivered_at && $this->sent_at) {
            return $this->delivered_at->diffInSeconds($this->sent_at);
        }
        
        return null;
    }

    public function getTotalTime(): ?int
    {
        if ($this->delivered_at && $this->created_at) {
            return $this->delivered_at->diffInSeconds($this->created_at);
        }
        
        return null;
    }

    /**
     * Получить статистику по времени
     */
    public function getTimeStats(): array
    {
        return [
            'processing_time' => $this->getProcessingTime(),
            'delivery_time' => $this->getDeliveryTime(),
            'total_time' => $this->getTotalTime(),
        ];
    }

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'channel_label' => $this->channel->getLabel(),
            'status_label' => $this->status->getLabel(),
            'can_retry' => $this->canRetry(),
            'time_stats' => $this->getTimeStats(),
        ]);
    }
}