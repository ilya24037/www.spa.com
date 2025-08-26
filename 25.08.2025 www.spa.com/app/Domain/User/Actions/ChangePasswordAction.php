<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;
use App\Domain\User\Services\UserService;
use Illuminate\Support\Facades\Log;

/**
 * Action для изменения пароля пользователя
 */
class ChangePasswordAction
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Изменить пароль пользователя
     */
    public function execute(User $user, string $currentPassword, string $newPassword): array
    {
        try {
            // Валидация нового пароля
            if (strlen($newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'Новый пароль должен содержать не менее 8 символов',
                ];
            }

            // Меняем пароль через сервис
            $result = $this->userService->changePassword($user, $currentPassword, $newPassword);

            if ($result['success']) {
                // Сбрасываем все токены пользователя для безопасности
                $user->tokens()->delete();

                Log::info('Password changed successfully', [
                    'user_id' => $user->id,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to change password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при изменении пароля',
            ];
        }
    }
}