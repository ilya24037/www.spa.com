<?php

namespace App\Domain\Master\Models;

use App\Support\Traits\JsonFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель локации мастера
 * Отвечает за информацию о местах работы (салоны, выезд)
 */
class MasterLocation extends Model
{
    use HasFactory, JsonFieldsTrait;

    protected $fillable = [
        'master_profile_id', 'type', 'name', 'address', 'city', 'district',
        'metro_station', 'lat', 'lng', 'description', 'amenities',
        'photos', 'working_hours', 'phone', 'is_primary', 'is_active',
        'sort_order'
    ];

    /**
     * JSON поля для использования с JsonFieldsTrait
     */
    protected $jsonFields = [
        'amenities',
        'photos',
        'working_hours'
    ];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8', 
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    const TYPES = [
        'salon' => 'Салон',
        'home' => 'На дому у мастера',
        'outcall' => 'Выезд к клиенту',
        'mobile' => 'Мобильный'
    ];

    /**
     * Связь с профилем мастера
     */
    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_primary', 'desc')
                    ->orderBy('sort_order')
                    ->orderBy('name');
    }

    /**
     * Получить полный адрес
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->district,
            $this->city
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Получить название типа
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Проверить доступность в данное время
     */
    public function isAvailableAt($dayOfWeek, $time): bool
    {
        if (!$this->is_active) return false;
        
        $workingHours = $this->getJsonField('working_hours', []);
        
        if (!isset($workingHours[$dayOfWeek])) return false;
        
        $hours = $workingHours[$dayOfWeek];
        if (!$hours['is_working']) return false;
        
        return $time >= $hours['start'] && $time <= $hours['end'];
    }

    /**
     * Получить расстояние до координат (в км)
     */
    public function getDistanceTo($lat, $lng): ?float
    {
        if (!$this->lat || !$this->lng) return null;
        
        $earthRadius = 6371; // км
        
        $dLat = deg2rad($lat - $this->lat);
        $dLng = deg2rad($lng - $this->lng);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->lat)) * cos(deg2rad($lat)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c, 2);
    }
}