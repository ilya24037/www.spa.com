<?php

namespace App\Enums;

/**
 * Типы бронирований
 */
enum BookingType: string
{
    case OUTCALL = 'outcall';       // Выезд к клиенту
    case INCALL = 'incall';         // Прием у мастера
    case ONLINE = 'online';         // Онлайн консультация
    case PACKAGE = 'package';       // Пакет услуг

    /**
     * Получить читаемое название типа
     */
    public function getLabel(): string
    {
        return match($this) {
            self::OUTCALL => 'Выезд к клиенту',
            self::INCALL => 'Прием у мастера',
            self::ONLINE => 'Онлайн консультация',
            self::PACKAGE => 'Пакет услуг',
        };
    }

    /**
     * Получить описание типа
     */
    public function getDescription(): string
    {
        return match($this) {
            self::OUTCALL => 'Мастер приезжает к клиенту по указанному адресу',
            self::INCALL => 'Клиент приезжает к мастеру в салон или студию',
            self::ONLINE => 'Консультация проводится онлайн (видеозвонок, чат)',
            self::PACKAGE => 'Комплекс услуг, включающий несколько процедур',
        };
    }

    /**
     * Получить иконку типа
     */
    public function getIcon(): string
    {
        return match($this) {
            self::OUTCALL => '🚗',
            self::INCALL => '🏢',
            self::ONLINE => '💻',
            self::PACKAGE => '📦',
        };
    }

    /**
     * Получить цвет типа для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::OUTCALL => '#3B82F6',    // blue
            self::INCALL => '#10B981',     // green
            self::ONLINE => '#8B5CF6',     // violet
            self::PACKAGE => '#F59E0B',    // amber
        };
    }

    /**
     * Проверить требует ли тип адрес
     */
    public function requiresAddress(): bool
    {
        return match($this) {
            self::OUTCALL => true,
            self::INCALL => true,
            default => false,
        };
    }

    /**
     * Проверить требует ли тип дополнительную плату за выезд
     */
    public function hasDeliveryFee(): bool
    {
        return $this === self::OUTCALL;
    }

    /**
     * Проверить поддерживает ли тип онлайн оплату
     */
    public function supportsOnlinePayment(): bool
    {
        return match($this) {
            self::ONLINE, self::PACKAGE => true,
            default => config('booking.online_payment_enabled', true),
        };
    }

    /**
     * Проверить поддерживает ли тип предоплату
     */
    public function supportsPrepayment(): bool
    {
        return match($this) {
            self::OUTCALL, self::PACKAGE => true,
            default => false,
        };
    }

    /**
     * Получить минимальное время заказа заранее (в часах)
     */
    public function getMinAdvanceHours(): int
    {
        return match($this) {
            self::OUTCALL => 4,     // Выезд требует больше времени на подготовку
            self::INCALL => 2,      // Прием в салоне
            self::ONLINE => 1,      // Онлайн быстрее всего
            self::PACKAGE => 8,     // Пакет услуг требует подготовки
        };
    }

    /**
     * Получить максимальную продолжительность (в часах)
     */
    public function getMaxDurationHours(): int
    {
        return match($this) {
            self::OUTCALL => 8,     // Целый день
            self::INCALL => 6,      // Рабочий день в салоне
            self::ONLINE => 2,      // Консультация
            self::PACKAGE => 12,    // Комплексный пакет
        };
    }

    /**
     * Получить стандартную продолжительность (в минутах)
     */
    public function getDefaultDurationMinutes(): int
    {
        return match($this) {
            self::OUTCALL => 120,   // 2 часа
            self::INCALL => 90,     // 1.5 часа
            self::ONLINE => 60,     // 1 час
            self::PACKAGE => 180,   // 3 часа
        };
    }

    /**
     * Проверить поддерживает ли тип отмену за 24 часа
     */
    public function allowsFreeCancellation(): bool
    {
        return match($this) {
            self::ONLINE => true,
            default => false,
        };
    }

    /**
     * Получить процент штрафа за отмену
     */
    public function getCancellationFeePercent(): int
    {
        return match($this) {
            self::OUTCALL => 30,    // Высокий штраф за выезд
            self::INCALL => 20,     // Средний штраф
            self::ONLINE => 0,      // Без штрафа
            self::PACKAGE => 50,    // Максимальный штраф за пакет
        };
    }

    /**
     * Проверить поддерживает ли тип групповые бронирования
     */
    public function supportsGroupBooking(): bool
    {
        return match($this) {
            self::INCALL, self::PACKAGE => true,
            default => false,
        };
    }

    /**
     * Получить максимальное количество участников
     */
    public function getMaxParticipants(): int
    {
        return match($this) {
            self::OUTCALL => 1,
            self::INCALL => 4,
            self::ONLINE => 1,
            self::PACKAGE => 6,
        };
    }

    /**
     * Проверить требует ли тип подтверждения оборудования
     */
    public function requiresEquipmentConfirmation(): bool
    {
        return match($this) {
            self::OUTCALL => true,  // Нужно уточнить что везти
            default => false,
        };
    }

    /**
     * Получить список необходимых полей для бронирования
     */
    public function getRequiredFields(): array
    {
        $baseFields = ['client_id', 'master_id', 'service_id', 'start_time', 'duration'];
        
        return match($this) {
            self::OUTCALL => array_merge($baseFields, [
                'client_address', 'client_phone', 'equipment_list'
            ]),
            self::INCALL => array_merge($baseFields, [
                'master_address', 'parking_info'
            ]),
            self::ONLINE => array_merge($baseFields, [
                'platform', 'meeting_link'
            ]),
            self::PACKAGE => array_merge($baseFields, [
                'services_list', 'break_times', 'total_duration'
            ]),
        };
    }

    /**
     * Получить настройки напоминаний (в часах до начала)
     */
    public function getReminderHours(): array
    {
        return match($this) {
            self::OUTCALL => [24, 4, 1],    // За сутки, 4 часа и час
            self::INCALL => [24, 2],        // За сутки и 2 часа
            self::ONLINE => [2, 0.25],      // За 2 часа и 15 минут
            self::PACKAGE => [48, 24, 4],   // За 2 дня, сутки и 4 часа
        };
    }

    /**
     * Получить приоритет типа для отображения
     */
    public function getPriority(): int
    {
        return match($this) {
            self::PACKAGE => 1,     // Самый важный
            self::OUTCALL => 2,     // Выезд
            self::INCALL => 3,      // Прием
            self::ONLINE => 4,      // Онлайн
        };
    }

    /**
     * Получить все типы для выборки
     */
    public static function options(): array
    {
        $types = [];
        foreach (self::cases() as $type) {
            $types[$type->value] = $type->getLabel();
        }
        return $types;
    }

    /**
     * Получить тип по умолчанию
     */
    public static function default(): self
    {
        return self::INCALL;
    }

    /**
     * Получить популярные типы
     */
    public static function getPopularTypes(): array
    {
        return [self::INCALL, self::OUTCALL];
    }

    /**
     * Получить типы, доступные для мобильных мастеров
     */
    public static function getMobileTypes(): array
    {
        return [self::OUTCALL, self::ONLINE];
    }

    /**
     * Получить типы, доступные для салонов
     */
    public static function getSalonTypes(): array
    {
        return [self::INCALL, self::PACKAGE];
    }
}