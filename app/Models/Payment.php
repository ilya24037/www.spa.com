<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Универсальная модель платежа
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number',
        'user_id',
        'payable_type',
        'payable_id',
        'type',
        'method',
        'status',
        'amount',
        'fee',
        'total_amount',
        'currency',
        'description',
        'external_id',
        'external_data',
        'gateway',
        'gateway_response',
        'processed_at',
        'confirmed_at',
        'failed_at',
        'refunded_at',
        'metadata',
        'notes',
        // Старые поля для совместимости
        'ad_id',
        'ad_plan_id', 
        'payment_id',
        'external_payment_id',
        'discount_amount',
        'final_amount',
        'discount_percent',
        'promo_code',
        'payment_method',
        'purchase_type',
        'activation_code',
        'activation_code_used',
        'paid_at',
        'expires_at'
    ];

    protected $casts = [
        'type' => PaymentType::class,
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'external_data' => 'array',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        // Старые поля
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'activation_code_used' => 'boolean',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Генерация номера платежа при создании
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber();
            }
        });
    }

    /**
     * Пользователь, совершивший платеж
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь с объектом платежа
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Объявление (для обратной совместимости)
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Тарифный план (для обратной совместимости)
     */
    public function adPlan(): BelongsTo
    {
        return $this->belongsTo(AdPlan::class);
    }

    /**
     * Возвраты по этому платежу
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(self::class, 'parent_payment_id');
    }

    /**
     * Родительский платеж (для возвратов)
     */
    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    /**
     * Сгенерировать уникальный номер платежа
     */
    public static function generatePaymentNumber(): string
    {
        do {
            $number = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (static::where('payment_number', $number)->exists());
        
        return $number;
    }

    /**
     * Генерация уникального ID платежа (для обратной совместимости)
     */
    public static function generatePaymentId()
    {
        return 'PAY_' . strtoupper(uniqid());
    }

    /**
     * Проверить, является ли платеж успешным
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Проверка, оплачен ли платеж (для обратной совместимости)
     */
    public function isPaid()
    {
        return $this->isSuccessful() || ($this->status === 'completed' && $this->paid_at !== null);
    }

    /**
     * Проверить, можно ли отменить платеж
     */
    public function isCancellable(): bool
    {
        return $this->status instanceof PaymentStatus ? $this->status->isCancellable() : false;
    }

    /**
     * Проверить, можно ли вернуть платеж
     */
    public function isRefundable(): bool
    {
        if (!$this->status instanceof PaymentStatus) return false;
        if (!$this->method instanceof PaymentMethod) return false;
        
        return $this->status->isRefundable() && 
               $this->method->supportsRefund() &&
               $this->getRemainingRefundAmount() > 0;
    }

    /**
     * Получить оставшуюся сумму для возврата
     */
    public function getRemainingRefundAmount(): float
    {
        $refundedAmount = $this->refunds()
            ->whereIn('status', [PaymentStatus::COMPLETED, PaymentStatus::PROCESSING])
            ->sum('amount');
            
        return max(0, $this->amount - $refundedAmount);
    }

    /**
     * Подтвердить платеж
     */
    public function confirm(): bool
    {
        if ($this->status instanceof PaymentStatus && !$this->status->canTransitionTo(PaymentStatus::COMPLETED)) {
            return false;
        }
        
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'confirmed_at' => now(),
        ]);
        
        return true;
    }

    /**
     * Отклонить платеж
     */
    public function fail(string $reason = null): bool
    {
        if ($this->status instanceof PaymentStatus && !$this->status->canTransitionTo(PaymentStatus::FAILED)) {
            return false;
        }
        
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
            'notes' => $reason,
        ]);
        
        return true;
    }

    /**
     * Получить форматированную сумму
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = $this->total_amount ?? $this->final_amount ?? $this->amount;
        $currency = $this->currency ?? 'RUB';
        
        if ($currency === 'RUB') {
            return number_format($amount, 0, ',', ' ') . ' ₽';
        }
        
        return number_format($amount, 2, ',', ' ') . ' ' . $currency;
    }

    /**
     * Получить форматированную комиссию
     */
    public function getFormattedFeeAttribute(): string
    {
        return number_format($this->fee ?? 0, 2, ',', ' ') . ' ' . ($this->currency ?? 'RUB');
    }

    /**
     * Получить цвет статуса
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->status instanceof PaymentStatus) {
            return $this->status->getColor();
        }
        
        // Для старых статусов
        return match($this->status) {
            'completed' => '#10B981',
            'pending' => '#F59E0B',
            'failed' => '#EF4444',
            default => '#6B7280',
        };
    }

    /**
     * Скоупы для фильтрации
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where(function($q) {
            $q->where('status', PaymentStatus::COMPLETED)
              ->orWhere('status', 'completed');
        });
    }

    public function scopeFailed($query)
    {
        return $query->where(function($q) {
            $q->whereIn('status', [PaymentStatus::FAILED, PaymentStatus::CANCELLED, PaymentStatus::EXPIRED])
              ->orWhere('status', 'failed');
        });
    }

    public function scopePending($query)
    {
        return $query->where(function($q) {
            $q->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::PROCESSING])
              ->orWhere('status', 'pending');
        });
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
