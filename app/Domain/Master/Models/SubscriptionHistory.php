<?php

namespace App\Domain\Master\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;

/**
 * История изменений подписки
 * 
 * @property int $id
 * @property int $master_subscription_id
 * @property string $action
 * @property string|null $description
 * @property SubscriptionPlan $plan
 * @property SubscriptionStatus $status
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 */
class SubscriptionHistory extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'master_subscription_id',
        'action',
        'description',
        'plan',
        'status',
        'metadata',
    ];

    protected $casts = [
        'plan' => SubscriptionPlan::class,
        'status' => SubscriptionStatus::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }

    /**
     * Подписка
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(MasterSubscription::class, 'master_subscription_id');
    }

    /**
     * Получить читаемое описание действия
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            'created' => 'Создана',
            'activated' => 'Активирована',
            'renewed' => 'Продлена',
            'expired' => 'Истекла',
            'cancelled' => 'Отменена',
            'suspended' => 'Приостановлена',
            'resumed' => 'Возобновлена',
            'plan_changed' => 'Изменен план',
            'trial_started' => 'Начат пробный период',
            'payment_received' => 'Платеж получен',
            'payment_failed' => 'Ошибка платежа',
            default => $this->action,
        };
    }

    /**
     * Получить иконку для действия
     */
    public function getActionIcon(): string
    {
        return match($this->action) {
            'created' => '🆕',
            'activated' => '✅',
            'renewed' => '🔄',
            'expired' => '⌛',
            'cancelled' => '❌',
            'suspended' => '⚠️',
            'resumed' => '▶️',
            'plan_changed' => '🔀',
            'trial_started' => '🎁',
            'payment_received' => '💳',
            'payment_failed' => '💔',
            default => '📝',
        };
    }
}