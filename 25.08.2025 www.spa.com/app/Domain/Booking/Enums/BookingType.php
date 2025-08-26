<?php

namespace App\Domain\Booking\Enums;

/**
 * Типы бронирования
 * Определяет способ предоставления услуги
 */
enum BookingType: string
{
    case HOME_SERVICE = 'home_service';          // Услуга на дому у клиента
    case SALON_SERVICE = 'salon_service';        // Услуга в салоне мастера
    case ONLINE_SERVICE = 'online_service';      // Онлайн консультация
    case MOBILE_SERVICE = 'mobile_service';      // Выездная услуга
    case HYBRID_SERVICE = 'hybrid_service';      // Гибридная услуга (часть онлайн, часть офлайн)

    /**
     * Получить русское название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::HOME_SERVICE => 'Услуга на дому',
            self::SALON_SERVICE => 'В салоне мастера',
            self::ONLINE_SERVICE => 'Онлайн консультация',
            self::MOBILE_SERVICE => 'Выездная услуга',
            self::HYBRID_SERVICE => 'Гибридная услуга',
        };
    }

    /**
     * Получить короткое название
     */
    public function getShortLabel(): string
    {
        return match($this) {
            self::HOME_SERVICE => 'На дому',
            self::SALON_SERVICE => 'В салоне',
            self::ONLINE_SERVICE => 'Онлайн',
            self::MOBILE_SERVICE => 'Выезд',
            self::HYBRID_SERVICE => 'Гибрид',
        };
    }

    /**
     * Получить описание типа
     */
    public function getDescription(): string
    {
        return match($this) {
            self::HOME_SERVICE => 'Мастер приедет к вам домой или в офис',
            self::SALON_SERVICE => 'Услуга будет оказана в салоне мастера',
            self::ONLINE_SERVICE => 'Консультация через видеосвязь',
            self::MOBILE_SERVICE => 'Мастер работает с выездом в любое место',
            self::HYBRID_SERVICE => 'Комбинация очной и онлайн работы',
        };
    }

    /**
     * Получить иконку типа
     */
    public function getIcon(): string
    {
        return match($this) {
            self::HOME_SERVICE => 'home',
            self::SALON_SERVICE => 'building-storefront',
            self::ONLINE_SERVICE => 'video-camera',
            self::MOBILE_SERVICE => 'truck',
            self::HYBRID_SERVICE => 'puzzle-piece',
        };
    }

    /**
     * Получить цвет типа для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::HOME_SERVICE => 'blue',
            self::SALON_SERVICE => 'green',
            self::ONLINE_SERVICE => 'purple',
            self::MOBILE_SERVICE => 'orange',
            self::HYBRID_SERVICE => 'indigo',
        };
    }

    /**
     * Требует ли тип физический адрес
     */
    public function requiresAddress(): bool
    {
        return match($this) {
            self::HOME_SERVICE, self::MOBILE_SERVICE => true,
            self::SALON_SERVICE => false, // Адрес мастера уже известен
            self::ONLINE_SERVICE, self::HYBRID_SERVICE => false,
        };
    }

    /**
     * Требует ли тип транспортные расходы
     */
    public function requiresTravelFee(): bool
    {
        return match($this) {
            self::HOME_SERVICE, self::MOBILE_SERVICE => true,
            self::SALON_SERVICE, self::ONLINE_SERVICE, self::HYBRID_SERVICE => false,
        };
    }

    /**
     * Требует ли тип онлайн инструменты
     */
    public function requiresOnlineTools(): bool
    {
        return match($this) {
            self::ONLINE_SERVICE, self::HYBRID_SERVICE => true,
            self::HOME_SERVICE, self::SALON_SERVICE, self::MOBILE_SERVICE => false,
        };
    }

    /**
     * Требует ли тип подтверждение адреса
     */
    public function requiresAddressConfirmation(): bool
    {
        return $this->requiresAddress();
    }

    /**
     * Может ли быть изменен на другой тип
     */
    public function canChangeTo(self $newType): bool
    {
        // Онлайн услуги легко меняются
        if ($this === self::ONLINE_SERVICE) {
            return true;
        }

        // Физические услуги можно поменять между собой до подтверждения
        if ($this->isPhysical() && $newType->isPhysical()) {
            return true;
        }

        // Гибридные услуги гибкие
        if ($this === self::HYBRID_SERVICE || $newType === self::HYBRID_SERVICE) {
            return true;
        }

        return false;
    }

    /**
     * Является ли тип физической услугой
     */
    public function isPhysical(): bool
    {
        return in_array($this, [
            self::HOME_SERVICE,
            self::SALON_SERVICE,
            self::MOBILE_SERVICE,
        ]);
    }

    /**
     * Является ли тип онлайн услугой
     */
    public function isOnline(): bool
    {
        return in_array($this, [
            self::ONLINE_SERVICE,
            self::HYBRID_SERVICE,
        ]);
    }

    /**
     * Получить множитель цены для типа
     */
    public function getPriceMultiplier(): float
    {
        return match($this) {
            self::HOME_SERVICE => 1.2,      // +20% за выезд на дом
            self::SALON_SERVICE => 1.0,     // Базовая цена
            self::ONLINE_SERVICE => 0.8,    // -20% за онлайн
            self::MOBILE_SERVICE => 1.5,    // +50% за мобильность
            self::HYBRID_SERVICE => 1.1,    // +10% за гибкость
        };
    }

    /**
     * Получить минимальное время подготовки в минутах
     */
    public function getPreparationTime(): int
    {
        return match($this) {
            self::HOME_SERVICE => 30,       // 30 минут на дорогу и подготовку
            self::SALON_SERVICE => 15,      // 15 минут на подготовку места
            self::ONLINE_SERVICE => 5,      // 5 минут на подключение
            self::MOBILE_SERVICE => 45,     // 45 минут на дорогу и подготовку оборудования
            self::HYBRID_SERVICE => 20,     // 20 минут на настройку всех инструментов
        };
    }

    /**
     * Получить типы для фильтров
     */
    public static function getFilterOptions(): array
    {
        return [
            'physical' => [self::HOME_SERVICE, self::SALON_SERVICE, self::MOBILE_SERVICE],
            'online' => [self::ONLINE_SERVICE],
            'hybrid' => [self::HYBRID_SERVICE],
        ];
    }

    /**
     * Получить популярные типы
     */
    public static function getPopularTypes(): array
    {
        return [
            self::HOME_SERVICE,
            self::SALON_SERVICE,
            self::ONLINE_SERVICE,
        ];
    }
}