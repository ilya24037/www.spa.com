<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\DTOs\PaymentFilterDTO;
use Carbon\Carbon;

/**
 * Сервис для создания фильтров по датам
 */
class PaymentFilterDateService
{
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
}