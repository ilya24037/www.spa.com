<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserProfile;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Services\UserAuthService;
use App\Domain\User\Services\UserProfileService;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Основной сервис пользователей - только CRUD операции
 * Остальная логика вынесена в UserAuthService и UserProfileService
 */
class UserService
{
    protected UserRepository $userRepository;
    protected UserAuthService $authService;
    protected UserProfileService $profileService;

    public function __construct(
        UserRepository $userRepository,
        UserAuthService $authService,
        UserProfileService $profileService
    ) {
        $this->userRepository = $userRepository;
        $this->authService = $authService;
        $this->profileService = $profileService;
    }

    /**
     * Создать пользователя из DTO
     */
    public function createFromDTO(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $userData = [
                'name' => $dto->name,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'password' => Hash::make($dto->password),
                'role' => $dto->role ?? 'client',
                'is_active' => $dto->is_active ?? true
            ];

            $user = $this->userRepository->create($userData);

            // Создать профиль через отдельный сервис
            if (!empty($dto->profile_data)) {
                $this->profileService->createProfile($user, $dto->profile_data);
            }

            Log::info('User created', ['user_id' => $user->id, 'email' => $user->email]);
            
            return $user;
        });
    }

    /**
     * Обновить пользователя
     */
    public function update(User $user, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto) {
            $userData = array_filter([
                'name' => $dto->name,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'is_active' => $dto->is_active
            ], fn($value) => $value !== null);

            if ($dto->password) {
                $userData['password'] = Hash::make($dto->password);
            }

            $user = $this->userRepository->update($user, $userData);

            // Обновить профиль через отдельный сервис
            if (!empty($dto->profile_data)) {
                $this->profileService->updateProfile($user, $dto->profile_data);
            }

            Log::info('User updated', ['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Получить пользователя по ID
     */
    public function findById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Получить пользователя по email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Получить пользователя по телефону
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->userRepository->findByPhone($phone);
    }

    /**
     * Найти пользователей
     */
    public function search(array $criteria, int $limit = 50): Collection
    {
        return $this->userRepository->search($criteria, $limit);
    }

    /**
     * Деактивировать пользователя
     */
    public function deactivate(User $user, string $reason = null): User
    {
        $user = $this->userRepository->update($user, [
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason
        ]);

        Log::info('User deactivated', [
            'user_id' => $user->id,
            'reason' => $reason
        ]);

        return $user;
    }

    /**
     * Активировать пользователя
     */
    public function activate(User $user): User
    {
        $user = $this->userRepository->update($user, [
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null
        ]);

        Log::info('User activated', ['user_id' => $user->id]);

        return $user;
    }

    /**
     * Удалить пользователя (мягкое удаление)
     */
    public function delete(User $user): bool
    {
        $result = $this->userRepository->delete($user);

        if ($result) {
            Log::info('User deleted', ['user_id' => $user->id]);
        }

        return $result;
    }

    /**
     * Восстановить пользователя
     */
    public function restore(int $userId): ?User
    {
        $user = $this->userRepository->restore($userId);

        if ($user) {
            Log::info('User restored', ['user_id' => $user->id]);
        }

        return $user;
    }

    /**
     * Получить активных пользователей
     */
    public function getActive(int $limit = 100): Collection
    {
        return $this->userRepository->getActive($limit);
    }

    /**
     * Получить пользователей по роли
     */
    public function getByRole(string $role, int $limit = 100): Collection
    {
        return $this->userRepository->getByRole($role, $limit);
    }

    /**
     * Сменить пароль
     */
    public function changePassword(User $user, string $newPassword): User
    {
        return $this->authService->changePassword($user, $newPassword);
    }

    /**
     * Сменить email
     */
    public function changeEmail(User $user, string $newEmail): User
    {
        return $this->authService->changeEmail($user, $newEmail);
    }

    /**
     * Верифицировать email
     */
    public function verifyEmail(User $user): User
    {
        return $this->authService->verifyEmail($user);
    }

    /**
     * Отправить код подтверждения
     */
    public function sendVerificationCode(User $user, string $type = 'email'): bool
    {
        return $this->authService->sendVerificationCode($user, $type);
    }

    /**
     * Проверить код подтверждения
     */
    public function verifyCode(User $user, string $code, string $type = 'email'): bool
    {
        return $this->authService->verifyCode($user, $code, $type);
    }

    /**
     * Получить профиль пользователя
     */
    public function getProfile(User $user): ?UserProfile
    {
        return $this->profileService->getProfile($user);
    }

    /**
     * Обновить настройки уведомлений
     */
    public function updateNotificationSettings(User $user, array $settings): User
    {
        return $this->profileService->updateNotificationSettings($user, $settings);
    }

    /**
     * Обновить настройки приватности
     */
    public function updatePrivacySettings(User $user, array $settings): User
    {
        return $this->profileService->updatePrivacySettings($user, $settings);
    }

    /**
     * Получить статистику пользователей
     */
    public function getStats(): array
    {
        return [
            'total' => $this->userRepository->getTotalCount(),
            'active' => $this->userRepository->getActiveCount(),
            'clients' => $this->userRepository->getCountByRole('client'),
            'masters' => $this->userRepository->getCountByRole('master'),
            'admins' => $this->userRepository->getCountByRole('admin'),
            'verified' => $this->userRepository->getVerifiedCount(),
            'registered_today' => $this->userRepository->getRegisteredTodayCount(),
            'registered_this_month' => $this->userRepository->getRegisteredThisMonthCount()
        ];
    }

    /**
     * Массовое обновление пользователей
     */
    public function bulkUpdate(array $userIds, array $data): int
    {
        $count = $this->userRepository->bulkUpdate($userIds, $data);

        Log::info('Bulk user update completed', [
            'updated_count' => $count,
            'user_ids' => $userIds,
            'data' => array_keys($data)
        ]);

        return $count;
    }

    /**
     * Экспорт пользователей
     */
    public function export(array $criteria = []): Collection
    {
        return $this->userRepository->export($criteria);
    }
}