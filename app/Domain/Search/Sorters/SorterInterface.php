<?php

namespace App\Domain\Search\Sorters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Интерфейс для всех сортировщиков поиска
 * Соответствует DDD архитектуре и SOLID принципам
 */
interface SorterInterface
{
    /**
     * Применить сортировку к запросу
     */
    public function apply(Builder $query): Builder;

    /**
     * Получить параметры сортировки
     */
    public function getParams(): array;

    /**
     * Получить описание сортировки для UI
     */
    public function getDescription(): string;

    /**
     * Клонировать сортировщик
     */
    public function clone(): self;

    /**
     * Установить направление сортировки
     */
    public function setDirection(string $direction): self;

    /**
     * Инвертировать направление сортировки
     */
    public function invert(): self;
}