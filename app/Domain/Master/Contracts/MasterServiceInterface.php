<?php

namespace App\Domain\Master\Contracts;

use App\Domain\Master\Models\MasterProfile;
use Illuminate\Database\Eloquent\Collection;

/**
 * Интерфейс сервиса мастеров
 * Для развязки доменов и инкапсуляции бизнес-логики
 */
interface MasterServiceInterface
{
    /**
     * Создать профиль мастера
     */
    public function createMasterProfile(int $userId, array $data): MasterProfile;

    /**
     * Обновить профиль мастера
     */
    public function updateMasterProfile(int $profileId, array $data): bool;

    /**
     * Активировать профиль мастера
     */
    public function activateMasterProfile(int $profileId): bool;

    /**
     * Деактивировать профиль мастера
     */
    public function deactivateMasterProfile(int $profileId, ?string $reason = null): bool;

    /**
     * Заблокировать мастера
     */
    public function blockMaster(int $profileId, int $blockedBy, string $reason): bool;

    /**
     * Разблокировать мастера
     */
    public function unblockMaster(int $profileId, int $unblockedBy): bool;

    /**
     * Валидировать данные профиля мастера
     */
    public function validateMasterProfileData(array $data): array;

    /**
     * Получить рекомендованных мастеров
     */
    public function getRecommendedMasters(array $criteria, int $limit = 10): Collection;

    /**
     * Получить похожих мастеров
     */
    public function getSimilarMasters(int $profileId, int $limit = 5): Collection;

    /**
     * Обновить рейтинг мастера
     */
    public function updateMasterRating(int $profileId): bool;

    /**
     * Получить статистику по мастерам
     */
    public function getMasterStatistics(array $filters = []): array;

    /**
     * Отправить уведомления мастеру
     */
    public function sendMasterNotifications(MasterProfile $master, string $eventType, array $data = []): void;
}