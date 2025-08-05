<?php

namespace App\Domain\Notification\Models;

use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Enums\NotificationChannel;
use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use App\Domain\User\Models\User;

/**
 * Модель уведомлений
 */
class Notification extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'title',
        'message',
        'data',
        'channels',
        'notifiable_type',
        'notifiable_id',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'expires_at',
        'priority',
        'group_key',
        'template',
        'locale',
        'retry_count',
        'max_retries',
        'external_id',
        'metadata',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'data',
        'channels',
        'metadata',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'status' => NotificationStatus::class,
        // JSON поля обрабатываются через JsonFieldsTrait
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    protected $attributes = [
        'status' => NotificationStatus::PENDING,
        'priority' => 'medium',
        'retry_count' => 0,
        'max_retries' => 3,
        'locale' => 'ru',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь с уведомляемой сущностью
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Связь с доставками по каналам
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    /**
     * Скоупы для запросов
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
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

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, NotificationType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>', now());
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', NotificationStatus::PENDING)
                    ->where(function($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                    })
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
    }

    public function scopeGrouped($query, string $groupKey)
    {
        return $query->where('group_key', $groupKey);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

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

    /**
     * Получить данные для отображения
     */
    public function getDisplayTitle(): string
    {
        return $this->title ?: $this->type->getTitle();
    }

    public function getDisplayMessage(): string
    {
        return $this->message ?: $this->type->getDefaultMessage();
    }

    public function getIcon(): string
    {
        return $this->type->getIcon();
    }

    public function getColor(): string
    {
        return $this->type->getColor();
    }

    public function getFormattedCreatedAt(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getTimeAgo(): string
    {
        if ($this->isRead()) {
            return "Прочитано {$this->read_at->diffForHumans()}";
        }
        
        return "Отправлено {$this->created_at->diffForHumans()}";
    }

    /**
     * Получить URL для действия (если есть)
     */
    public function getActionUrl(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Получить текст кнопки действия
     */
    public function getActionText(): ?string
    {
        return $this->data['action_text'] ?? null;
    }

    /**
     * Получить дополнительные данные
     */
    public function getExtraData(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Группировка уведомлений
     */
    public function getGroupKey(): ?string
    {
        return $this->group_key;
    }

    public function setGroupKey(string $key): void
    {
        $this->update(['group_key' => $key]);
    }

    public static function generateGroupKey(int $userId, NotificationType $type, string $identifier = null): string
    {
        $parts = [$userId, $type->value];
        
        if ($identifier) {
            $parts[] = $identifier;
        }
        
        return implode(':', $parts);
    }

    /**
     * Статистика
     */
    public function getDeliveryStats(): array
    {
        $deliveries = $this->deliveries->groupBy('channel');
        
        return $deliveries->map(function($channelDeliveries) {
            return [
                'total' => $channelDeliveries->count(),
                'sent' => $channelDeliveries->where('status', 'sent')->count(),
                'delivered' => $channelDeliveries->where('status', 'delivered')->count(),
                'failed' => $channelDeliveries->where('status', 'failed')->count(),
            ];
        })->toArray();
    }

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

    /**
     * Форматирование для API
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'display_title' => $this->getDisplayTitle(),
            'display_message' => $this->getDisplayMessage(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'time_ago' => $this->getTimeAgo(),
            'is_read' => $this->isRead(),
            'is_expired' => $this->isExpired(),
            'action_url' => $this->getActionUrl(),
            'action_text' => $this->getActionText(),
            'group_key' => $this->getGroupKey(),
            'delivery_stats' => $this->getDeliveryStats(),
        ]);
    }

    /**
     * Cleanup старых уведомлений
     */
    public static function cleanup(): int
    {
        $deletedCount = 0;
        
        // Удаляем прочитанные уведомления старше 30 дней
        $deletedCount += static::where('read_at', '<', now()->subDays(30))->delete();
        
        // Удаляем истекшие уведомления
        $deletedCount += static::expired()->delete();
        
        // Удаляем неудачные уведомления старше 7 дней
        $deletedCount += static::where('status', NotificationStatus::FAILED)
                              ->where('created_at', '<', now()->subDays(7))
                              ->delete();
        
        return $deletedCount;
    }
}