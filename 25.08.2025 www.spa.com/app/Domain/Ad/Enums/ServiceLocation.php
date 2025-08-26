<?php

namespace App\Domain\Ad\Enums;

/**
 * Места оказания услуг
 * Доменная модель для определения формата предоставления услуг
 */
enum ServiceLocation: string
{
    case HOME = 'home';
    case SALON = 'salon';
    case BOTH = 'both';
    case OUTCALL = 'outcall';
    case ONLINE = 'online';

    /**
     * Получить читаемое название места услуг
     */
    public function getLabel(): string
    {
        return match($this) {
            self::HOME => 'У себя',
            self::SALON => 'В салоне',
            self::BOTH => 'У себя и в салоне',
            self::OUTCALL => 'С выездом',
            self::ONLINE => 'Онлайн консультация',
        };
    }

    /**
     * Получить подробное описание
     */
    public function getDescription(): string
    {
        return match($this) {
            self::HOME => 'Клиент приезжает к мастеру домой',
            self::SALON => 'Услуги в салоне красоты или студии',
            self::BOTH => 'Возможны оба варианта по договоренности',
            self::OUTCALL => 'Мастер выезжает к клиенту',
            self::ONLINE => 'Консультации и программы онлайн',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::HOME => '🏠',
            self::SALON => '🏢',
            self::BOTH => '🏠🏢',
            self::OUTCALL => '🚗',
            self::ONLINE => '💻',
        };
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::HOME => '#10B981',      // emerald
            self::SALON => '#3B82F6',     // blue
            self::BOTH => '#8B5CF6',      // violet
            self::OUTCALL => '#F59E0B',   // amber
            self::ONLINE => '#EC4899',    // pink
        };
    }

    /**
     * Проверить, требует ли место указание адреса
     */
    public function requiresAddress(): bool
    {
        return match($this) {
            self::HOME, self::SALON, self::BOTH => true,
            self::OUTCALL, self::ONLINE => false,
        };
    }

    /**
     * Проверить, требует ли место указание зоны выезда
     */
    public function requiresServiceArea(): bool
    {
        return match($this) {
            self::OUTCALL, self::BOTH => true,
            default => false,
        };
    }

    /**
     * Проверить, поддерживает ли оплату наличными
     */
    public function supportsCashPayment(): bool
    {
        return match($this) {
            self::ONLINE => false,
            default => true,
        };
    }

    /**
     * Получить рекомендуемые способы связи
     */
    public function getRecommendedContactMethods(): array
    {
        return match($this) {
            self::HOME, self::SALON => ['phone', 'whatsapp', 'telegram'],
            self::BOTH => ['phone', 'whatsapp', 'telegram', 'email'],
            self::OUTCALL => ['phone', 'whatsapp', 'telegram'],
            self::ONLINE => ['email', 'telegram', 'zoom', 'skype'],
        };
    }

    /**
     * Получить дополнительные поля, требуемые для этого типа локации
     */
    public function getRequiredFields(): array
    {
        return match($this) {
            self::HOME, self::SALON => ['address', 'phone'],
            self::BOTH => ['address', 'phone', 'outcall_areas'],
            self::OUTCALL => ['phone', 'service_areas', 'travel_time'],
            self::ONLINE => ['email', 'online_platforms'],
        };
    }

    /**
     * Проверить совместимость с типом объявления
     */
    public function isCompatibleWith(AdType $adType): bool
    {
        return match($this) {
            self::ONLINE => in_array($adType, [
                AdType::WELLNESS,
                AdType::THERAPY,
                AdType::FITNESS,
                AdType::RELAXATION,
            ]),
            default => true, // Все остальные локации совместимы с любыми типами
        };
    }

    /**
     * Получить множитель цены (некоторые локации дороже)
     */
    public function getPriceMultiplier(): float
    {
        return match($this) {
            self::OUTCALL => 1.3,      // Выезд дороже на 30%
            self::ONLINE => 0.7,       // Онлайн дешевле на 30%
            default => 1.0,
        };
    }

    /**
     * Получить все места для выборки
     */
    public static function options(): array
    {
        $locations = [];
        foreach (self::cases() as $location) {
            $locations[$location->value] = $location->getLabel();
        }
        return $locations;
    }

    /**
     * Получить популярные локации
     */
    public static function popularLocations(): array
    {
        return [
            self::SALON,
            self::OUTCALL,
            self::BOTH,
        ];
    }
}