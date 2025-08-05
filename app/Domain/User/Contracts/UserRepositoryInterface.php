<?php

namespace App\Domain\User\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserProfile;
use App\Domain\User\Models\UserSettings;

/**
 * Интерфейс репозитория пользователей
 * Для развязки доменов и тестирования
 */
interface UserRepositoryInterface
{
    /**
     * Получить пользователя по ID
     */
    public function findById(int $userId): ?User;

    /**
     * Получить пользователя по email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Создать нового пользователя
     */
    public function create(array $data): User;

    /**
     * Обновить пользователя
     */
    public function update(int $userId, array $data): bool;

    /**
     * Удалить пользователя
     */
    public function delete(int $userId): bool;

    /**
     * Получить профиль пользователя
     */
    public function getUserProfile(int $userId): ?UserProfile;

    /**
     * Получить настройки пользователя
     */
    public function getUserSettings(int $userId): ?UserSettings;

    /**
     * Создать профиль пользователя
     */
    public function createUserProfile(int $userId, array $data): UserProfile;

    /**
     * Создать настройки пользователя
     */
    public function createUserSettings(int $userId, array $data): UserSettings;

    /**
     * Обновить профиль пользователя
     */
    public function updateUserProfile(int $userId, array $data): bool;

    /**
     * Обновить настройки пользователя
     */
    public function updateUserSettings(int $userId, array $data): bool;

    /**
     * Получить пользователей по роли
     */
    public function getUsersByRole(string $role): Collection;

    /**
     * Получить активных пользователей
     */
    public function getActiveUsers(): Collection;

    /**
     * Получить статистики пользователя
     */
    public function getUserStats(int $userId): array;

    /**
     * Изменить роль пользователя
     */
    public function changeUserRole(int $userId, string $newRole): bool;

    /**
     * Проверить существование пользователя
     */
    public function exists(int $userId): bool;
}