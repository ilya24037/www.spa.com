<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\Master\Repositories\MasterRepository;

/**
 * Вспомогательный сервис для работы с мастерами
 */
class MasterHelperService
{
    public function __construct(
        private UserRepository $userRepository,
        private MasterRepository $repository
    ) {}

    /**
     * Проверить готовность профиля к активации
     */
    public function canActivateProfile(MasterProfile $profile): bool
    {
        $required = [
            'display_name', 'bio', 'phone', 'city', 
            'experience_years', 'services',
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
     * Обновить роль пользователя
     */
    public function updateUserRole(int $userId): void
    {
        $user = $this->userRepository->find($userId);
        if ($user && !$user->hasRole('master')) {
            $user->assignRole('master');
        }
    }

    /**
     * Отменить активные бронирования
     */
    public function cancelActiveBookings(MasterProfile $profile): void
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
    public function clearCache(MasterProfile $profile): void
    {
        // Используем методы инвалидации кеша из декоратора
        if (method_exists($this->repository, 'invalidateCache')) {
            $this->repository->invalidateCache($profile->id);
            $this->repository->invalidateAllCache();
        }
    }
}