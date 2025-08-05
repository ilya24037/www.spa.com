<?php

namespace App\Domain\User\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Domain\User\Models\User;

/**
 * Интерфейс для запросов к пользователям
 * Для чтения данных без изменения состояния (CQRS pattern)
 */
interface UserQueryInterface
{
    /**
     * Получить пользователей с пагинацией
     */
    public function getUsersPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Поиск пользователей по имени и email
     */
    public function searchUsers(string $query, array $filters = []): Collection;

    /**
     * Получить новых пользователей
     */
    public function getNewUsers(int $days = 30, int $limit = 10): Collection;

    /**
     * Получить активных пользователей
     */
    public function getActiveUsers(int $period = 30): Collection;

    /**
     * Получить неактивных пользователей
     */
    public function getInactiveUsers(int $days = 30): Collection;

    /**
     * Получить пользователей для модерации
     */
    public function getUsersForModeration(): Collection;

    /**
     * Получить статистику регистраций по периодам
     */
    public function getRegistrationStatistics(string $period): array;

    /**
     * Получить популярные города пользователей
     */
    public function getPopularCities(int $limit = 10): array;

    /**
     * Получить отчет по активности пользователей
     */
    public function getUserActivityReport(array $filters): array;

    /**
     * Получить пользователей с неполными профилями
     */
    public function getUsersWithIncompleteProfiles(): Collection;

    /**
     * Получить топ пользователей по активности
     */
    public function getTopActiveUsers(int $limit = 10): Collection;

    /**
     * Получить пользователей по географии
     */
    public function getUsersByGeography(): array;

    /**
     * Получить демографические данные
     */
    public function getDemographics(): array;
}