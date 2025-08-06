<?php

namespace App\Infrastructure\Cache\Contracts;

/**
 * Интерфейс стратегии кеширования
 */
interface CacheStrategyInterface
{
    /**
     * Получить ключ кеша для сущности
     */
    public function getKey($identifier): string;

    /**
     * Получить теги кеша для сущности
     */
    public function getTags($identifier): array;

    /**
     * Получить время жизни кеша
     */
    public function getTTL(): int;

    /**
     * Проверить, нужно ли кешировать данную операцию
     */
    public function shouldCache($data): bool;

    /**
     * Подготовить данные для кеширования
     */
    public function prepareForCache($data);

    /**
     * Обработать данные после получения из кеша
     */
    public function processFromCache($data);

    /**
     * Инвалидировать связанный кеш
     */
    public function invalidate($identifier): bool;
}