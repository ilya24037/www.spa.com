<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Log;

/**
 * Action для верификации email пользователя
 */
class VerifyEmailAction
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Подтвердить email пользователя
     */
    public function execute(int $userId): array
    {
        try {
            $user = $this->userRepository->findById($userId);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Пользователь не найден',
                ];
            }

            // Проверяем, не подтвержден ли уже email
            if ($user->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Email уже подтвержден',
                ];
            }

            // Подтверждаем email
            $user->email_verified_at = now();
            
            // Если пользователь в статусе ожидания, активируем его
            if ($user->status === UserStatus::PENDING) {
                $user->status = UserStatus::ACTIVE;
            }
            
            $user->save();

            Log::info('Email verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'message' => 'Email успешно подтвержден',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to verify email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при подтверждении email',
            ];
        }
    }
}