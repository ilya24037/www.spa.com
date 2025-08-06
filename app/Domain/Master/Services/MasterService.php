<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Services\MasterProfileService;
use App\Domain\Master\Services\MasterModerationService;
use App\Domain\Master\Services\MasterSearchService;
use App\Domain\Master\Services\MasterFullProfileService;
use App\Domain\Master\Services\MasterHelperService;
use App\Infrastructure\Notification\NotificationService;
use App\Domain\Master\DTOs\CreateMasterDTO;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Domain\Master\DTOs\MasterFilterDTO;
use Illuminate\Support\Facades\DB;

/**
 * Сервис мастеров - координатор
 */
class MasterService
{
    public function __construct(
        private MasterRepository $repository,
        private MasterProfileService $profileService,
        private MasterModerationService $moderationService,
        private MasterSearchService $searchService,
        private MasterFullProfileService $fullProfileService,
        private MasterHelperService $helperService,
        private NotificationService $notificationService
    ) {}

    /**
     * Создать профиль мастера
     */
    public function createProfile(CreateMasterDTO $dto): MasterProfile
    {
        return DB::transaction(function () use ($dto) {
            $profile = $this->profileService->createProfile($dto);
            $this->helperService->updateUserRole($profile->user_id);
            $this->notificationService->sendMasterProfileCreated($profile);
            return $profile;
        });
    }

    /**
     * Обновить профиль мастера
     */
    public function updateProfile(int $masterId, UpdateMasterDTO $dto): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        return DB::transaction(function () use ($profile, $dto) {
            $profile = $this->profileService->updateProfile($profile, $dto);
            $this->repository->updateLevel($profile);
            $this->helperService->clearCache($profile);
            return $profile->fresh();
        });
    }

    /**
     * Активировать профиль мастера
     */
    public function activateProfile(int $masterId): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        if (!$this->helperService->canActivateProfile($profile)) {
            throw new \Exception('Профиль не готов к активации. Заполните все обязательные поля.');
        }

        $profile = $this->profileService->activateProfile($profile);
        $this->notificationService->sendProfileForModeration($profile);

        return $profile;
    }

    /**
     * Одобрить профиль
     */
    public function approveProfile(int $masterId, User $moderator): MasterProfile
    {
        return $this->moderationService->approveProfile($masterId, $moderator);
    }

    /**
     * Отклонить профиль
     */
    public function rejectProfile(int $masterId, string $reason, User $moderator): MasterProfile
    {
        return $this->moderationService->rejectProfile($masterId, $reason, $moderator);
    }

    /**
     * Деактивировать профиль
     */
    public function deactivateProfile(int $masterId, ?string $reason = null): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        $profile = $this->profileService->deactivateProfile($profile, $reason);
        $this->helperService->cancelActiveBookings($profile);

        return $profile;
    }

    /**
     * Заблокировать профиль
     */
    public function blockProfile(int $masterId, string $reason, User $admin): MasterProfile
    {
        return $this->moderationService->blockProfile($masterId, $reason, $admin);
    }

    /**
     * Поиск мастеров
     */
    public function search(MasterFilterDTO $filters)
    {
        return $this->searchService->search($filters);
    }

    /**
     * Получить профиль по slug
     */
    public function getBySlug(string $slug): ?MasterProfile
    {
        return $this->searchService->getBySlug($slug);
    }

    /**
     * Получить топ мастеров
     */
    public function getTopMasters(int $limit = 10)
    {
        return $this->searchService->getTopMasters($limit);
    }

    /**
     * Получить новых мастеров
     */
    public function getNewMasters(int $limit = 10)
    {
        return $this->searchService->getNewMasters($limit);
    }

    /**
     * Получить статистику мастера
     */
    public function getMasterStats(int $masterId): array
    {
        return $this->searchService->getMasterStats($masterId);
    }

    /**
     * Создать полный профиль
     */
    public function createFullProfile(array $data): MasterProfile
    {
        return $this->fullProfileService->createFullProfile($data);
    }

    /**
     * Обновить рейтинг мастера
     */
    public function updateRating(MasterProfile $master): bool
    {
        return $this->searchService->updateRating($master);
    }

    /**
     * Найти мастера с отношениями
     */
    public function findWithRelations(int $id)
    {
        return $this->searchService->findWithRelations($id);
    }
}