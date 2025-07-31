<?php

namespace App\Services;

use App\Models\MasterProfile;
use App\Models\User;
use App\Enums\MasterStatus;
use App\Enums\MasterLevel;
use App\Repositories\MasterRepository;
use App\Services\MediaService;
use App\Services\NotificationService;
use App\DTOs\CreateMasterDTO;
use App\DTOs\UpdateMasterDTO;
use App\DTOs\MasterFilterDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Сервис для работы с мастерами
 */
class MasterService
{
    public function __construct(
        private MasterRepository $repository,
        private MediaService $mediaService,
        private NotificationService $notificationService
    ) {}

    /**
     * Создать профиль мастера
     */
    public function createProfile(CreateMasterDTO $dto): MasterProfile
    {
        return DB::transaction(function () use ($dto) {
            // Создаем профиль
            $profile = $this->repository->create([
                'user_id' => $dto->user_id,
                'display_name' => $dto->display_name,
                'slug' => $this->generateUniqueSlug($dto->display_name),
                'bio' => $dto->bio,
                'phone' => $dto->phone,
                'whatsapp' => $dto->whatsapp,
                'telegram' => $dto->telegram,
                'experience_years' => $dto->experience_years,
                'city' => $dto->city,
                'district' => $dto->district,
                'metro_station' => $dto->metro_station,
                'home_service' => $dto->home_service,
                'salon_service' => $dto->salon_service,
                'salon_address' => $dto->salon_address,
                'status' => MasterStatus::DRAFT,
                'level' => MasterLevel::BEGINNER,
                'age' => $dto->age,
                'features' => $dto->features,
                'services' => $dto->services,
            ]);

            // Обрабатываем аватар
            if ($dto->avatar) {
                $avatar = $this->mediaService->uploadAvatar($dto->avatar, $profile);
                $profile->update(['avatar' => $avatar->path]);
            }

            // Обновляем роль пользователя
            $this->updateUserRole($profile->user_id);

            // Отправляем уведомление
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
            // Обновляем основные данные
            $updateData = $dto->toArray();
            
            // Обновляем slug если изменилось имя
            if ($dto->display_name && $dto->display_name !== $profile->display_name) {
                $updateData['slug'] = $this->generateUniqueSlug($dto->display_name);
            }

            $this->repository->update($profile, $updateData);

            // Обновляем аватар
            if ($dto->avatar) {
                $avatar = $this->mediaService->uploadAvatar($dto->avatar, $profile);
                $profile->update(['avatar' => $avatar->path]);
            }

            // Обновляем уровень
            $this->repository->updateLevel($profile);

            // Очищаем кеш
            $this->clearCache($profile);

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

        if (!$this->canActivateProfile($profile)) {
            throw new \Exception('Профиль не готов к активации. Заполните все обязательные поля.');
        }

        $profile->update(['status' => MasterStatus::PENDING]);

        // Отправляем на модерацию
        $this->notificationService->sendProfileForModeration($profile);

        return $profile;
    }

    /**
     * Одобрить профиль (для модератора)
     */
    public function approveProfile(int $masterId, User $moderator): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        if ($profile->status !== MasterStatus::PENDING) {
            throw new \Exception('Профиль не находится на модерации');
        }

        $profile->update([
            'status' => MasterStatus::ACTIVE,
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => $moderator->id,
        ]);

        // Отправляем уведомление мастеру
        $this->notificationService->sendProfileApproved($profile);

        return $profile;
    }

    /**
     * Отклонить профиль (для модератора)
     */
    public function rejectProfile(int $masterId, string $reason, User $moderator): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        if ($profile->status !== MasterStatus::PENDING) {
            throw new \Exception('Профиль не находится на модерации');
        }

        $profile->update([
            'status' => MasterStatus::DRAFT,
            'moderation_notes' => $reason,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);

        // Отправляем уведомление с причиной отклонения
        $this->notificationService->sendProfileRejected($profile, $reason);

        return $profile;
    }

    /**
     * Деактивировать профиль
     */
    public function deactivateProfile(int $masterId, string $reason = null): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        $profile->update([
            'status' => MasterStatus::INACTIVE,
            'deactivation_reason' => $reason,
            'deactivated_at' => now(),
        ]);

        // Отменяем все активные бронирования
        $this->cancelActiveBookings($profile);

        return $profile;
    }

    /**
     * Заблокировать профиль
     */
    public function blockProfile(int $masterId, string $reason, User $admin): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        $profile->update([
            'status' => MasterStatus::BLOCKED,
            'block_reason' => $reason,
            'blocked_by' => $admin->id,
            'blocked_at' => now(),
        ]);

        // Отменяем все активные бронирования
        $this->cancelActiveBookings($profile);

        // Отправляем уведомление
        $this->notificationService->sendProfileBlocked($profile, $reason);

