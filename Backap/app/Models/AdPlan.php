<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'days',
        'price',
        'description',
        'is_popular',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'days' => 'integer',
        'price' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Платежи по этому плану
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Активные планы
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Форматированная цена
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', ' ') . ' ₽';
    }
}
