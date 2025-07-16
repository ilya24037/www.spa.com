<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'specialty',
        'clients',
        'service_location',
        'work_format',
        'service_provider',
        'experience',
        'description',
        'price',
        'price_unit',
        'is_starting_price',
        'discount',
        'gift',
        'address',
        'travel_area',
        'phone',
        'contact_method',
        'status'
    ];

    protected $casts = [
        'clients' => 'array',
        'service_location' => 'array',
        'service_provider' => 'array',
        'is_starting_price' => 'boolean',
        'price' => 'decimal:2',
        'discount' => 'integer'
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить читаемый статус объявления
     */
    public function getReadableStatusAttribute()
    {
        return [
            'draft' => 'Черновик',
            'active' => 'Активное',
            'paused' => 'Приостановлено',
            'archived' => 'В архиве',
            'inactive' => 'Неактивное',
            'old' => 'Старое'
        ][$this->attributes['status']] ?? $this->attributes['status'];
    }

    /**
     * Получить форматированную цену
     */
    public function getFormattedPriceAttribute()
    {
        if (!$this->price) {
            return 'Цена не указана';
        }

        $units = [
            'service' => 'за услугу',
            'hour' => 'за час',
            'unit' => 'за единицу',
            'day' => 'за день',
            'month' => 'за месяц',
            'minute' => 'за минуту'
        ];

        $prefix = $this->is_starting_price ? 'от ' : '';
        $unit = $units[$this->price_unit] ?? $this->price_unit;
        
        return $prefix . number_format($this->price, 0, ',', ' ') . ' ₽ ' . $unit;
    }
} 