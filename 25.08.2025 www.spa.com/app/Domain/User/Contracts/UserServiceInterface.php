<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserProfile;
use App\Domain\User\Models\UserSettings;
use Illuminate\Database\Eloquent\Collection;

/**
 * Интерфейс сервиса пользователей
 * Для развязки доменов и инкапсуляции бизнес-логики
 */
interface UserServiceInterface
{
    /**
     * Зарегистрировать нового пользователя
     */
    public function registerUser(array $data): User;

    /**
     * Обновить профиль пользователя
     */
    public function updateUserProfile(int $userId, array $data): bool;

    /**
     * Обновить настройки пользователя
     */
    public function updateUserSettings(int $userId, array $data): bool;

    /**
     * Изменить роль пользователя
     */
    public function changeUserRole(int $userId, string $newRole, ?int $changedBy = null, ?string $reason = null): bool;

    /**
     * Деактивировать пользователя
     */
    public function deactivateUser(int $userId, ?string $reason = null): bool;

    /**
     * Активировать пользователя
     */
    public function activateUser(int $userId): bool;

    /**
     * Удалить пользователя и все связанные данные
     */
    public function deleteUser(int $userId): bool;

    /**
     * Получить полную информацию о пользователе
     */
    public function getUserFullInfo(int $userId): array;

    /**
     * Валидировать данные пользователя
     */
    public function validateUserData(array $data): array;

    /**
     * Отправить уведомления пользователю
     */
    public function sendUserNotifications(User $user, string $eventType, array $data = []): void;

    /**
     * Получить статистику пользователей
     */
    public function getUserStatistics(array $filters = []): array;

    /**
     * Синхронизировать данные пользователя между системами
     */
    public function syncUserData(int $userId): bool;
}