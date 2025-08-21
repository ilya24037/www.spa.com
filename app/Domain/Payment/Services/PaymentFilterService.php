<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\DTOs\PaymentFilterDTO;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Enums\PaymentType;
use Carbon\Carbon;

/**
 * Консолидированный сервис фильтрации платежей
 * Объединяет: PaymentFilterFactoryService, PaymentFilterDateService, 
 * PaymentFilterBuilderService, PaymentFilterAnalysisService
 */
class PaymentFilterService
{
    // ========== ФАБРИЧНЫЕ МЕТОДЫ (из PaymentFilterFactoryService) ==========

    /**
     * Фильтр для пользователя
     */
    public static function forUser(int $userId, array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'user_id' => $userId,
        ], $additionalFilters));
    }

    /**
     * Фильтр для успешных платежей
     */
    public static function successful(array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'statuses' => [PaymentStatus::COMPLETED->value],
        ], $additionalFilters));
    }

    /**
     * Фильтр для неудачных платежей
     */
    public static function failed(array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'statuses' => [
                PaymentStatus::FAILED->value,
                PaymentStatus::CANCELLED->value,
                PaymentStatus::EXPIRED->value ?? 'expired', // Fallback для случаев если EXPIRED не определён
            ],
        ], $additionalFilters));
    }

    /**
     * Фильтр для ожидающих платежей
     */
    public static function pending(array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'statuses' => [
                PaymentStatus::PENDING->value,
                PaymentStatus::PROCESSING->value,
            ],
        ], $additionalFilters));
    }

    /**
     * Фильтр для возвратов
     */
    public static function refunded(array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'statuses' => [
                PaymentStatus::REFUNDED->value,
                PaymentStatus::PARTIALLY_REFUNDED->value,
            ],
        ], $additionalFilters));
    }

    /**
     * Фильтр для авторизованных платежей
     */
    public static function authorized(array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'statuses' => [PaymentStatus::AUTHORIZED->value],
        ], $additionalFilters));
    }

    // ========== ФИЛЬТРЫ ПО ДАТАМ (из PaymentFilterDateService) ==========

    /**
     * Фильтр за период
     */
    public static function forPeriod(Carbon $from, Carbon $to, array $additionalFilters = []): PaymentFilterDTO
    {
        return PaymentFilterDTO::fromArray(array_merge([
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
        ], $additionalFilters));
    }

    /**
     * Фильтр за сегодня
     */
    public static function today(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->startOfDay(),
            now()->endOfDay(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за вчера
     */
    public static function yesterday(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->subDay()->startOfDay(),
            now()->subDay()->endOfDay(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за неделю
     */
    public static function thisWeek(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->startOfWeek(),
            now()->endOfWeek(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за прошлую неделю
     */
    public static function lastWeek(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за месяц
     */
    public static function thisMonth(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->startOfMonth(),
            now()->endOfMonth(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за прошлый месяц
     */
    public static function lastMonth(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за год
     */
    public static function thisYear(array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->startOfYear(),
            now()->endOfYear(),
            $additionalFilters
        );
    }

    /**
     * Фильтр за последние N дней
     */
    public static function lastDays(int $days, array $additionalFilters = []): PaymentFilterDTO
    {
        return self::forPeriod(
            now()->subDays($days)->startOfDay(),
            now()->endOfDay(),
            $additionalFilters
        );
    }

    // ========== СТРОИТЕЛЬ ФИЛЬТРОВ (из PaymentFilterBuilderService) ==========

    /**
     * Добавить статус
     */
    public function withStatus(PaymentFilterDTO $filter, PaymentStatus $status): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->statuses = array_merge($filter->statuses ?? [], [$status]);
        return $clone;
    }

    /**
     * Добавить метод
     */
    public function withMethod(PaymentFilterDTO $filter, PaymentMethod $method): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->methods = array_merge($filter->methods ?? [], [$method]);
        return $clone;
    }

    /**
     * Добавить тип
     */
    public function withType(PaymentFilterDTO $filter, PaymentType $type): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->types = array_merge($filter->types ?? [], [$type]);
        return $clone;
    }

    /**
     * Установить пользователя
     */
    public function forUserBuilder(PaymentFilterDTO $filter, int $userId): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->userId = $userId;
        return $clone;
    }

    /**
     * Установить диапазон сумм
     */
    public function amountRange(PaymentFilterDTO $filter, float $from, float $to): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->amountFrom = $from;
        $clone->amountTo = $to;
        return $clone;
    }

    /**
     * Установить минимальную сумму
     */
    public function amountFrom(PaymentFilterDTO $filter, float $from): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->amountFrom = $from;
        return $clone;
    }

    /**
     * Установить максимальную сумму
     */
    public function amountTo(PaymentFilterDTO $filter, float $to): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->amountTo = $to;
        return $clone;
    }

    /**
     * Установить диапазон дат
     */
    public function dateRange(PaymentFilterDTO $filter, Carbon $from, Carbon $to): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->dateFrom = $from;
        $clone->dateTo = $to;
        return $clone;
    }

    /**
     * Установить дату начала
     */
    public function dateFrom(PaymentFilterDTO $filter, Carbon $from): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->dateFrom = $from;
        return $clone;
    }

    /**
     * Установить дату окончания
     */
    public function dateTo(PaymentFilterDTO $filter, Carbon $to): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->dateTo = $to;
        return $clone;
    }

    /**
     * Установить поиск
     */
    public function search(PaymentFilterDTO $filter, string $query): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->search = $query;
        return $clone;
    }

    /**
     * Установить валюту
     */
    public function currency(PaymentFilterDTO $filter, string $currency): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->currency = $currency;
        return $clone;
    }

    /**
     * Установить сортировку
     */
    public function sortBy(PaymentFilterDTO $filter, string $field, string $order = 'desc'): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->sortBy = $field;
        $clone->sortOrder = $order;
        return $clone;
    }

    /**
     * Установить пагинацию
     */
    public function paginate(PaymentFilterDTO $filter, int $page, int $perPage = 20): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->page = $page;
        $clone->perPage = $perPage;
        return $clone;
    }

    /**
     * Фильтр только с возвратами
     */
    public function withRefunds(PaymentFilterDTO $filter, bool $hasRefunds = true): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->hasRefunds = $hasRefunds;
        return $clone;
    }

    /**
     * Сбросить все фильтры
     */
    public function reset(PaymentFilterDTO $filter): PaymentFilterDTO
    {
        return new PaymentFilterDTO(
            sortBy: $filter->sortBy ?? 'created_at',
            sortOrder: $filter->sortOrder ?? 'desc',
            perPage: $filter->perPage ?? 20,
        );
    }

    // ========== АНАЛИЗ ФИЛЬТРОВ (из PaymentFilterAnalysisService) ==========

    /**
     * Проверить есть ли активные фильтры
     */
    public function hasActiveFilters(PaymentFilterDTO $filter): bool
    {
        return !empty(array_filter([
            $filter->statuses,
            $filter->methods,
            $filter->types,
            $filter->userId,
            $filter->gateway,
            $filter->amountFrom,
            $filter->amountTo,
            $filter->dateFrom,
            $filter->dateTo,
            $filter->payableType,
            $filter->hasRefunds,
            $filter->search,
            $filter->currency,
        ]));
    }

    /**
     * Получить количество активных фильтров
     */
    public function getActiveFiltersCount(PaymentFilterDTO $filter): int
    {
        return count(array_filter([
            $filter->statuses,
            $filter->methods,
            $filter->types,
            $filter->userId,
            $filter->gateway,
            $filter->amountFrom,
            $filter->amountTo,
            $filter->dateFrom,
            $filter->dateTo,
            $filter->payableType,
            $filter->hasRefunds,
            $filter->search,
            $filter->currency,
        ]));
    }

    /**
     * Получить описание фильтров
     */
    public function getDescription(PaymentFilterDTO $filter): array
    {
        $description = [];

        if ($filter->statuses) {
            $labels = array_map(fn($s) => $this->getStatusLabel($s), $filter->statuses);
            $description[] = 'Статус: ' . implode(', ', $labels);
        }

        if ($filter->methods) {
            $labels = array_map(fn($m) => $this->getMethodLabel($m), $filter->methods);
            $description[] = 'Способ: ' . implode(', ', $labels);
        }

        if ($filter->types) {
            $labels = array_map(fn($t) => $this->getTypeLabel($t), $filter->types);
            $description[] = 'Тип: ' . implode(', ', $labels);
        }

        if ($filter->amountFrom || $filter->amountTo) {
            $from = $filter->amountFrom ? number_format($filter->amountFrom, 0, ',', ' ') : '0';
            $to = $filter->amountTo ? number_format($filter->amountTo, 0, ',', ' ') : '∞';
            $description[] = "Сумма: {$from} - {$to} руб.";
        }

        if ($filter->dateFrom || $filter->dateTo) {
            $from = $filter->dateFrom ? $filter->dateFrom->format('d.m.Y') : '';
            $to = $filter->dateTo ? $filter->dateTo->format('d.m.Y') : '';
            $description[] = "Период: {$from} - {$to}";
        }

        if ($filter->search) {
            $description[] = "Поиск: \"{$filter->search}\"";
        }

        if ($filter->currency) {
            $description[] = "Валюта: {$filter->currency}";
        }

        return $description;
    }

    /**
     * Получить краткое описание фильтров
     */
    public function getShortDescription(PaymentFilterDTO $filter): string
    {
        $count = $this->getActiveFiltersCount($filter);
        
        if ($count === 0) {
            return 'Все платежи';
        }

        $descriptions = $this->getDescription($filter);
        
        if ($count === 1) {
            return $descriptions[0];
        }

        return $descriptions[0] . " и ещё " . ($count - 1) . " фильтр" . ($count > 2 ? 'ов' : '');
    }

    /**
     * Проверить валидность фильтра
     */
    public function validateFilter(PaymentFilterDTO $filter): array
    {
        $errors = [];

        // Проверка диапазона сумм
        if ($filter->amountFrom && $filter->amountTo && $filter->amountFrom > $filter->amountTo) {
            $errors[] = 'Минимальная сумма не может быть больше максимальной';
        }

        // Проверка диапазона дат
        if ($filter->dateFrom && $filter->dateTo && $filter->dateFrom > $filter->dateTo) {
            $errors[] = 'Дата начала не может быть позже даты окончания';
        }

        // Проверка отрицательных значений
        if ($filter->amountFrom && $filter->amountFrom < 0) {
            $errors[] = 'Сумма не может быть отрицательной';
        }

        if ($filter->amountTo && $filter->amountTo < 0) {
            $errors[] = 'Сумма не может быть отрицательной';
        }

        // Проверка пагинации
        if ($filter->page && $filter->page < 1) {
            $errors[] = 'Номер страницы должен быть больше 0';
        }

        if ($filter->perPage && ($filter->perPage < 1 || $filter->perPage > 100)) {
            $errors[] = 'Количество элементов на странице должно быть от 1 до 100';
        }

        return $errors;
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========

    /**
     * Получить метку статуса
     */
    private function getStatusLabel($status): string
    {
        if ($status instanceof PaymentStatus) {
            return match($status) {
                PaymentStatus::PENDING => 'Ожидает оплаты',
                PaymentStatus::PROCESSING => 'Обрабатывается',
                PaymentStatus::COMPLETED => 'Завершён',
                PaymentStatus::FAILED => 'Ошибка',
                PaymentStatus::CANCELLED => 'Отменён',
                PaymentStatus::REFUNDED => 'Возвращён',
                PaymentStatus::PARTIALLY_REFUNDED => 'Частично возвращён',
                PaymentStatus::AUTHORIZED => 'Авторизован',
                default => 'Неизвестно'
            };
        }
        
        return ucfirst($status);
    }

    /**
     * Получить метку метода платежа
     */
    private function getMethodLabel($method): string
    {
        if ($method instanceof PaymentMethod) {
            return match($method) {
                PaymentMethod::CARD => 'Банковская карта',
                PaymentMethod::YOOKASSA => 'ЮKassa',
                PaymentMethod::SBP => 'СБП',
                PaymentMethod::BALANCE => 'Баланс аккаунта',
                PaymentMethod::WEBMONEY => 'WebMoney',
                default => 'Неизвестно'
            };
        }
        
        return ucfirst($method);
    }

    /**
     * Получить метку типа платежа
     */
    private function getTypeLabel($type): string
    {
        if ($type instanceof PaymentType) {
            // Предполагается что PaymentType имеет подобные константы
            return $type->getLabel() ?? ucfirst($type->value);
        }
        
        return ucfirst($type);
    }

    /**
     * Создать пустой фильтр
     */
    public static function empty(): PaymentFilterDTO
    {
        return new PaymentFilterDTO();
    }

    /**
     * Создать фильтр с базовыми настройками
     */
    public static function default(): PaymentFilterDTO
    {
        return new PaymentFilterDTO(
            sortBy: 'created_at',
            sortOrder: 'desc',
            perPage: 20
        );
    }
}