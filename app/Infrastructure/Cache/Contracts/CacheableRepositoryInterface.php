<?php

namespace App\Infrastructure\Cache\Contracts;

/**
 * Интерфейс для репозиториев с поддержкой кеширования
 */
interface CacheableRepositoryInterface
{
    /**
     * Найти сущность по ID с кешированием
     */
    public function findCached($id, ?int $ttl = null);

    /**
     * Найти все сущности с кешированием
     */
    public function allCached(?int $ttl = null);

    /**
     * Пагинированный поиск с кешированием
     */
    public function paginatedCached(int $perPage = 15, ?int $ttl = null);

    /**
     * Найти по условиям с кешированием
     */
    public function whereCached(array $conditions, ?int $ttl = null);

    /**
     * Инвалидировать кеш для конкретной сущности
     */
    public function invalidateCache($id): bool;

    /**
     * Инвалидировать весь кеш репозитория
     */
    public function invalidateAllCache(): bool;

    /**
     * Получить статистику кеширования
     */
    public function getCacheStats(): array;
}