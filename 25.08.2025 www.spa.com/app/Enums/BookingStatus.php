<?php

namespace App\Enums;

/**
 * Статусы бронирований
 */
enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED_BY_CLIENT = 'cancelled_by_client';
    case CANCELLED_BY_MASTER = 'cancelled_by_master';
    case NO_SHOW = 'no_show';
    case RESCHEDULED = 'rescheduled';

    /**
     * Получить читаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает подтверждения',
            self::CONFIRMED => 'Подтверждено',
            self::IN_PROGRESS => 'В процессе',
            self::COMPLETED => 'Завершено',
            self::CANCELLED_BY_CLIENT => 'Отменено клиентом',
            self::CANCELLED_BY_MASTER => 'Отменено мастером',
            self::NO_SHOW => 'Клиент не пришел',
            self::RESCHEDULED => 'Перенесено',
        };
    }

    /**
     * Получить описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::PENDING => 'Бронирование создано и ожидает подтверждения от мастера',
            self::CONFIRMED => 'Мастер подтвердил бронирование',
            self::IN_PROGRESS => 'Услуга выполняется в данный момент',
            self::COMPLETED => 'Услуга успешно выполнена',
            self::CANCELLED_BY_CLIENT => 'Клиент отменил бронирование',
            self::CANCELLED_BY_MASTER => 'Мастер отменил бронирование',
            self::NO_SHOW => 'Клиент не явился на сеанс',
            self::RESCHEDULED => 'Время бронирования было изменено',
        };
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => '#F59E0B',           // amber
            self::CONFIRMED => '#3B82F6',        // blue
            self::IN_PROGRESS => '#8B5CF6',      // violet
            self::COMPLETED => '#10B981',        // green
            self::CANCELLED_BY_CLIENT => '#EF4444',   // red
            self::CANCELLED_BY_MASTER => '#DC2626',   // red-600
            self::NO_SHOW => '#6B7280',          // gray
            self::RESCHEDULED => '#F97316',      // orange
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::CONFIRMED => '✅',
            self::IN_PROGRESS => '🔄',
            self::COMPLETED => '🎉',
            self::CANCELLED_BY_CLIENT => '❌',
            self::CANCELLED_BY_MASTER => '🚫',
            self::NO_SHOW => '👻',
            self::RESCHEDULED => '📅',
        };
    }

    /**
     * Проверить активен ли статус
     */
    public function isActive(): bool
    {
        return match($this) {
            self::PENDING, self::CONFIRMED, self::IN_PROGRESS => true,
            default => false,
        };
    }

    /**
     * Проверить завершен ли статус
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Проверить отменен ли статус
     */
    public function isCancelled(): bool
    {
        return match($this) {
            self::CANCELLED_BY_CLIENT, self::CANCELLED_BY_MASTER, self::NO_SHOW => true,
            default => false,
        };
    }

    /**
     * Проверить требует ли статус оплаты
     */
    public function requiresPayment(): bool
    {
        return match($this) {
            self::CONFIRMED, self::IN_PROGRESS, self::COMPLETED => true,
            default => false,
        };
    }

    /**
     * Проверить можно ли отменить бронирование
     */
    public function canBeCancelled(): bool
    {
        return match($this) {
            self::PENDING, self::CONFIRMED => true,
            default => false,
        };
    }

    /**
     * Проверить можно ли перенести бронирование
     */
    public function canBeRescheduled(): bool
    {
        return match($this) {
            self::PENDING, self::CONFIRMED => true,
            default => false,
        };
    }

    /**
     * Проверить можно ли начать выполнение услуги
     */
    public function canStart(): bool
    {
        return $this === self::CONFIRMED;
    }

    /**
     * Проверить можно ли завершить услугу
     */
    public function canComplete(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    /**
     * Проверить можно ли оставить отзыв
     */
    public function canReview(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Получить возможные следующие статусы
     */
    public function getNextPossibleStatuses(): array
    {
        return match($this) {
            self::PENDING => [
                self::CONFIRMED,
                self::CANCELLED_BY_CLIENT,
                self::CANCELLED_BY_MASTER,
                self::RESCHEDULED
            ],
            self::CONFIRMED => [
                self::IN_PROGRESS,
                self::CANCELLED_BY_CLIENT,
                self::CANCELLED_BY_MASTER,
                self::NO_SHOW,
                self::RESCHEDULED
            ],
            self::IN_PROGRESS => [
                self::COMPLETED,
                self::CANCELLED_BY_MASTER
            ],
            self::COMPLETED => [],
            self::CANCELLED_BY_CLIENT => [],
            self::CANCELLED_BY_MASTER => [],
            self::NO_SHOW => [],
            self::RESCHEDULED => [
                self::PENDING,
                self::CONFIRMED
            ],
        };
    }

    /**
     * Проверить возможен ли переход к указанному статусу
     */
    public function canTransitionTo(BookingStatus $status): bool
    {
        return in_array($status, $this->getNextPossibleStatuses());
    }

    /**
     * Получить активные статусы
     */
    public static function getActiveStatuses(): array
    {
        return [self::PENDING, self::CONFIRMED, self::IN_PROGRESS];
    }

    /**
     * Получить завершенные статусы
     */
    public static function getCompletedStatuses(): array
    {
        return [self::COMPLETED];
    }

    /**
     * Получить отмененные статусы
     */
    public static function getCancelledStatuses(): array
    {
        return [
            self::CANCELLED_BY_CLIENT,
            self::CANCELLED_BY_MASTER,
            self::NO_SHOW
        ];
    }

    /**
     * Получить статусы для фильтрации
     */
    public static function getFilterStatuses(): array
    {
        return [
            'active' => self::getActiveStatuses(),
            'completed' => self::getCompletedStatuses(),
            'cancelled' => self::getCancelledStatuses(),
        ];
    }

    /**
     * Получить все статусы для выборки
     */
    public static function options(): array
    {
        $statuses = [];
        foreach (self::cases() as $status) {
            $statuses[$status->value] = $status->getLabel();
        }
        return $statuses;
    }

    /**
     * Получить статус по умолчанию для новых бронирований
     */
    public static function default(): self
    {
        return self::PENDING;
    }

    /**
     * Получить приоритет статуса для сортировки
     */
    public function getPriority(): int
    {
        return match($this) {
            self::IN_PROGRESS => 1,
            self::CONFIRMED => 2,
            self::PENDING => 3,
            self::RESCHEDULED => 4,
            self::COMPLETED => 5,
            self::CANCELLED_BY_CLIENT => 6,
            self::CANCELLED_BY_MASTER => 7,
            self::NO_SHOW => 8,
        };
    }

    /**
     * Проверить нужно ли напоминание
     */
    public function needsReminder(): bool
    {
        return match($this) {
            self::CONFIRMED => true,
            default => false,
        };
    }

    /**
     * Получить время до автоотмены (в часах)
     */
    public function getAutoCanelHours(): ?int
    {
        return match($this) {
            self::PENDING => config('booking.auto_cancel_pending_hours', 24),
            default => null,
        };
    }
}