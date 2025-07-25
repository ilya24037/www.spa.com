<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rub_balance',
        'usd_balance', 
        'eur_balance',
        'bonus_balance',
        'frozen_balance',
        'total_deposited',
        'total_spent',
        'deposits_count',
        'loyalty_discount_percent',
        'loyalty_level',
        'last_deposit_at',
        'last_spend_at'
    ];

    protected $casts = [
        'rub_balance' => 'decimal:2',
        'usd_balance' => 'decimal:2',
        'eur_balance' => 'decimal:2',
        'bonus_balance' => 'decimal:2',
        'frozen_balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'loyalty_discount_percent' => 'decimal:2',
        'last_deposit_at' => 'datetime',
        'last_spend_at' => 'datetime'
    ];

    /**
     * Связь с пользователем
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить основной баланс (RUB)
     */
    public function getMainBalanceAttribute()
    {
        return $this->rub_balance;
    }

    /**
     * Получить доступный баланс (без замороженных средств)
     */
    public function getAvailableBalanceAttribute()
    {
        return $this->rub_balance - $this->frozen_balance;
    }

    /**
     * Пополнить баланс (как в DigiSeller)
     */
    public function addFunds($amount, $currency = 'RUB')
    {
        $field = strtolower($currency) . '_balance';
        
        if (!in_array($field, $this->fillable)) {
            throw new \InvalidArgumentException("Unsupported currency: {$currency}");
        }

        $this->increment($field, $amount);
        $this->increment('total_deposited', $amount);
        $this->increment('deposits_count');
        $this->update(['last_deposit_at' => now()]);

        // Обновляем уровень лояльности
        $this->updateLoyaltyLevel();

        return $this;
    }

    /**
     * Списать средства с баланса
     */
    public function spend($amount, $currency = 'RUB')
    {
        $field = strtolower($currency) . '_balance';
        
        if ($this->$field < $amount) {
            throw new \Exception('Insufficient funds');
        }

        $this->decrement($field, $amount);
        $this->increment('total_spent', $amount);
        $this->update(['last_spend_at' => now()]);

        return $this;
    }

    /**
     * Заморозить средства (для pending платежей)
     */
    public function freezeFunds($amount)
    {
        if ($this->available_balance < $amount) {
            throw new \Exception('Insufficient available funds');
        }

        $this->increment('frozen_balance', $amount);
        return $this;
    }

    /**
     * Разморозить средства
     */
    public function unfreezeFunds($amount)
    {
        $this->decrement('frozen_balance', max(0, min($amount, $this->frozen_balance)));
        return $this;
    }

    /**
     * Обновить уровень лояльности (как система скидок в DigiSeller)
     */
    public function updateLoyaltyLevel()
    {
        $totalSpent = $this->total_spent;

        if ($totalSpent >= 250000) { // 250k+ руб
            $this->loyalty_level = 'platinum';
            $this->loyalty_discount_percent = 45;
        } elseif ($totalSpent >= 50000) { // 50k+ руб  
            $this->loyalty_level = 'gold';
            $this->loyalty_discount_percent = 25;
        } elseif ($totalSpent >= 10000) { // 10k+ руб
            $this->loyalty_level = 'silver';
            $this->loyalty_discount_percent = 10;
        } else {
            $this->loyalty_level = 'bronze';
            $this->loyalty_discount_percent = 0;
        }

        $this->save();
    }

    /**
     * Получить персональную скидку для суммы
     */
    public function getDiscountForAmount($amount)
    {
        $discountPercent = $this->loyalty_discount_percent;
        
        // Дополнительные скидки за объём (как в DigiSeller)
        if ($amount >= 25000) {
            $discountPercent = max($discountPercent, 45);
        } elseif ($amount >= 10000) {
            $discountPercent = max($discountPercent, 25);
        } elseif ($amount >= 5000) {
            $discountPercent = max($discountPercent, 10);
        } elseif ($amount >= 1000) {
            $discountPercent = max($discountPercent, 5);
        }

        return [
            'percent' => $discountPercent,
            'amount' => $amount * ($discountPercent / 100),
            'final_amount' => $amount * (1 - $discountPercent / 100)
        ];
    }

    /**
     * Форматированный баланс
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->rub_balance, 0, ',', ' ') . ' ₽';
    }
}
