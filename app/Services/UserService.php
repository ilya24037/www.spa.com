<?php

namespace App\Services;

use App\Domain\User\Services\UserService as DomainUserService;
use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Legacy-адаптер для UserService
 * @deprecated Используйте App\Domain\User\Services\UserService
 */
class UserService
{
    private DomainUserService $domainService;

    public function __construct(DomainUserService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * @deprecated Используйте DomainUserService::create()
     */
    public function create(array $data): User
    {
        Log::info('Using legacy UserService::create - consider migrating to Domain service');
        return $this->domainService->create($data);
    }

    /**
     * @deprecated Используйте DomainUserService::updateProfile()
     */
    public function updateProfile(User $user, array $profileData): bool
    {
        Log::info('Using legacy UserService::updateProfile - consider migrating to Domain service');
        return $this->domainService->updateProfile($user, $profileData);
    }

    /**
     * @deprecated Используйте DomainUserService::updateSettings()
     */
    public function updateSettings(User $user, array $settingsData): bool
    {
        Log::info('Using legacy UserService::updateSettings - consider migrating to Domain service');
        return $this->domainService->updateSettings($user, $settingsData);
    }

    /**
     * @deprecated Используйте DomainUserService::changePassword()
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        Log::info('Using legacy UserService::changePassword - consider migrating to Domain service');
        return $this->domainService->changePassword($user, $currentPassword, $newPassword);
    }

    /**
     * @deprecated Используйте DomainUserService::uploadAvatar()
     */
    public function uploadAvatar(User $user, UploadedFile $file): array
    {
        Log::info('Using legacy UserService::uploadAvatar - consider migrating to Domain service');
        return $this->domainService->uploadAvatar($user, $file);
    }

    /**
     * @deprecated Используйте DomainUserService::deleteAvatar()
     */
    public function deleteAvatar(User $user): bool
    {
        Log::info('Using legacy UserService::deleteAvatar - consider migrating to Domain service');
        return $this->domainService->deleteAvatar($user);
    }

    /**
     * @deprecated Используйте DomainUserService::activate()
     */
    public function activate(User $user): bool
    {
        Log::info('Using legacy UserService::activate - consider migrating to Domain service');
        return $this->domainService->activate($user);
    }

    /**
     * @deprecated Используйте DomainUserService::deactivate()
     */
    public function deactivate(User $user): bool
    {
        Log::info('Using legacy UserService::deactivate - consider migrating to Domain service');
        return $this->domainService->deactivate($user);
    }

    /**
     * @deprecated Используйте DomainUserService::suspend()
     */
    public function suspend(User $user, string $reason = ''): bool
    {
        Log::info('Using legacy UserService::suspend - consider migrating to Domain service');
        return $this->domainService->suspend($user, $reason);
    }

    /**
     * @deprecated Используйте DomainUserService::ban()
     */
    public function ban(User $user, string $reason = ''): bool
    {
        Log::info('Using legacy UserService::ban - consider migrating to Domain service');
        return $this->domainService->ban($user, $reason);
    }

    /**
     * @deprecated Используйте DomainUserService::changeRole()
     */
    public function changeRole(User $user, UserRole $newRole): bool
    {
        Log::info('Using legacy UserService::changeRole - consider migrating to Domain service');
        return $this->domainService->changeRole($user, $newRole);
    }

    /**
     * @deprecated Используйте DomainUserService::getUserStats()
     */
    public function getUserStats(User $user): array
    {
        Log::info('Using legacy UserService::getUserStats - consider migrating to Domain service');
        return $this->domainService->getUserStats($user);
    }

    /**
     * @deprecated Используйте DomainUserService::exportUserData()
     */
    public function exportUserData(User $user): array
    {
        Log::info('Using legacy UserService::exportUserData - consider migrating to Domain service');
        return $this->domainService->exportUserData($user);
    }

    /**
     * @deprecated Используйте DomainUserService::deleteUserData()
     */
    public function deleteUserData(User $user): bool
    {
        Log::info('Using legacy UserService::deleteUserData - consider migrating to Domain service');
        return $this->domainService->deleteUserData($user);
    }

    /**
     * Proxy для всех других методов
     */
    public function __call($method, $arguments)
    {
        Log::info("Using legacy UserService::{$method} - consider migrating to Domain service");
        return $this->domainService->$method(...$arguments);
    }
}