<?php

namespace App\Models;

use App\Domain\Ad\Models\Ad as BaseAd;
use App\Domain\Ad\Models\AdMedia;
use App\Domain\Ad\Models\AdPricing;
use App\Domain\Ad\Models\AdLocation;
use App\Enums\PriceUnit;
use App\Enums\WorkFormat;

/**
 * Legacy Ad model для обратной совместимости
 * Использует новую доменную структуру
 */
class Ad extends BaseAd
{

    // Добавляем все поля для обратной совместимости
    protected $fillable = [
        'user_id',
        'category',
        'title',
        'specialty',
        'clients',
        'service_location',
        'outcall_locations',
        'taxi_option',
        'work_format',
        'service_provider',
        'experience',
        'education_level',
        'features',
        'additional_features',
        'description',
        'price',
        'price_unit',
        'is_starting_price',
        'pricing_data',
        'contacts_per_hour',
        'discount',
        'new_client_discount',
        'gift',
        'address',
        'travel_area',
        'phone',
        'contact_method',
        // Физические параметры
        'age',
        'height',
        'weight',
        'breast_size',
        'hair_color',
        'eye_color',
        'appearance',
        'nationality',
        'has_girlfriend',
        'services',
        'services_additional_info',
        'schedule',
        'schedule_notes',
        'photos',
        'video',
        'show_photos_in_gallery',
        'allow_download_photos',
        'watermark_photos',
        'status',
        'is_paid',
        'paid_at',
        'expires_at',
        'views_count',
        'contacts_shown',
        'favorites_count'
    ];

    protected $casts = [
        'clients' => 'array',
        'service_location' => 'array',
        'outcall_locations' => 'array',
        'service_provider' => 'array',
        'features' => 'array',
        'services' => 'array',
        'schedule' => 'array',
        'photos' => 'array',
        'video' => 'array',
        'is_starting_price' => 'boolean',
        'has_girlfriend' => 'boolean',
        'pricing_data' => 'array',
        'show_photos_in_gallery' => 'boolean',
        'allow_download_photos' => 'boolean',
        'watermark_photos' => 'boolean',
        'is_paid' => 'boolean',
        'price' => 'decimal:2',
        'discount' => 'integer',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'views_count' => 'integer',
        'contacts_shown' => 'integer',
        'favorites_count' => 'integer',
        // Убираем каст к enum, так как используем mutator
        // 'price_unit' => PriceUnit::class,
        // 'work_format' => WorkFormat::class,
    ];

    /* --------------------------------------------------------------------- */
    /*  Легаси методы для обратной совместимости                           */
    /* --------------------------------------------------------------------- */


    /**
     * Получить форматированную цену (делегируем в AdPricing)
     */
    public function getFormattedPriceAttribute()
    {
        if ($this->pricing) {
            return $this->pricing->formatted_price;
        }

        // Обратная совместимость для старых записей
        if (!$this->price) {
            return 'Цена не указана';
        }

        $prefix = $this->is_starting_price ? 'от ' : '';
        $unit = $this->price_unit?->getLabel() ?? '';
        
        return $prefix . number_format($this->price, 0, ',', ' ') . ' ₽ ' . $unit;
    }




    /**
     * Accessor для work_format - возвращает enum
     */
    public function getWorkFormatAttribute($value): ?WorkFormat
    {
        if (!$value) {
            return null;
        }
        
        try {
            return WorkFormat::from($value);
        } catch (\ValueError $e) {
            // Если значение не валидное, возвращаем значение по умолчанию
            return WorkFormat::INDIVIDUAL;
        }
    }

    /**
     * Accessor для price_unit - возвращает enum
     */
    public function getPriceUnitAttribute($value): ?PriceUnit
    {
        if (!$value) {
            return null;
        }
        
        try {
            return PriceUnit::from($value);
        } catch (\ValueError $e) {
            // Если значение не валидное, возвращаем значение по умолчанию
            return PriceUnit::SERVICE;
        }
    }

    /**
     * Мутатор для price_unit - преобразует неизвестные значения
     */
    public function setPriceUnitAttribute($value)
    {
        // Маппинг старых/неправильных значений к правильным
        $mapping = [
            'час' => 'hour',
            'сеанс' => 'session',
            'услуга' => 'service',
            'минута' => 'minute',
            'день' => 'day',
            'месяц' => 'month',
        ];

        // Если значение в маппинге, используем преобразованное
        if (isset($mapping[$value])) {
            $value = $mapping[$value];
        }

        // Проверяем, является ли значение валидным для enum
        if ($value) {
            $validValues = array_column(PriceUnit::cases(), 'value');
            if (!in_array($value, $validValues)) {
                // Если не валидное, используем значение по умолчанию
                $value = 'service';
            }
        }

        $this->attributes['price_unit'] = $value;
    }

    /**
     * Проверить готовность к публикации (используем старую логику + новую)
     */
    public function canBePublished(): bool
    {
        // Сначала используем логику из базовой модели
        if (method_exists(parent::class, 'canBePublished')) {
            return parent::canBePublished();
        }

        // Обратная совместимость для старых полей
        if (empty($this->category) || empty($this->address) || empty($this->phone)) {
            return false;
        }

        if (!$this->content || !$this->content->isComplete()) {
            return false;
        }

        // Для старых записей без pricing
        if ($this->pricing && !$this->pricing->isValidPrice()) {
            return false;
        } elseif (!$this->pricing && !$this->price) {
            return false;
        }

        return true;
    }


} 