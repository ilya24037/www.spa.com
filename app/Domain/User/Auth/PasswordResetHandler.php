<?php

namespace App\Domain\User\Auth;

use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Обработчик восстановления пароля
 */
class PasswordResetHandler
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Отправка ссылки для сброса пароля
     */
    public function sendPasswordResetLink(string $email): array
    {
        try {
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                // Не раскрываем информацию о существовании пользователя
                return [
                    'success' => true,
                    'message' => 'Если пользователь с таким email существует, на него отправлена ссылка для сброса пароля'
                ];
            }

            // Генерируем токен для сброса пароля
            $token = Str::random(60);
            
            // Сохраняем токен в базе (нужна таблица password_resets)
            \DB::table('password_resets')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Отправляем email с токеном
            // event(new PasswordResetRequested($user, $token));

            Log::info('Password reset requested', [
                'email' => $email,
            ]);

            return [
                'success' => true,
                'message' => 'Ссылка для сброса пароля отправлена на email'
            ];

        } catch (\Exception $e) {
            Log::error('Password reset request failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при отправке ссылки для сброса пароля'
            ];
        }
    }

    /**
     * Сброс пароля по токену
     */
    public function resetPassword(string $email, string $token, string $password): array
    {
        try {
            // Проверяем токен
            $reset = \DB::table('password_resets')
                ->where('email', $email)
                ->first();

            if (!$reset || !Hash::check($token, $reset->token)) {
                return [
                    'success' => false,
                    'error' => 'Неверный токен для сброса пароля'
                ];
            }

            // Проверяем срок действия токена (24 часа)
            if (Carbon::parse($reset->created_at)->addHours(24)->isPast()) {
                \DB::table('password_resets')->where('email', $email)->delete();
                
                return [
                    'success' => false,
                    'error' => 'Срок действия токена истек'
                ];
            }

            // Находим пользователя
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'Пользователь не найден'
                ];
            }

            // Обновляем пароль
            $user->password = Hash::make($password);
            $user->save();

            // Удаляем токен
            \DB::table('password_resets')->where('email', $email)->delete();

            Log::info('Password reset completed', [
                'user_id' => $user->id,
                'email' => $email,
            ]);

            return [
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ];

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при сбросе пароля'
            ];
        }
    }

    /**
     * Сброс пароля через Laravel Password (для NewPasswordController)
     * @throws ValidationException
     */
    public function resetPasswordWithToken(array $credentials): string
    {
        // Используем Laravel Password facade для сброса пароля
        $status = Password::reset(
            $credentials,
            function ($user) use ($credentials) {
                $user->forceFill([
                    'password' => Hash::make($credentials['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                
                Log::info('Password reset via token', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }
        );
        
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }
        
        return $status;
    }

    /**
     * Отправка ссылки для сброса пароля через Laravel Password (для контроллера)
     * @throws ValidationException
     */
    public function sendPasswordResetLinkViaFacade(string $email): string
    {
        // Используем Laravel Password facade для отправки ссылки
        $status = Password::sendResetLink(['email' => $email]);
        
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }
        
        Log::info('Password reset link sent via facade', [
            'email' => $email,
            'ip' => request()->ip(),
        ]);
        
        return $status;
    }
}