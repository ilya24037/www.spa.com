<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_profile_id', 'city', 'district', 'metro_station',
        'extra_charge', 'extra_charge_type', 'min_order_amount',
        'max_distance_km', 'work_from', 'work_to', 'work_days',
        'is_active', 'priority'
    ];

    protected $casts = [
        'work_from' => 'datetime:H:i',
        'work_to' => 'datetime:H:i',
        'work_days' => 'array',
        'is_active' => 'boolean',
        'extra_charge' => 'decimal:2',
        'min_order_amount' => 'integer',
        'max_distance_km' => 'integer'
    ];

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function getExtraChargeAmountAttribute($basePrice = 0): float
    {
        if ($this->extra_charge_type === 'percentage') {
            return $basePrice * ($this->extra_charge / 100);
        }
        return $this->extra_charge;
    }

    public function isAvailableAt($dayOfWeek, $time): bool
    {
        if (!$this->is_active) return false;
        if (!in_array($dayOfWeek, $this->work_days ?? [])) return false;
        
        $requestTime = Carbon::parse($time);
        $workFrom = Carbon::parse($this->work_from);
        $workTo = Carbon::parse($this->work_to);
        
        return $requestTime->between($workFrom, $workTo);
    }
}