<?php

namespace App\Domain\Master\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Carbon\Carbon;

/**
 * Модель подписки мастера
 * 
 * @property int $id
 * @property int $master_profile_id
 * @property SubscriptionPlan $plan
 * @property SubscriptionStatus $status
 * @property int $price
 * @property int $period_months
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $trial_ends_at
 * @property bool $auto_renew
 * @property string|null $payment_method
 * @property string|null $transaction_id
 * @property array|null $metadata
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * 
 * @property-read MasterProfile $masterProfile
 * @property-read SubscriptionHistory[] $history
 */
class MasterSubscription extends Model
{
    use SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'master_profile_id',
        'plan',
        'status',
        'price',
        'period_months',
        'start_date',
        'end_date',
        'trial_ends_at',
        'auto_renew',
        'payment_method',
        'transaction_id',
        'metadata',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'metadata',
    ];

    protected $casts = [
        'plan' => SubscriptionPlan::class,
        'status' => SubscriptionStatus::class,
        'price' => 'integer',
        'period_months' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trial_ends_at' => 'datetime',
        'auto_renew' => 'boolean',
        // JSON поля обрабатываются через JsonFieldsTrait
        'cancelled_at' => 'datetime',
    ];

    /**
     * Значения по умолчанию
     */
    protected $attributes = [
        'status' => SubscriptionStatus::PENDING,
        'auto_renew' => true,
        'period_months' => 1,
    ];

    /**
     * Мастер
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * История изменений подписки
     */
    public function history(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class)->orderByDesc('created_at');
    }

    /**
     * Проверить, активна ли подписка
     */
    public function isActive(): bool
    {
        if (!$this->status->isActive()) {
            return false;
        }

        // Проверяем пробный период
        if ($this->status === SubscriptionStatus::TRIAL) {
            return $this->trial_ends_at && $this->trial_ends_at->isFuture();
        }

        // Проверяем основной период
        return $this->end_date && $this->end_date->isFuture();
    }

    /**
     * Проверить, истекает ли скоро (в течение N дней)
     */
    public function expiringInDays(int $days = 7): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $expiryDate = $this->status === SubscriptionStatus::TRIAL 
            ? $this->trial_ends_at 
            : $this->end_date;

        return $expiryDate && $expiryDate->diffInDays(now()) <= $days;
    }

    /**
     * Получить дату истечения
     */
    public function getExpiryDate(): ?Carbon
    {
        if ($this->status === SubscriptionStatus::TRIAL) {
            return $this->trial_ends_at;
        }

        return $this->end_date;
    }

    /**
     * Получить оставшиеся дни
     */
    public function getRemainingDays(): int
    {
        $expiryDate = $this->getExpiryDate();
        
        if (!$expiryDate || $expiryDate->isPast()) {
            return 0;
        }

        return $expiryDate->diffInDays(now());
    }

    /**
     * Активировать подписку
     */
    public function activate(): void
    {
        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
            'start_date' => now(),
            'end_date' => now()->addMonths($this->period_months),
        ]);

        $this->logHistory('activated', 'Подписка активирована');
    }

    /**
     * Продлить подписку
     */
    public function renew(int $months = null): void
    {
        $months = $months ?? $this->period_months;
        
        $newEndDate = $this->end_date->isPast() 
            ? now()->addMonths($months)
            : $this->end_date->addMonths($months);

        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
            'end_date' => $newEndDate,
            'period_months' => $months,
        ]);

        $this->logHistory('renewed', "Подписка продлена на {$months} мес.");
    }

    /**
     * Отменить подписку
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        $this->logHistory('cancelled', $reason ?? 'Подписка отменена пользователем');
    }

    /**
     * Приостановить подписку
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);

        $this->logHistory('suspended', $reason ?? 'Подписка приостановлена');
    }

    /**
     * Возобновить подписку
     */
    public function resume(): void
    {
        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        $this->logHistory('resumed', 'Подписка возобновлена');
    }

    /**
     * Обновить план подписки
     */
    public function changePlan(SubscriptionPlan $newPlan): void
    {
        $oldPlan = $this->plan;
        
        $this->update([
            'plan' => $newPlan,
            'price' => $newPlan->calculateTotal($this->period_months),
        ]);

        $this->logHistory('plan_changed', "План изменен с {$oldPlan->getLabel()} на {$newPlan->getLabel()}");
    }

    /**
     * Начать пробный период
     */
    public function startTrial(int $days = 14): void
    {
        $this->update([
            'status' => SubscriptionStatus::TRIAL,
            'trial_ends_at' => now()->addDays($days),
            'start_date' => now(),
        ]);

        $this->logHistory('trial_started', "Начат пробный период на {$days} дней");
    }

    /**
     * Проверить истечение и обновить статус
     */
    public function checkExpiration(): void
    {
        if ($this->status !== SubscriptionStatus::ACTIVE && $this->status !== SubscriptionStatus::TRIAL) {
            return;
        }

        $expiryDate = $this->getExpiryDate();

        if ($expiryDate && $expiryDate->isPast()) {
            if ($this->auto_renew && $this->status === SubscriptionStatus::ACTIVE) {
                // Автопродление
                $this->renew();
            } else {
                // Истечение
                $this->update(['status' => SubscriptionStatus::EXPIRED]);
                $this->logHistory('expired', 'Подписка истекла');
            }
        }
    }

    /**
     * Получить лимиты текущего плана
     */
    public function getLimits(): array
    {
        return $this->plan->getLimits();
    }

    /**
     * Получить конкретный лимит
     */
    public function getLimit(string $key): mixed
    {
        return $this->plan->getLimit($key);
    }

    /**
     * Проверить, достигнут ли лимит
     */
    public function hasReachedLimit(string $resource, int $currentCount): bool
    {
        $limit = $this->getLimit($resource);
        
        // -1 означает безлимит
        if ($limit === -1) {
            return false;
        }

        return $currentCount >= $limit;
    }

    /**
     * Записать в историю
     */
    protected function logHistory(string $action, string $description = null): void
    {
        SubscriptionHistory::create([
            'master_subscription_id' => $this->id,
            'action' => $action,
            'description' => $description,
            'plan' => $this->plan,
            'status' => $this->status,
            'metadata' => [
                'price' => $this->price,
                'period_months' => $this->period_months,
                'auto_renew' => $this->auto_renew,
            ],
        ]);
    }

    /**
     * Scope для активных подписок
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
                     ->where(function ($q) {
                         $q->where('end_date', '>', now())
                           ->orWhere(function ($q2) {
                               $q2->where('status', SubscriptionStatus::TRIAL)
                                  ->where('trial_ends_at', '>', now());
                           });
                     });
    }

    /**
     * Scope для истекающих подписок
     */
    public function scopeExpiring($query, int $days = 7)
    {
        return $query->active()
                     ->where(function ($q) use ($days) {
                         $q->whereBetween('end_date', [now(), now()->addDays($days)])
                           ->orWhereBetween('trial_ends_at', [now(), now()->addDays($days)]);
                     });
    }

    /**
     * Scope для истекших подписок
     */
    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::EXPIRED);
    }
}