<?php

namespace App\Domain\Ad\Models;

use App\Enums\WorkFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель для работы с локацией и форматом работы объявления
 * Управляет адресами, зонами выезда и форматом работы
 */
class AdLocation extends Model
{
    protected $table = 'ad_locations';

    protected $fillable = [
        'ad_id',
        'work_format',
        'service_location',
        'outcall_locations',
        'taxi_option',
        'address',
        'travel_area',
        'phone',
        'contact_method',
        'schedule',
        'schedule_notes',
    ];

    protected $casts = [
        'service_location' => 'array',
        'outcall_locations' => 'array',
        'schedule' => 'array',
        'taxi_option' => 'boolean',
        'work_format' => WorkFormat::class,
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Проверка полноты данных локации
     */
    public function isComplete(): bool
    {
        return !empty($this->address) && 
               !empty($this->phone) && 
               !empty($this->work_format);
    }

    /**
     * Проверка поддержки выезда
     */
    public function supportsOutcall(): bool
    {
        return in_array($this->work_format?->value, [
            WorkFormat::OUTCALL->value,
            WorkFormat::BOTH->value
        ]);
    }

    /**
     * Проверка поддержки работы в салоне
     */
    public function supportsIncall(): bool
    {
        return in_array($this->work_format?->value, [
            WorkFormat::INCALL->value,
            WorkFormat::BOTH->value
        ]);
    }

    /**
     * Получить читаемый формат работы
     */
    public function getWorkFormatLabelAttribute(): string
    {
        return $this->work_format?->getLabel() ?? 'Не указано';
    }

    /**
     * Получить список районов выезда
     */
    public function getOutcallDistrictsAttribute(): array
    {
        if (!$this->supportsOutcall() || empty($this->outcall_locations)) {
            return [];
        }

        return $this->outcall_locations;
    }

    /**
     * Проверка доступности в районе
     */
    public function isAvailableInDistrict(string $district): bool
    {
        if (!$this->supportsOutcall()) {
            return false;
        }

        return in_array($district, $this->outcall_districts);
    }

    /**
     * Получить форматированный адрес
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->travel_area ? "({$this->travel_area})" : null,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Получить рабочие дни из расписания
     */
    public function getWorkingDaysAttribute(): array
    {
        if (empty($this->schedule)) {
            return [];
        }

        $days = [];
        foreach ($this->schedule as $day => $hours) {
            if (!empty($hours['start']) && !empty($hours['end'])) {
                $days[] = $day;
            }
        }

        return $days;
    }

    /**
     * Проверка работы в определенный день
     */
    public function isWorkingOnDay(string $day): bool
    {
        return in_array($day, $this->working_days);
    }

    /**
     * Получить рабочие часы для дня
     */
    public function getWorkingHoursForDay(string $day): ?array
    {
        if (!$this->isWorkingOnDay($day)) {
            return null;
        }

        return $this->schedule[$day] ?? null;
    }

    /**
     * Проверка круглосуточной работы
     */
    public function isWorking24Hours(): bool
    {
        if (empty($this->schedule)) {
            return false;
        }

        foreach ($this->schedule as $hours) {
            if (!empty($hours['is_24_hours'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить предпочтительный способ связи
     */
    public function getPreferredContactMethodAttribute(): string
    {
        return $this->contact_method ?? 'phone';
    }

    /**
     * Форматирование телефона
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (empty($this->phone)) {
            return '';
        }

        // Простое форматирование российского номера
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        
        if (strlen($phone) === 11 && $phone[0] === '7') {
            return '+7 (' . substr($phone, 1, 3) . ') ' . 
                   substr($phone, 4, 3) . '-' . 
                   substr($phone, 7, 2) . '-' . 
                   substr($phone, 9, 2);
        }

        return $this->phone;
    }

    /**
     * Получить описание локации для отображения
     */
    public function getLocationSummaryAttribute(): string
    {
        $parts = [];

        if ($this->supportsIncall()) {
            $parts[] = 'Приём в салоне: ' . $this->address;
        }

        if ($this->supportsOutcall()) {
            $districts = implode(', ', $this->outcall_districts);
            $parts[] = 'Выезд: ' . ($districts ?: 'все районы');
            
            if ($this->taxi_option) {
                $parts[] = '(возможно такси за счёт клиента)';
            }
        }

        return implode('; ', $parts);
    }
}