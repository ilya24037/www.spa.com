<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Auth\LoginHandler;
use App\Domain\User\Auth\RegistrationHandler;
use App\Domain\User\Auth\PasswordResetHandler;
use App\Domain\User\Auth\TokenManager;
use App\Domain\User\Auth\PermissionChecker;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Упрощенный сервис аутентификации - делегирует работу специализированным обработчикам
 */
class UserAuthService
{
    private UserRepository $userRepository;
    private LoginHandler $loginHandler;
    private RegistrationHandler $registrationHandler;
    private PasswordResetHandler $passwordResetHandler;
    private TokenManager $tokenManager;
    private PermissionChecker $permissionChecker;

    public function __construct(
        UserRepository $userRepository,
        UserService $userService
    ) {
        $this->userRepository = $userRepository;
        
        // Инициализируем обработчики
        $this->loginHandler = new LoginHandler($userRepository);
        $this->registrationHandler = new RegistrationHandler($userRepository, $userService);
        $this->passwordResetHandler = new PasswordResetHandler($userRepository);
        $this->tokenManager = new TokenManager();
        $this->permissionChecker = new PermissionChecker();
    }

    // === РЕГИСТРАЦИЯ ===

    /**
     * Регистрация нового пользователя
     */
    public function register(array $data): array
    {
        return $this->registrationHandler->register($data);
    }

    /**
     * Подтверждение email адреса
     */
    public function verifyEmail(User $user): array
    {
        return $this->registrationHandler->verifyEmail($user);
    }

    // === АВТОРИЗАЦИЯ ===

    /**
     * Авторизация пользователя
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        return $this->loginHandler->login($email, $password, $remember);
    }

    /**
     * Аутентификация через Request (для LoginRequest)
     * @throws ValidationException
     */
    public function authenticateRequest(Request $request): void
    {
        $this->loginHandler->authenticateRequest($request);
    }

    /**
     * Выход пользователя
     */
    public function logout(?User $user = null): bool
    {
        return $this->loginHandler->logout($user);
    }

    // === ВОССТАНОВЛЕНИЕ ПАРОЛЯ ===

    /**
     * Отправка ссылки для сброса пароля
     */
    public function sendPasswordResetLink(string $email): array
    {
        return $this->passwordResetHandler->sendPasswordResetLink($email);
    }

    /**
     * Сброс пароля по токену
     */
    public function resetPassword(string $email, string $token, string $password): array
    {
        return $this->passwordResetHandler->resetPassword($email, $token, $password);
    }

    /**
     * Сброс пароля через Laravel Password (для NewPasswordController)
     * @throws ValidationException
     */
    public function resetPasswordWithToken(array $credentials): string
    {
        return $this->passwordResetHandler->resetPasswordWithToken($credentials);
    }

    /**
     * Отправка ссылки для сброса пароля через Laravel Password (для контроллера)
     * @throws ValidationException
     */
    public function sendPasswordResetLinkViaFacade(string $email): string
    {
        return $this->passwordResetHandler->sendPasswordResetLinkViaFacade($email);
    }

    // === API ТОКЕНЫ ===

    /**
     * Создание API токена для пользователя
     */
    public function createApiToken(User $user, string $name = 'api-token'): string
    {
        return $this->tokenManager->createApiToken($user, $name);
    }

    /**
     * Отзыв всех API токенов пользователя
     */
    public function revokeAllTokens(User $user): bool
    {
        return $this->tokenManager->revokeAllTokens($user);
    }

    /**
     * Получить все активные токены пользователя
     */
    public function getUserTokens(User $user): array
    {
        return $this->tokenManager->getUserTokens($user);
    }

    // === ПРАВА ДОСТУПА ===

    /**
     * Проверка прав доступа
     */
    public function checkPermission(User $user, string $permission): bool
    {
        return $this->permissionChecker->checkPermission($user, $permission);
    }

    /**
     * Проверка может ли пользователь выполнить действие
     */
    public function canPerformAction(User $user, string $action, $resource = null): bool
    {
        return $this->permissionChecker->canPerformAction($user, $action, $resource);
    }

    /**
     * Проверить может ли пользователь редактировать ресурс
     */
    public function canEdit(User $user, $resource): bool
    {
        return $this->permissionChecker->canEdit($user, $resource);
    }

    /**
     * Проверить может ли пользователь просматривать ресурс
     */
    public function canView(User $user, $resource): bool
    {
        return $this->permissionChecker->canView($user, $resource);
    }

    /**
     * Проверить может ли пользователь удалить ресурс
     */
    public function canDelete(User $user, $resource): bool
    {
        return $this->permissionChecker->canDelete($user, $resource);
    }

    // === СТАТИСТИКА ===

    /**
     * Получить статистику аутентификации
     */
    public function getAuthStats(): array
    {
        return $this->userRepository->getStatistics();
    }
}