        return $profile;
    }

    /**
     * Установить отпуск
     */
    public function setVacation(int $masterId, \DateTime $until, string $message = null): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        if (!$profile->status->canTransitionTo(MasterStatus::VACATION)) {
            throw new \Exception('Невозможно установить отпуск в текущем статусе');
        }

        $profile->update([
            'status' => MasterStatus::VACATION,
            'vacation_until' => $until,
            'vacation_message' => $message,
        ]);

        return $profile;
    }

    /**
     * Вернуться из отпуска
     */
    public function returnFromVacation(int $masterId): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        if ($profile->status !== MasterStatus::VACATION) {
            throw new \Exception('Мастер не находится в отпуске');
        }

        $profile->update([
            'status' => MasterStatus::ACTIVE,
            'vacation_until' => null,
            'vacation_message' => null,
        ]);

        return $profile;
    }

    /**
     * Поиск мастеров
     */
    public function search(MasterFilterDTO $filters)
    {
        // Кешируем результаты поиска
        $cacheKey = 'masters_search_' . md5(serialize($filters->toArray()));
        
        return Cache::remember($cacheKey, 300, function () use ($filters) { // 5 минут
            return $this->repository->search(
                $filters->toArray(),
                $filters->per_page ?? 20
            );
        });
    }

    /**
     * Получить профиль по slug
     */
    public function getBySlug(string $slug): ?MasterProfile
    {
        $profile = $this->repository->findBySlug($slug);
        
        if ($profile && $profile->status->isPubliclyVisible()) {
            // Увеличиваем счетчик просмотров
            $this->repository->incrementViews($profile);
            return $profile;
        }
        
        return null;
    }

    /**
     * Получить топ мастеров
     */
    public function getTopMasters(int $limit = 10)
    {
        return Cache::remember('top_masters', 3600, function () use ($limit) { // 1 час
            return $this->repository->getTopMasters($limit);
        });
    }

    /**
     * Получить новых мастеров
     */
    public function getNewMasters(int $limit = 10)
    {
        return Cache::remember('new_masters', 1800, function () use ($limit) { // 30 минут
            return $this->repository->getNewMasters($limit);
        });
    }

    /**
     * Получить статистику мастера
     */
    public function getMasterStats(int $masterId): array
    {
        return Cache::remember("master_stats_{$masterId}", 900, function () use ($masterId) { // 15 минут
            return $this->repository->getMasterStats($masterId);
        });
    }

    /**
     * Обновить премиум статус
     */
    public function updatePremiumStatus(int $masterId, \DateTime $until): MasterProfile
    {
        $profile = $this->repository->findById($masterId);
        
        if (!$profile) {
            throw new \Exception('Профиль мастера не найден');
        }

        $profile->update([
            'is_premium' => true,
            'premium_until' => $until,
        ]);

        $this->clearCache($profile);

        return $profile;
    }

    /**
     * Проверить готовность профиля к активации
     */
    protected function canActivateProfile(MasterProfile $profile): bool
    {
        $required = [
            'display_name',
            'bio',
            'phone',
            'city',
            'experience_years',
            'services',
        ];

        foreach ($required as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }

        // Проверяем наличие хотя бы одного типа услуг
        if (!$profile->home_service && !$profile->salon_service) {
            return false;
        }

        // Проверяем наличие фотографий
        if ($profile->photos()->count() < 1) {
            return false;
        }

        return true;
    }

    /**
     * Генерация уникального slug
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = 1;
        
        while ($this->repository->findBySlug($slug)) {
            $slug = Str::slug($name) . '-' . $count;
            $count++;
        }
        
        return $slug;
    }

    /**
     * Обновить роль пользователя
     */
    protected function updateUserRole(int $userId): void
    {
        $user = User::find($userId);
        if ($user && !$user->hasRole('master')) {
            $user->assignRole('master');
        }
    }

    /**
     * Отменить активные бронирования
     */
    protected function cancelActiveBookings(MasterProfile $profile): void
    {
        $activeBookings = $profile->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->get();

        foreach ($activeBookings as $booking) {
            $booking->cancel('Мастер временно недоступен');
        }
    }

    /**
     * Очистить кеш
     */
    protected function clearCache(MasterProfile $profile): void
    {
        Cache::forget("master_stats_{$profile->id}");
        Cache::forget('top_masters');
        Cache::forget('new_masters');
    }

    /**
     * Автоматические задачи
     */
    public function runDailyTasks(): void
    {
        // Завершаем отпуска
        MasterProfile::where('status', MasterStatus::VACATION)
            ->where('vacation_until', '<=', now())
            ->update(['status' => MasterStatus::ACTIVE]);

        // Деактивируем неактивных мастеров
        $this->repository->deactivateInactiveMasters();

        // Обновляем уровни мастеров
        MasterProfile::where('status', MasterStatus::ACTIVE)
            ->chunk(100, function ($masters) {
                foreach ($masters as $master) {
                    $this->repository->updateLevel($master);
                }
            });
    }
}