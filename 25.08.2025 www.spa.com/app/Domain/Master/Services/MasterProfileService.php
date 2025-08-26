<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\DTOs\CreateMasterDTO;
use App\Domain\Master\DTOs\UpdateMasterDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для управления профилем мастера
 */
class MasterProfileService
{
    protected MasterRepository $repository;

    public function __construct(MasterRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Создать профиль мастера
     */
    public function createProfile(CreateMasterDTO $dto): MasterProfile
    {
        return DB::transaction(function () use ($dto) {
            $profileData = [
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
                'address' => $dto->address,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'specialties' => $dto->specialties ?? [],
                'services' => $dto->services ?? [],
                'working_hours' => $dto->working_hours ?? [],
                'price_range' => $dto->price_range ?? [],
                'education' => $dto->education ?? [],
                'certificates' => $dto->certificates ?? [],
                'is_active' => true,
                'status' => 'pending'
            ];

            $profile = $this->repository->create($profileData);

            Log::info('Master profile created', [
                'master_id' => $profile->id,
                'user_id' => $dto->user_id,
                'display_name' => $dto->display_name
            ]);

            return $profile;
        });
    }

    /**
     * Обновить профиль мастера
     */
    public function updateProfile(MasterProfile $profile, UpdateMasterDTO $dto): MasterProfile
    {
        return DB::transaction(function () use ($profile, $dto) {
            $updateData = array_filter([
                'display_name' => $dto->display_name,
                'bio' => $dto->bio,
                'phone' => $dto->phone,
                'whatsapp' => $dto->whatsapp,
                'telegram' => $dto->telegram,
                'experience_years' => $dto->experience_years,
                'city' => $dto->city,
                'district' => $dto->district,
                'metro_station' => $dto->metro_station,
                'address' => $dto->address,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'specialties' => $dto->specialties,
                'services' => $dto->services,
                'working_hours' => $dto->working_hours,
                'price_range' => $dto->price_range,
                'education' => $dto->education,
                'certificates' => $dto->certificates
            ], fn($value) => $value !== null);

            // Обновляем slug если изменилось имя
            if (isset($dto->display_name) && $dto->display_name !== $profile->display_name) {
                $updateData['slug'] = $this->generateUniqueSlug($dto->display_name, $profile->id);
            }

            $profile = $this->repository->update($profile, $updateData);

            Log::info('Master profile updated', [
                'master_id' => $profile->id,
                'updated_fields' => array_keys($updateData)
            ]);

            return $profile;
        });
    }

    /**
     * Получить профиль мастера
     */
    public function getProfile(int $id): ?MasterProfile
    {
        return $this->repository->findById($id);
    }

    /**
     * Получить профиль по слагу
     */
    public function getProfileBySlug(string $slug): ?MasterProfile
    {
        return $this->repository->findBySlug($slug);
    }

    /**
     * Получить профиль по пользователю
     */
    public function getProfileByUser(User $user): ?MasterProfile
    {
        return $this->repository->findByUserId($user->id);
    }

    /**
     * Активировать профиль
     */
    public function activateProfile(MasterProfile $profile): MasterProfile
    {
        $profile = $this->repository->update($profile, [
            'is_active' => true,
            'status' => 'active',
            'activated_at' => now()
        ]);

        Log::info('Master profile activated', ['master_id' => $profile->id]);

        return $profile;
    }

    /**
     * Деактивировать профиль
     */
    public function deactivateProfile(MasterProfile $profile, string $reason = null): MasterProfile
    {
        $profile = $this->repository->update($profile, [
            'is_active' => false,
            'status' => 'inactive',
            'deactivated_at' => now(),
            'deactivation_reason' => $reason
        ]);

        Log::info('Master profile deactivated', [
            'master_id' => $profile->id,
            'reason' => $reason
        ]);

        return $profile;
    }

    /**
     * Верифицировать мастера
     */
    public function verifyProfile(MasterProfile $profile, string $level = 'basic'): MasterProfile
    {
        $profile = $this->repository->update($profile, [
            'is_verified' => true,
            'verification_level' => $level,
            'verified_at' => now()
        ]);

        Log::info('Master profile verified', [
            'master_id' => $profile->id,
            'level' => $level
        ]);

        return $profile;
    }

    /**
     * Отозвать верификацию
     */
    public function revokeVerification(MasterProfile $profile, string $reason = null): MasterProfile
    {
        $profile = $this->repository->update($profile, [
            'is_verified' => false,
            'verification_level' => null,
            'verified_at' => null,
            'verification_revoked_at' => now(),
            'verification_revoke_reason' => $reason
        ]);

        Log::info('Master verification revoked', [
            'master_id' => $profile->id,
            'reason' => $reason
        ]);

        return $profile;
    }

    /**
     * Обновить рейтинг мастера
     */
    public function updateRating(MasterProfile $profile, float $rating, int $reviewsCount): MasterProfile
    {
        $profile = $this->repository->update($profile, [
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'rating_updated_at' => now()
        ]);

        Log::info('Master rating updated', [
            'master_id' => $profile->id,
            'rating' => $rating,
            'reviews_count' => $reviewsCount
        ]);

        return $profile;
    }

    /**
     * Обновить статистику просмотров
     */
    public function incrementViews(MasterProfile $profile): MasterProfile
    {
        return $this->repository->update($profile, [
            'views_count' => $profile->views_count + 1,
            'last_viewed_at' => now()
        ]);
    }

    /**
     * Обновить время последней активности
     */
    public function updateLastActivity(MasterProfile $profile): MasterProfile
    {
        return $this->repository->update($profile, [
            'last_active_at' => now()
        ]);
    }

    /**
     * Удалить профиль (мягкое удаление)
     */
    public function deleteProfile(MasterProfile $profile): bool
    {
        $result = $this->repository->delete($profile);

        if ($result) {
            Log::info('Master profile deleted', ['master_id' => $profile->id]);
        }

        return $result;
    }

    /**
     * Восстановить удаленный профиль
     */
    public function restoreProfile(int $profileId): ?MasterProfile
    {
        $profile = $this->repository->restore($profileId);

        if ($profile) {
            Log::info('Master profile restored', ['master_id' => $profile->id]);
        }

        return $profile;
    }

    /**
     * Сгенерировать уникальный slug
     */
    protected function generateUniqueSlug(string $name, int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Проверить существование slug
     */
    protected function slugExists(string $slug, int $excludeId = null): bool
    {
        $query = $this->repository->newQuery()->where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Получить статистику профиля
     */
    public function getProfileStats(MasterProfile $profile): array
    {
        return [
            'views_count' => $profile->views_count ?? 0,
            'rating' => $profile->rating ?? 0,
            'reviews_count' => $profile->reviews_count ?? 0,
            'bookings_count' => $profile->bookings()->count(),
            'completed_bookings' => $profile->bookings()->where('status', 'completed')->count(),
            'profile_completeness' => $this->calculateProfileCompleteness($profile),
            'is_active' => $profile->is_active,
            'is_verified' => $profile->is_verified,
            'verification_level' => $profile->verification_level,
            'created_at' => $profile->created_at,
            'last_active_at' => $profile->last_active_at
        ];
    }

    /**
     * Рассчитать полноту профиля
     */
    protected function calculateProfileCompleteness(MasterProfile $profile): int
    {
        $fields = [
            'display_name' => 10,
            'bio' => 15,
            'phone' => 10,
            'city' => 5,
            'address' => 5,
            'specialties' => 15,
            'services' => 20,
            'working_hours' => 10,
            'education' => 5,
            'certificates' => 5
        ];

        $score = 0;
        foreach ($fields as $field => $weight) {
            if (!empty($profile->$field)) {
                $score += $weight;
            }
        }

        return min($score, 100);
    }
}