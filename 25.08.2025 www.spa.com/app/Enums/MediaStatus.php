<?php

namespace App\Enums;

/**
 * Статусы медиа файлов
 */
enum MediaStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    /**
     * Получить читаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает обработки',
            self::PROCESSING => 'Обрабатывается',
            self::PROCESSED => 'Обработан',
            self::FAILED => 'Ошибка обработки',
            self::ARCHIVED => 'В архиве',
            self::DELETED => 'Удален',
        };
    }

    /**
     * Получить описание статуса
     */
    public function getDescription(): string
    {
        return match($this) {
            self::PENDING => 'Файл загружен и ожидает обработки',
            self::PROCESSING => 'Файл обрабатывается (генерация превью, оптимизация и т.д.)',
            self::PROCESSED => 'Файл успешно обработан и готов к использованию',
            self::FAILED => 'Произошла ошибка при обработке файла',
            self::ARCHIVED => 'Файл перемещен в архив',
            self::DELETED => 'Файл помечен как удаленный',
        ];
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => '#F59E0B',      // amber
            self::PROCESSING => '#3B82F6',   // blue
            self::PROCESSED => '#10B981',    // green
            self::FAILED => '#EF4444',       // red
            self::ARCHIVED => '#6B7280',     // gray
            self::DELETED => '#4B5563',      // gray-600
        };
    }

    /**
     * Получить иконку статуса
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => '⏳',
            self::PROCESSING => '⚙️',
            self::PROCESSED => '✅',
            self::FAILED => '❌',
            self::ARCHIVED => '📦',
            self::DELETED => '🗑️',
        };
    }

    /**
     * Проверить доступен ли файл для использования
     */
    public function isAvailable(): bool
    {
        return match($this) {
            self::PROCESSED => true,
            default => false,
        };
    }

    /**
     * Проверить нужна ли обработка файла
     */
    public function needsProcessing(): bool
    {
        return match($this) {
            self::PENDING => true,
            default => false,
        };
    }

    /**
     * Проверить обрабатывается ли файл в данный момент
     */
    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }

    /**
     * Проверить произошла ли ошибка
     */
    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Проверить удален ли файл
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * Проверить можно ли удалить файл
     */
    public function canBeDeleted(): bool
    {
        return match($this) {
            self::PROCESSED, self::FAILED, self::ARCHIVED => true,
            default => false,
        };
    }

    /**
     * Проверить можно ли восстановить файл
     */
    public function canBeRestored(): bool
    {
        return match($this) {
            self::ARCHIVED, self::DELETED => true,
            default => false,
        };
    }

    /**
     * Получить возможные следующие статусы
     */
    public function getNextPossibleStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::PROCESSING, self::FAILED, self::DELETED],
            self::PROCESSING => [self::PROCESSED, self::FAILED, self::DELETED],
            self::PROCESSED => [self::ARCHIVED, self::DELETED],
            self::FAILED => [self::PENDING, self::DELETED],
            self::ARCHIVED => [self::PROCESSED, self::DELETED],
            self::DELETED => [], // Нельзя изменить статус удаленного файла
        };
    }

    /**
     * Получить статусы для фильтрации
     */
    public static function getActiveStatuses(): array
    {
        return [self::PENDING, self::PROCESSING, self::PROCESSED];
    }

    /**
     * Получить статусы с ошибками
     */
    public static function getErrorStatuses(): array
    {
        return [self::FAILED];
    }

    /**
     * Получить неактивные статусы
     */
    public static function getInactiveStatuses(): array
    {
        return [self::ARCHIVED, self::DELETED];
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
     * Получить статус по умолчанию для новых файлов
     */
    public static function default(): self
    {
        return self::PENDING;
    }
}