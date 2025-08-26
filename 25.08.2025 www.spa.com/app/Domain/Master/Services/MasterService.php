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
use App\Domain\Master\Contracts\MasterServiceInterface;
use App\Infrastructure\Notification\NotificationService;
use App\Domain\Master\DTOs\CreateMasterDTO;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Domain\Master\DTOs\MasterFilterDTO;
use Illuminate\Support\Facades\DB;

/**
 * Сервис мастеров - координатор
 */
class MasterService implements MasterServiceInterface
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
     * Проверить валидность slug для SEO-URL
     */
    public function isValidSlug(MasterProfile $profile, string $slug): bool
    {
        return $profile->slug === $slug;
    }

    /**
     * Обновить мета-теги профиля
     */
    public function ensureMetaTags(MasterProfile $profile): void
    {
        // Если мета-теги не установлены, устанавливаем по умолчанию
        if (empty($profile->meta_title)) {
            $profile->update([
                'meta_title' => $profile->display_name . ' - Массаж в ' . ($profile->city ?? 'Москве'),
                'meta_description' => 'Профессиональный массаж от ' . $profile->display_name . '. Запись на массаж онлайн.'
            ]);
        }
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews(MasterProfile $profile): void
    {
        $profile->increment('views_count');
    }

    /**
     * Получить похожих мастеров
     */
    public function getSimilarMasters(int $masterId, ?string $city, int $limit = 5): array
    {
        return $this->searchService->getSimilarMasters($masterId, $city, $limit);
    }

    /**
     * Проверить находится ли мастер в избранном пользователя
     */
    public function isFavorite(int $masterId, int $userId): bool
    {
        return \DB::table('user_favorites')
            ->where('user_id', $userId)
            ->where('favoritable_type', 'master')
            ->where('favoritable_id', $masterId)
            ->exists();
    }

}