<?php

namespace App\Domain\Search\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Интерфейс для всех фильтров поиска
 * Соответствует DDD архитектуре и SOLID принципам
 */
interface FilterInterface
{
    /**
     * Применить фильтр к запросу
     */
    public function apply(Builder $query): Builder;

    /**
     * Проверить, активен ли фильтр
     */
    public function isActive(): bool;

    /**
     * Получить активные параметры фильтра
     */
    public function getActiveParams(): array;

    /**
     * Получить описание фильтра для UI
     */
    public function getDescription(): string;

    /**
     * Сбросить фильтр
     */
    public function reset(): self;

    /**
     * Клонировать фильтр
     */
    public function clone(): self;
}