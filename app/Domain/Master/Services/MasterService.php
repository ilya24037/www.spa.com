<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\User;
use App\Enums\MasterStatus;
use App\Enums\MasterLevel;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Media\Services\MasterMediaService;
use App\Infrastructure\Notification\NotificationService;
use App\Domain\Master\DTOs\CreateMasterDTO;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Domain\Master\DTOs\MasterFilterDTO;
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
        private MasterMediaService $mediaService,
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
    public function deactivateProfile(int $masterId, ?string $reason = null): MasterProfile
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
    public function setVacation(int $masterId, \DateTime $until, ?string $message = null): MasterProfile
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
        $user = $this->userRepository->find($userId);
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
     * Создать полный профиль мастера с услугами и фотографиями (для AddItemController)
     */
    public function createFullProfile(array $data): MasterProfile
    {
        return DB::transaction(function () use ($data) {
            // Создаем основной профиль
            $profile = $data['user']->masterProfiles()->create([
                'display_name' => $data['display_name'],
                'slug' => $this->generateUniqueSlug($data['display_name']),
                'description' => $data['description'],
                'age' => $data['age'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'city' => $data['city'],
                'district' => $data['district'] ?? null,
                'address' => $data['address'] ?? null,
                'salon_name' => $data['salon_name'] ?? null,
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'telegram' => $data['telegram'] ?? null,
                'price_from' => $data['price_from'],
                'price_to' => $data['price_to'] ?? null,
                'show_phone' => $data['show_phone'] ?? false,
                'category_type' => $data['category_type'] ?? 'massage',
                'is_adult_content' => $data['is_adult_content'] ?? false,
                'status' => 'active',
                'is_active' => true,
            ]);

            // Добавляем услуги
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    $serviceData = [
                        'name' => $service['name'],
                        'price' => $service['price'],
                        'duration_minutes' => $service['duration'],
                        'description' => $service['description'] ?? null,
                        'adult_content' => $data['is_adult_content'] ?? false,
                    ];

                    // Для массажа используем massage_category_id
                    if ($data['category_type'] === 'massage' && isset($service['category_id'])) {
                        $serviceData['massage_category_id'] = $service['category_id'];
                    } else {
                        // Для эротических услуг используем простой category_id
                        $serviceData['category_id'] = $service['category_id'] ?? null;
                    }

                    $profile->services()->create($serviceData);
                }
            }

            // Добавляем зоны работы
            if (!empty($data['work_zones'])) {
                foreach ($data['work_zones'] as $zone) {
                    $profile->workZones()->create(['name' => $zone]);
                }
            }

            // Загружаем фотографии
            if (!empty($data['photos'])) {
                foreach ($data['photos'] as $index => $photo) {
                    $path = $photo->store('masters/photos', 'public');
                    $profile->photos()->create([
                        'path' => $path,
                        'is_main' => $index === 0,
                    ]);
                }
            }

            // Обновляем роль пользователя
            $this->updateUserRole($data['user']->id);

            return $profile;
        });
    }

    /**
     * Автоматические задачи
     */
    public function runDailyTasks(): void
    {
        // Завершаем отпуска
        $this->repository->finishVacations();

        // Деактивируем неактивных мастеров
        $this->repository->deactivateInactiveMasters();

        // Обновляем уровни мастеров
        $this->repository->updateAllMasterLevels();
    }

    /**
     * Получить доступные города
     */
    public function getAvailableCities(): array
    {
        return ['Москва', 'Санкт-Петербург', 'Екатеринбург', 'Казань', 'Новосибирск', 'Нижний Новгород'];
    }

    /**
     * Найти мастера с отношениями
     */
    public function findWithRelations(int $id)
    {
        return $this->repository->findWithRelations($id, [
            'user',
            'services.category',
            'photos',  
            'reviews.user',
            'workZones',
            'schedules'
        ]);
    }

    /**
     * Проверить валидность slug
     */
    public function isValidSlug($profile, string $slug): bool
    {
        return $profile->slug === $slug;
    }

    /**
     * Обеспечить наличие meta тегов
     */
    public function ensureMetaTags($profile): void
    {
        if (empty($profile->meta_title) || empty($profile->meta_description)) {
            $profile->generateMetaTags()->save();
        }
    }

    /**
     * Увеличить счетчик просмотров
     */
    public function incrementViews($profile): void
    {
        $profile->increment('views_count');
    }

    /**
     * Найти мастера по имени
     */
    public function findByDisplayName(string $displayName)
    {
        return $this->repository->findByDisplayName($displayName);
    }

    /**
     * Обновить статус мастера
     */
    public function updateMasterStatus($profile, array $data): bool
    {
        return $profile->update($data);
    }
}