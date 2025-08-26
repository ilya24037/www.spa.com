<?php

namespace App\Domain\Payment\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Payment\Enums\SubscriptionInterval;

/**
 * Универсальная модель подписки
 * 
 * Может использоваться для:
 * - Подписок мастеров на тарифные планы
 * - Подписок пользователей на премиум функции
 * - Подписок на уведомления
 * - Любых других периодических услуг
 */
class Subscription extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'subscribable_type',
        'subscribable_id',
        'plan_id',
        'plan_name',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'cancellation_reason',
        'price',
        'currency',
        'interval',
        'interval_count',
        'features',
        'limits',
        'usage',
        'auto_renew',
        'payment_method',
        'gateway',
        'gateway_subscription_id',
        'gateway_customer_id',
        'gateway_data',
        'last_payment_id',
        'next_payment_at',
        'metadata',
        'notes'
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'features',
        'limits',
        'usage',
        'gateway_data',
        'metadata'
    ];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'interval' => SubscriptionInterval::class,
        'price' => 'decimal:2',
        'interval_count' => 'integer',
        'auto_renew' => 'boolean',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_payment_at' => 'datetime'
    ];


    /**
     * Пользователь подписки
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    /**
     * Полиморфная связь с объектом подписки
     */
    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Платежи по подписке
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    /**
     * Последний платеж
     */
    public function lastPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'last_payment_id');
    }

    /**
     * Генерация уникального ID подписки
     */
    public static function generateSubscriptionId(): string
    {
        do {
            $id = 'SUB-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        } while (static::where('subscription_id', $id)->exists());
        
        return $id;
    }

    /**
     * Проверка активности подписки
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE && 
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Проверка, находится ли подписка на пробном периоде
     */
    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::TRIALING && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    /**
     * Проверка, истек ли пробный период
     */
    public function hasExpiredTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Проверка, отменена ли подписка
     */
    public function isCancelled(): bool
    {
        return !is_null($this->cancelled_at);
    }

    /**
     * Проверка, истекла ли подписка
     */
    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED || 
               ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Проверка, находится ли подписка на паузе
     */
    public function isPaused(): bool
    {
        return $this->status === SubscriptionStatus::PAUSED;
    }

    /**
     * Отменить подписку
     */
    public function cancel(?string $reason = null): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        $this->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false
        ]);

        return true;
    }

    /**
     * Возобновить отмененную подписку
     */
    public function resume(): bool
    {
        if (!$this->isCancelled() || $this->isExpired()) {
            return false;
        }

        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'auto_renew' => true
        ]);

        return true;
    }

    /**
     * Приостановить подписку
     */
    public function pause(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $this->update([
            'status' => SubscriptionStatus::PAUSED,
            'auto_renew' => false
        ]);

        return true;
    }

    /**
     * Продлить подписку
     */
    public function extend(int $duration, SubscriptionInterval $interval = SubscriptionInterval::MONTH): bool
    {
        $endsAt = $this->ends_at ?? now();
        
        $newEndsAt = match($interval) {
            SubscriptionInterval::DAY => $endsAt->addDays($duration),
            SubscriptionInterval::WEEK => $endsAt->addWeeks($duration),
            SubscriptionInterval::MONTH => $endsAt->addMonths($duration),
            SubscriptionInterval::QUARTER => $endsAt->addMonths($duration * 3),
            SubscriptionInterval::YEAR => $endsAt->addYears($duration),
            default => $endsAt->addMonths($duration),
        };

        $this->update([
            'ends_at' => $newEndsAt,
            'status' => SubscriptionStatus::ACTIVE
        ]);

        return true;
    }

    /**
     * Получить оставшиеся дни подписки
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    /**
     * Получить процент использования периода
     */
    public function getUsagePercentage(): float
    {
        if (!$this->starts_at || !$this->ends_at) {
            return 0;
        }

        $total = $this->starts_at->diffInDays($this->ends_at);
        $used = $this->starts_at->diffInDays(now());

        return min(100, round(($used / $total) * 100, 2));
    }

    /**
     * Проверить лимит функции
     */
    public function checkFeatureLimit(string $feature, int $usage = 1): bool
    {
        $limits = $this->limits ?? [];
        
        if (!isset($limits[$feature])) {
            return true; // Нет лимита
        }

        $currentUsage = $this->usage[$feature] ?? 0;
        
        return ($currentUsage + $usage) <= $limits[$feature];
    }

    /**
     * Увеличить использование функции
     */
    public function incrementFeatureUsage(string $feature, int $amount = 1): void
    {
        $usage = $this->usage ?? [];
        $usage[$feature] = ($usage[$feature] ?? 0) + $amount;
        
        $this->update(['usage' => $usage]);
    }

    /**
     * Сбросить использование функций
     */
    public function resetUsage(): void
    {
        $this->update(['usage' => []]);
    }

    /**
     * Получить цвет статуса
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    /**
     * Получить читаемое название интервала
     */
    public function getIntervalNameAttribute(): string
    {
        return $this->interval->labelWithCount($this->interval_count ?? 1);
    }

    /**
     * Скоупы для фильтрации
     */
    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE)
                    ->where(function($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>', now());
                    });
    }

    public function scopeExpiring($query, int $days = 7)
    {
        return $query->active()
                    ->whereNotNull('ends_at')
                    ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPlan($query, string $planName)
    {
        return $query->where('plan_name', $planName);
    }

    public function scopeRequiresPayment($query)
    {
        return $query->where('auto_renew', true)
                    ->whereNotNull('next_payment_at')
                    ->where('next_payment_at', '<=', now()->addDays(1));
    }
}