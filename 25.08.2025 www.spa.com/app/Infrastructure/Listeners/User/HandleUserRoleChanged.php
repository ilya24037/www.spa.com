<?php

namespace App\Infrastructure\Listeners\User;

use App\Domain\User\Events\UserRoleChanged;
use App\Domain\User\Repositories\UserRepository;
use App\Infrastructure\Listeners\User\Handlers\RoleChangeValidator;
use App\Infrastructure\Listeners\User\Handlers\PermissionManager;
use App\Infrastructure\Listeners\User\Handlers\RoleActionHandler;
use App\Infrastructure\Listeners\User\Handlers\RoleChangeNotifier;
use App\Infrastructure\Listeners\User\Handlers\RoleChangeLogger;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Упрощенный обработчик изменения роли пользователя
 * Делегирует работу специализированным обработчикам
 */
class HandleUserRoleChanged
{
    private UserRepository $userRepository;
    private RoleChangeValidator $validator;
    private PermissionManager $permissionManager;
    private RoleActionHandler $actionHandler;
    private RoleChangeNotifier $notifier;
    private RoleChangeLogger $logger;

    public function __construct(
        UserRepository $userRepository,
        RoleChangeValidator $validator,
        PermissionManager $permissionManager,
        RoleActionHandler $actionHandler,
        RoleChangeNotifier $notifier,
        RoleChangeLogger $logger
    ) {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->permissionManager = $permissionManager;
        $this->actionHandler = $actionHandler;
        $this->notifier = $notifier;
        $this->logger = $logger;
    }

    /**
     * Обработка события UserRoleChanged
     */
    public function handle(UserRoleChanged $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем пользователя
                $user = $this->userRepository->findById($event->userId);
                if (!$user) {
                    throw new Exception("Пользователь с ID {$event->userId} не найден");
                }

                // 2. Валидируем смену роли
                $this->validator->validateRoleChange($user, $event->oldRole, $event->newRole);

                // 3. Обновляем права доступа
                $this->permissionManager->updateUserPermissions($user, $event->newRole);

                // 4. Выполняем действия специфичные для новой роли
                $this->actionHandler->handleRoleSpecificActions($user, $event);

                // 5. Обновляем профиль пользователя
                $this->actionHandler->updateUserProfile($user, $event);

                // 6. Настраиваем специфичные для роли настройки
                $this->actionHandler->setupRoleSpecificSettings($user, $event);

                // 7. Создаем запись об изменении роли
                $this->logger->logRoleChange($user, $event);

                // 8. Отправляем уведомления
                $this->notifier->sendRoleChangeNotifications($user, $event);

            } catch (Exception $e) {
                $this->logger->logError($event, $e);
                throw $e;
            }
        });
    }

    /**
     * Проверить возможность изменения роли
     */
    public function canChangeRole($user, string $newRole): bool
    {
        try {
            $currentRole = $user->role->value;
            $this->validator->validateRoleChange($user, $currentRole, $newRole);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Получить недостающие требования для роли
     */
    public function getMissingRequirements($user, string $role): array
    {
        return $this->validator->getMissingRequirements($role, $user);
    }

    /**
     * Получить доступные роли для пользователя
     */
    public function getAvailableRoles($user): array
    {
        $currentRole = $user->role->value;
        $allowedTransitions = $this->validator->getAllowedRoleTransitions();
        
        return $allowedTransitions[$currentRole] ?? [];
    }

    /**
     * Получить описание разрешений для роли
     */
    public function getRolePermissionDescriptions(string $role): array
    {
        return $this->permissionManager->getPermissionDescriptions($role);
    }

    /**
     * Получить историю изменений ролей пользователя
     */
    public function getRoleHistory($userId): array
    {
        return $this->logger->getRoleChangeHistory($userId);
    }

    /**
     * Получить статистику изменений ролей
     */
    public function getRoleChangeStatistics(): array
    {
        return $this->logger->getRoleChangeStatistics();
    }

    /**
     * Отправить деактивацию роли (для дополнительной безопасности)
     */
    public function deactivateRole($user, string $role, string $reason): void
    {
        $this->notifier->sendRoleDeactivationNotification($user, $role, $reason);
        
        $this->logger->logRoleChange($user, new UserRoleChanged(
            userId: $user->id,
            oldRole: $role,
            newRole: 'client', // Деактивация всегда возвращает к роли клиента
            changedBy: 'system',
            reason: $reason,
            changedAt: now()
        ));
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(UserRoleChanged::class, [self::class, 'handle']);
    }
}