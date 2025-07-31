<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Carbon\Carbon;

/**
 * Сервис аутентификации пользователей
 */
class UserAuthService
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

            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return true;

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
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
     * Проверка прав доступа
     */
    public function checkPermission(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /**
     * Проверка может ли пользователь выполнить действие
     */
    public function canPerformAction(User $user, string $action, $resource = null): bool
    {
        // Основные проверки
        if (!$user->isActive()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // Проверяем специфические права
        switch ($action) {
            case 'create_ad':
                return $user->isMaster() && $user->hasPermission('create_ads');
                
            case 'create_booking':
                return $user->isClient() && $user->hasPermission('create_bookings');
                
            case 'moderate_content':
                return $user->isStaff() && $user->hasPermission('moderate_content');
                
            case 'admin_access':
                return $user->isAdmin();
                
            default:
                return $user->hasPermission($action);
        }
    }

    /**
     * Создание API токена для пользователя
     */
    public function createApiToken(User $user, string $name = 'api-token'): string
    {
        // Удаляем старые токены
        $user->tokens()->where('name', $name)->delete();
        
        // Создаем новый токен
        $token = $user->createToken($name);
        
        Log::info('API token created', [
            'user_id' => $user->id,
            'token_name' => $name,
        ]);
        
        return $token->plainTextToken;
    }

    /**
     * Отзыв всех API токенов пользователя
     */
    public function revokeAllTokens(User $user): bool
    {
        try {
            $user->tokens()->delete();
            
            Log::info('All API tokens revoked', [
                'user_id' => $user->id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Token revocation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Получить статистику аутентификации
     */
    public function getAuthStats(): array
    {
        return [
            'total_users' => User::count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'active_users' => User::where('status', UserStatus::ACTIVE)->count(),
            'blocked_users' => User::whereIn('status', [UserStatus::SUSPENDED, UserStatus::BANNED])->count(),
            'registrations_today' => User::whereDate('created_at', today())->count(),
            'registrations_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'logins_today' => User::whereDate('updated_at', today())->count(),
        ];
    }
}