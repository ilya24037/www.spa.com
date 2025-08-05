<?php

namespace App\Domain\Master\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Domain\Master\Models\MasterProfile;

/**
 * Интерфейс репозитория мастеров
 * Для развязки доменов и тестирования
 */
interface MasterRepositoryInterface
{
    /**
     * Получить профиль мастера пользователя
     */
    public function getUserMasterProfile(int $userId): ?MasterProfile;

    /**
     * Получить все профили мастера пользователя
     */
    public function getUserMasterProfiles(int $userId): Collection;

    /**
     * Получить основной профиль мастера
     */
    public function getMainMasterProfile(int $userId): ?MasterProfile;

    /**
     * Создать профиль мастера
     */
    public function create(array $data): MasterProfile;

    /**
     * Обновить профиль мастера
     */
    public function update(int $profileId, array $data): bool;

    /**
     * Удалить профиль мастера
     */
    public function delete(int $profileId): bool;

    /**
     * Получить мастеров по городу
     */
    public function getMastersByCity(string $city): Collection;

    /**
     * Получить мастеров по услугам
     */
    public function getMastersByServices(array $services): Collection;

    /**
     * Получить активных мастеров
     */
    public function getActiveMasters(): Collection;

    /**
     * Получить мастера по ID профиля
     */
    public function findById(int $profileId): ?MasterProfile;

    /**
     * Проверить есть ли активный профиль мастера у пользователя
     */
    public function hasActiveMasterProfile(int $userId): bool;

    /**
     * Изменить статус профиля мастера
     */
    public function updateStatus(int $profileId, string $status): bool;

    /**
     * Получить статистику мастера
     */
    public function getMasterStats(int $profileId): array;

    /**
     * Поиск мастеров по критериям
     */
    public function searchMasters(array $criteria): Collection;

    /**
     * Получить рейтинг мастера
     */
    public function getMasterRating(int $profileId): array;
}