<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\DTOs\PaymentFilterDTO;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Carbon\Carbon;

/**
 * Сервис для построения фильтров платежей через fluent интерфейс
 */
class PaymentFilterBuilderService
{
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
    public function forUser(PaymentFilterDTO $filter, int $userId): PaymentFilterDTO
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
     * Установить поиск
     */
    public function search(PaymentFilterDTO $filter, string $query): PaymentFilterDTO
    {
        $clone = clone $filter;
        $clone->search = $query;
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
     * Сбросить все фильтры
     */
    public function reset(PaymentFilterDTO $filter): PaymentFilterDTO
    {
        return new PaymentFilterDTO(
            sortBy: $filter->sortBy,
            sortOrder: $filter->sortOrder,
            perPage: $filter->perPage,
        );
    }
}