<?php

namespace App\Application\Services\Integration;

use App\Domain\Master\Contracts\MasterRepositoryInterface;
use App\Domain\Master\Contracts\MasterServiceInterface;
use App\Domain\Master\Events\MasterProfileCreated;
use App\Domain\Master\Events\MasterStatusChanged;
use App\Domain\Master\Models\MasterProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;

/**
 * Сервис интеграции User ↔ Master доменов
 * Заменяет прямые связи через трейт HasMasterProfile
 */
class UserMasterIntegrationService
{
    public function __construct(
        private MasterRepositoryInterface $masterRepository,
        private MasterServiceInterface $masterService
    ) {}

    /**
     * Получить профиль мастера пользователя
     * Заменяет: $user->masterProfile()
     */
    public function getUserMasterProfile(int $userId): ?MasterProfile
    {
        return $this->masterRepository->getUserMasterProfile($userId);
    }

    /**
     * Получить все профили мастера пользователя
     * Заменяет: $user->masterProfiles()
     */
    public function getUserMasterProfiles(int $userId): Collection
    {
        return $this->masterRepository->getUserMasterProfiles($userId);
    }

    /**
     * Получить основной профиль мастера
     * Заменяет: $user->getMainMasterProfile()
     */
    public function getMainMasterProfile(int $userId): ?MasterProfile
    {
        return $this->masterRepository->getMainMasterProfile($userId);
    }

    /**
     * Проверить есть ли активный профиль мастера
     * Заменяет: $user->hasActiveMasterProfile()
     */
    public function hasActiveMasterProfile(int $userId): bool
    {
        return $this->masterRepository->hasActiveMasterProfile($userId);
    }

    /**
     * Создать профиль мастера через событие
     * Заменяет: $user->masterProfile()->create($data)
     */
    public function createMasterProfileForUser(int $userId, array $profileData): void
    {
        // Используем сервис для валидации и создания
        $masterProfile = $this->masterService->createMasterProfile($userId, $profileData);

        // Отправляем событие о создании
        Event::dispatch(new MasterProfileCreated(
            $userId,
            $masterProfile->id,
            $profileData
        ));
    }

    /**
     * Обновить профиль мастера
     * Заменяет прямое обновление через модель
     */
    public function updateUserMasterProfile(int $userId, int $profileId, array $data): bool
    {
        // Получаем старые данные для события
        $oldProfile = $this->masterRepository->findById($profileId);
        if (!$oldProfile || $oldProfile->user_id !== $userId) {
            return false;
        }

        $oldData = $oldProfile->toArray();
        $result = $this->masterService->updateMasterProfile($profileId, $data);

        if ($result) {
            // Определяем измененные поля
            $changedFields = array_keys(array_diff_assoc($data, $oldData));

            Event::dispatch(new \App\Domain\Master\Events\MasterProfileUpdated(
                $userId,
                $profileId,
                $oldData,
                $data,
                $changedFields
            ));
        }

        return $result;
    }

    /**
     * Активировать профиль мастера
     * Интеграция с процессом модерации
     */
    public function activateUserMasterProfile(int $userId, int $profileId): bool
    {
        $profile = $this->masterRepository->findById($profileId);
        if (!$profile || $profile->user_id !== $userId) {
            return false;
        }

        $oldStatus = $profile->status;
        $result = $this->masterService->activateMasterProfile($profileId);

        if ($result) {
            Event::dispatch(new MasterStatusChanged(
                $userId,
                $profileId,
                $oldStatus,
                'active'
            ));
        }

        return $result;
    }

    /**
     * Деактивировать профиль мастера
     * С указанием причины
     */
    public function deactivateUserMasterProfile(int $userId, int $profileId, ?string $reason = null): bool
    {
        $profile = $this->masterRepository->findById($profileId);
        if (!$profile || $profile->user_id !== $userId) {
            return false;
        }

        $oldStatus = $profile->status;
        $result = $this->masterService->deactivateMasterProfile($profileId, $reason);

        if ($result) {
            Event::dispatch(new MasterStatusChanged(
                $userId,
                $profileId,
                $oldStatus,
                'inactive',
                $reason
            ));
        }

        return $result;
    }

    /**
     * Получить статистики мастера
     * Для профиля и аналитики
     */
    public function getUserMasterStatistics(int $userId): array
    {
        $profile = $this->getUserMasterProfile($userId);
        if (!$profile) {
            return [];
        }

        return $this->masterRepository->getMasterStats($profile->id);
    }

    /**
     * Получить рейтинг мастера пользователя
     * Интеграция с системой отзывов
     */
    public function getUserMasterRating(int $userId): array
    {
        $profile = $this->getUserMasterProfile($userId);
        if (!$profile) {
            return ['rating' => 0, 'reviews_count' => 0];
        }

        return $this->masterRepository->getMasterRating($profile->id);
    }

    /**
     * Проверить может ли пользователь создать профиль мастера
     * Бизнес-логика ограничений
     */
    public function canUserCreateMasterProfile(int $userId): bool
    {
        // Проверяем количество существующих профилей
        $existingProfiles = $this->getUserMasterProfiles($userId);
        
        // Лимит профилей мастера (например, максимум 3)
        return $existingProfiles->count() < 3;
    }
}