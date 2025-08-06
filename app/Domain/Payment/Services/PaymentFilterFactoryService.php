<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\DTOs\PaymentFilterDTO;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

/**
 * Сервис для создания фильтров платежей
 */
class PaymentFilterFactoryService
{
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
                PaymentStatus::EXPIRED->value,
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
}