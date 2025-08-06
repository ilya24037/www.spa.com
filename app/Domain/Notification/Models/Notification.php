<?php

namespace App\Domain\Notification\Models;

use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Support\Traits\JsonFieldsTrait;
use App\Domain\Notification\Traits\NotificationScopesTrait;
use App\Domain\Notification\Traits\NotificationStatusTrait;
use App\Domain\Notification\Traits\NotificationDisplayTrait;
use App\Domain\Notification\Traits\NotificationGroupingTrait;
use App\Domain\Notification\Traits\NotificationStatsTrait;
use App\Domain\Notification\Services\NotificationCleanupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\User\Models\User;

/**
 * Модель уведомлений
 */
class Notification extends Model
{
    use HasFactory, 
        SoftDeletes, 
        JsonFieldsTrait,
        NotificationScopesTrait,
        NotificationStatusTrait,
        NotificationDisplayTrait,
        NotificationGroupingTrait,
        NotificationStatsTrait;

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
     * Cleanup старых уведомлений через сервис
     */
    public static function cleanup(): int
    {
        return app(NotificationCleanupService::class)->cleanup();
    }
}