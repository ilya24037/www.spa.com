<?php

namespace App\Domain\Search\Contracts;

/**
 * Интерфейс для моделей с поддержкой поиска
 */
interface SearchableInterface
{
    /**
     * Получить индексируемые данные
     */
    public function toSearchableArray(): array;

    /**
     * Получить уникальный идентификатор для поиска
     */
    public function getSearchableId();

    /**
     * Получить тип модели для поиска
     */
    public function getSearchableType(): string;

    /**
     * Определить, должна ли модель быть проиндексирована
     */
    public function shouldBeSearchable(): bool;
}