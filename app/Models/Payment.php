<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ad_id',
        'ad_plan_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'description',
        'metadata',
        'paid_at',
        'expires_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    /**
     * Пользователь
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Объявление
     */
    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Тарифный план
     */
    public function adPlan()
    {
        return $this->belongsTo(AdPlan::class);
    }

    /**
     * Генерация уникального ID платежа
     */
    public static function generatePaymentId()
    {
        return 'PAY_' . strtoupper(uniqid());
    }

    /**
     * Проверка, оплачен ли платеж
     */
    public function isPaid()
    {
        return $this->status === 'completed' && $this->paid_at !== null;
    }

    /**
     * Форматированная сумма
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', ' ') . ' ₽';
    }
}
