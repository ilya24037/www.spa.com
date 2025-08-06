<?php

namespace App\Domain\User\Auth;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Lockout;

/**
 * Обработчик авторизации и выхода пользователей
 */
class LoginHandler
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Авторизация пользователя
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        // Проверяем rate limiting
        $key = 'login_attempts:' . $email;
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return [
                'success' => false,
                'error' => "Слишком много попыток входа. Попробуйте через {$seconds} секунд."
            ];
        }

        try {
            // Находим пользователя
            $user = $this->userRepository->findByEmail($email, true);
            
            if (!$user) {
                RateLimiter::hit($key);
                return [
                    'success' => false,
                    'error' => 'Неверный email или пароль'
                ];
            }

            // Проверяем пароль
            if (!Hash::check($password, $user->password)) {
                RateLimiter::hit($key);
                
                Log::warning('Failed login attempt', [
                    'email' => $email,
                    'ip' => request()->ip(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Неверный email или пароль'
                ];
            }

            // Проверяем статус пользователя
            if (!$user->canLogin()) {
                return [
                    'success' => false,
                    'error' => 'Аккаунт заблокирован или неактивен'
                ];
            }

            // Авторизуем пользователя
            Auth::login($user, $remember);

            // Очищаем счетчик попыток
            RateLimiter::clear($key);

            // Обновляем время последней активности
            $user->touch();

            Log::info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => request()->ip(),
            ]);

            return [
                'success' => true,
                'user' => $user,
                'message' => 'Вход выполнен успешно'
            ];

        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Ошибка при входе в систему'
            ];
        }
    }

    /**
     * Аутентификация через Request (для LoginRequest)
     * @throws ValidationException
     */
    public function authenticateRequest(Request $request): void
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');
        
        // Проверяем rate limiting
        $throttleKey = $this->getThrottleKey($request);
        
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));
            
            $seconds = RateLimiter::availableIn($throttleKey);
            
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
        
        // Пытаемся авторизовать пользователя
        if (!Auth::attempt($request->only('email', 'password'), $remember)) {
            RateLimiter::hit($throttleKey);
            
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }
        
        // Очищаем счетчик попыток при успешной авторизации
        RateLimiter::clear($throttleKey);
        
        // Логируем успешный вход
        Log::info('User logged in via request', [
            'user_id' => Auth::id(),
            'email' => $email,
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Выход пользователя
     */
    public function logout(?User $user = null): bool
    {
        try {
            $user = $user ?: Auth::user();
            
            if ($user) {
                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            Auth::guard('web')->logout();

            return true;

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Получить ключ для rate limiting
     */
    protected function getThrottleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    }
}