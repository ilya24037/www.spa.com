<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\DTOs\PaymentFilterDTO;

/**
 * Сервис для анализа фильтров платежей
 */
class PaymentFilterAnalysisService
{
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
            $labels = array_map(fn($s) => $s->getLabel(), $filter->statuses);
            $description[] = 'Статус: ' . implode(', ', $labels);
        }

        if ($filter->methods) {
            $labels = array_map(fn($m) => $m->getLabel(), $filter->methods);
            $description[] = 'Способ: ' . implode(', ', $labels);
        }

        if ($filter->types) {
            $labels = array_map(fn($t) => $t->getLabel(), $filter->types);
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

        return $description;
    }
}