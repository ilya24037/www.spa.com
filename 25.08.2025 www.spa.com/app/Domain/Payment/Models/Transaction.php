<?php

namespace App\Domain\Payment\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Domain\Payment\Enums\TransactionType;
use App\Domain\Payment\Enums\TransactionStatus;
use App\Domain\Payment\Enums\TransactionDirection;

/**
 * Модель транзакции для отслеживания всех финансовых операций
 * 
 * Транзакция - это запись о любом движении денег в системе:
 * - Платежи от пользователей
 * - Выплаты мастерам
 * - Комиссии платформы
 * - Возвраты средств
 * - Переводы между счетами
 */
class Transaction extends Model
{
    use HasFactory, SoftDeletes, JsonFieldsTrait;

    protected $fillable = [
        'transaction_id',
        'payment_id',
        'subscription_id',
        'user_id',
        'transactionable_type',
        'transactionable_id',
        'type',
        'direction',
        'status',
        'amount',
        'currency',
        'fee',
        'net_amount',
        'description',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'processed_at',
        'failed_at',
        'metadata',
        'balance_before',
        'balance_after',
        'notes'
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'gateway_response',
        'metadata'
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'direction' => TransactionDirection::class,
        'amount' => 'decimal:2',
        'fee' => 'decimal:2', 
        'net_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime'
    ];


    /**
     * Связанный платеж
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Связанная подписка
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Пользователь транзакции
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class);
    }

    /**
     * Полиморфная связь с объектом транзакции
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Генерация уникального ID транзакции
     */
    public static function generateTransactionId(): string
    {
        do {
            $id = 'TRX-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        } while (static::where('transaction_id', $id)->exists());
        
        return $id;
    }

    /**
     * Проверка успешности транзакции
     */
    public function isSuccessful(): bool
    {
        return $this->status === TransactionStatus::SUCCESS;
    }

    /**
     * Проверка, является ли транзакция входящей
     */
    public function isIncoming(): bool
    {
        return $this->direction === TransactionDirection::IN;
    }

    /**
     * Проверка, является ли транзакция исходящей
     */
    public function isOutgoing(): bool
    {
        return $this->direction === TransactionDirection::OUT;
    }

    /**
     * Получить форматированную сумму
     */
    public function getFormattedAmountAttribute(): string
    {
        $sign = $this->isOutgoing() ? '-' : '+';
        $amount = number_format($this->amount, 2, ',', ' ');
        
        return $sign . $amount . ' ' . $this->currency;
    }

    /**
     * Получить цвет статуса
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    /**
     * Получить иконку типа транзакции
     */
    public function getTypeIconAttribute(): string
    {
        return $this->type->icon();
    }

    /**
     * Скоупы для фильтрации
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', TransactionStatus::SUCCESS);
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', TransactionDirection::IN);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', TransactionDirection::OUT);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Получить сумму транзакций
     */
    public function scopeSumAmount($query)
    {
        return $query->sum('amount');
    }

    /**
     * Получить баланс (входящие минус исходящие)
     */
    public static function calculateBalance(int $userId): float
    {
        $incoming = static::byUser($userId)
            ->incoming()
            ->successful()
            ->sumAmount();
            
        $outgoing = static::byUser($userId)
            ->outgoing()
            ->successful()
            ->sumAmount();
            
        return $incoming - $outgoing;
    }
}