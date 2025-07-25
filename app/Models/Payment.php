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
        'external_payment_id',
        'amount',
        'discount_amount',
        'final_amount',
        'discount_percent',
        'promo_code',
        'currency',
        'status',
        'payment_method',
        'purchase_type',
        'description',
        'metadata',
        'activation_code',
        'activation_code_used',
        'paid_at',
        'expires_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'metadata' => 'array',
        'activation_code_used' => 'boolean',
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
