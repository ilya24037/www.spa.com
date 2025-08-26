<?php

namespace App\Domain\Booking\Enums;

/**
 * Статусы бронирования
 * Жизненный цикл: pending → confirmed → in_progress → completed
 * Альтернативные пути: pending/confirmed → cancelled
 */
enum BookingStatus: string
{
    case PENDING = 'pending';                    // Ожидает подтверждения
    case CONFIRMED = 'confirmed';                // Подтверждено
    case IN_PROGRESS = 'in_progress';            // Выполняется
    case COMPLETED = 'completed';                // Завершено
    case CANCELLED_BY_CLIENT = 'cancelled_by_client';    // Отменено клиентом
    case CANCELLED_BY_MASTER = 'cancelled_by_master';    // Отменено мастером
    case NO_SHOW = 'no_show';                    // Клиент не пришел
    case EXPIRED = 'expired';                    // Истекло

    /**
     * Получить русское название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждено',
            self::IN_PROGRESS => 'Выполняется',
            self::COMPLETED => 'Завершено',
            self::CANCELLED_BY_CLIENT => 'Отменено клиентом',
            self::CANCELLED_BY_MASTER => 'Отменено мастером',
            self::NO_SHOW => 'Клиент не пришел',
            self::EXPIRED => 'Истекло',
        };
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::CONFIRMED => 'blue',
            self::IN_PROGRESS => 'indigo',
            self::COMPLETED => 'green',
            self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_MASTER => 'red',
            self::NO_SHOW => 'gray',
            self::EXPIRED => 'orange',
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::CONFIRMED => 'check-circle',
            self::IN_PROGRESS => 'play-circle',
            self::COMPLETED => 'badge-check',
            self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_MASTER => 'x-circle',
            self::NO_SHOW => 'user-x',
            self::EXPIRED => 'calendar-x',
        };
    }

    /**
     * Может ли статус быть изменен на другой
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [
                self::CONFIRMED,
                self::CANCELLED_BY_CLIENT,
                self::CANCELLED_BY_MASTER,
                self::EXPIRED,
            ]),
            self::CONFIRMED => in_array($newStatus, [
                self::IN_PROGRESS,
                self::CANCELLED_BY_CLIENT,
                self::CANCELLED_BY_MASTER,
                self::NO_SHOW,
            ]),
            self::IN_PROGRESS => in_array($newStatus, [
                self::COMPLETED,
                self::CANCELLED_BY_MASTER, // Только мастер может отменить начатую услугу
            ]),
            self::COMPLETED, 
            self::CANCELLED_BY_CLIENT, 
            self::CANCELLED_BY_MASTER, 
            self::NO_SHOW, 
            self::EXPIRED => false, // Финальные статусы
        };
    }

    /**
     * Является ли статус активным (можно изменить)
     */
    public function isActive(): bool
    {
        return match($this) {
            self::PENDING, self::CONFIRMED, self::IN_PROGRESS => true,
            self::COMPLETED, self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_MASTER, self::NO_SHOW, self::EXPIRED => false,
        };
    }

    /**
     * Является ли статус завершенным
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Является ли статус отмененным
     */
    public function isCancelled(): bool
    {
        return in_array($this, [
            self::CANCELLED_BY_CLIENT,
            self::CANCELLED_BY_MASTER,
            self::NO_SHOW,
            self::EXPIRED,
        ]);
    }

    /**
     * Может ли быть отменено
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }

    /**
     * Требует ли статус оплаты
     */
    public function requiresPayment(): bool
    {
        return in_array($this, [self::CONFIRMED, self::IN_PROGRESS, self::COMPLETED]);
    }

    /**
     * Можно ли отправить напоминание
     */
    public function canSendReminder(): bool
    {
        return $this === self::CONFIRMED;
    }

    /**
     * Получить все возможные статусы для фильтров
     */
    public static function getFilterOptions(): array
    {
        return [
            'active' => [self::PENDING, self::CONFIRMED, self::IN_PROGRESS],
            'completed' => [self::COMPLETED],
            'cancelled' => [self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_MASTER, self::NO_SHOW, self::EXPIRED],
        ];
    }

    /**
     * Получить статусы для отчетов
     */
    public static function getReportingStatuses(): array
    {
        return [
            'pending' => self::PENDING,
            'confirmed' => self::CONFIRMED,
            'in_progress' => self::IN_PROGRESS,
            'completed' => self::COMPLETED,
            'cancelled' => [self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_MASTER],
            'no_show' => self::NO_SHOW,
            'expired' => self::EXPIRED,
        ];
    }
}