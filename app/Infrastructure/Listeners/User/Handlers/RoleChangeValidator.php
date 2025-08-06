<?php

namespace App\Infrastructure\Listeners\User\Handlers;

use Exception;

/**
 * Валидатор изменения ролей пользователей
 */
class RoleChangeValidator
{
    /**
     * Валидировать смену роли
     */
    public function validateRoleChange($user, string $oldRole, string $newRole): void
    {
        // Проверяем, что роль действительно изменилась
        if ($user->role->value === $newRole) {
            throw new Exception("Роль уже установлена в {$newRole}");
        }

        // Проверяем допустимые переходы ролей
        $allowedTransitions = $this->getAllowedRoleTransitions();
        
        if (!isset($allowedTransitions[$oldRole]) || 
            !in_array($newRole, $allowedTransitions[$oldRole])) {
            throw new Exception("Недопустимый переход роли с {$oldRole} на {$newRole}");
        }

        // Проверяем специальные условия для роли мастера
        if ($newRole === 'master') {
            $this->validateMasterRoleRequirements($user);
        }
    }

    /**
     * Получить допустимые переходы ролей
     */
    public function getAllowedRoleTransitions(): array
    {
        return [
            'client' => ['master', 'admin'], // Клиент может стать мастером или админом
            'master' => ['client', 'admin'], // Мастер может вернуться к клиенту или стать админом
            'admin' => ['client', 'master'], // Админ может стать кем угодно
            'moderator' => ['client', 'master', 'admin'], // Модератор может стать кем угодно
            'support' => ['client'], // Поддержка может стать только клиентом
        ];
    }

    /**
     * Валидировать требования для роли мастера
     */
    public function validateMasterRoleRequirements($user): void
    {
        $profile = $user->getProfile();
        
        // Проверяем обязательные поля для мастера
        if (!$profile) {
            throw new Exception("Для назначения роли мастера необходимо заполнить профиль");
        }

        if (empty($profile->phone)) {
            throw new Exception("Для роли мастера необходимо указать номер телефона");
        }

        if (empty($profile->name)) {
            throw new Exception("Для роли мастера необходимо указать имя");
        }

        // Проверяем подтверждение email
        if (!$user->hasVerifiedEmail()) {
            throw new Exception("Для роли мастера необходимо подтвердить email");
        }

        // Проверяем возраст (должен быть 18+)
        if ($profile->birth_date) {
            $age = now()->diffInYears($profile->birth_date);
            if ($age < 18) {
                throw new Exception("Для роли мастера необходимо быть старше 18 лет");
            }
        }
    }

    /**
     * Проверить доступность роли для пользователя
     */
    public function isRoleAvailable(string $role, $user): bool
    {
        $availableRoles = ['client', 'master', 'admin', 'moderator', 'support'];
        
        if (!in_array($role, $availableRoles)) {
            return false;
        }

        // Дополнительные проверки для специальных ролей
        if ($role === 'master') {
            try {
                $this->validateMasterRoleRequirements($user);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить список недостающих требований для роли
     */
    public function getMissingRequirements(string $role, $user): array
    {
        $missing = [];

        if ($role !== 'master') {
            return $missing;
        }

        $profile = $user->getProfile();
        
        if (!$profile) {
            $missing[] = 'Необходимо заполнить профиль';
            return $missing;
        }

        if (empty($profile->phone)) {
            $missing[] = 'Необходимо указать номер телефона';
        }

        if (empty($profile->name)) {
            $missing[] = 'Необходимо указать имя';
        }

        if (!$user->hasVerifiedEmail()) {
            $missing[] = 'Необходимо подтвердить email';
        }

        if ($profile->birth_date) {
            $age = now()->diffInYears($profile->birth_date);
            if ($age < 18) {
                $missing[] = 'Необходимо быть старше 18 лет';
            }
        } elseif (!$profile->birth_date) {
            $missing[] = 'Необходимо указать дату рождения';
        }

        return $missing;
    }
}