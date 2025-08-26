<?php

namespace App\Domain\User\Auth;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Services\UserService;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

/**
 * Обработчик регистрации и верификации пользователей
 */
class RegistrationHandler
{
    private UserRepository $userRepository;
    private UserService $userService;

    public function __construct(UserRepository $userRepository, UserService $userService)
    {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    /**
     * Регистрация нового пользователя
     */
    public function register(array $data): array
    {
        try {
            // Проверяем существует ли пользователь с таким email
            if ($this->userRepository->findByEmail($data['email'])) {
                return [
                    'success' => false,
                    'error' => 'Пользователь с таким email уже существует'
                ];
            }

            // Создаем пользователя
            $user = $this->userService->create([
                'email' => $data['email'],
                'password' => $data['password'],
                'name' => $data['name'] ?? 'Пользователь',
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'] ?? UserRole::CLIENT,
            ]);

            // Отправляем событие регистрации (для отправки email верификации)
            event(new Registered($user));

            Log::info('User registered', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role->value,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Регистрация прошла успешно. Проверьте email для подтверждения аккаунта.'
            ];

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при регистрации'
            ];
        }
    }

    /**
     * Подтверждение email адреса
     */
    public function verifyEmail(User $user): array
    {
        try {
            if ($user->hasVerifiedEmail()) {
                return [
                    'success' => false,
                    'error' => 'Email уже подтвержден'
                ];
            }

            $user->markEmailAsVerified();
            
            // Активируем пользователя после подтверждения email
            if ($user->status === UserStatus::PENDING) {
                $this->userService->activate($user);
            }

            event(new Verified($user));

            Log::info('Email verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'message' => 'Email успешно подтвержден'
            ];

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при подтверждении email'
            ];
        }
    }
}