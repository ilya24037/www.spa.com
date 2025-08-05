<?php

namespace App\Infrastructure\Listeners\User;

use App\Domain\User\Events\UserRoleChanged;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Services\UserService;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\PermissionService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик изменения роли пользователя
 * 
 * ФУНКЦИИ:
 * - Обновление прав доступа
 * - Создание мастер-профиля при назначении роли мастера
 * - Отправка уведомлений о смене роли
 * - Настройка специфичных для роли настроек
 * - Логирование изменений прав
 */
class HandleUserRoleChanged
{
    private UserRepository $userRepository;
    private UserService $userService;
    private EmailService $emailService;
    private PermissionService $permissionService;

    public function __construct(
        UserRepository $userRepository,
        UserService $userService,
        EmailService $emailService,
        PermissionService $permissionService
    ) {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->emailService = $emailService;
        $this->permissionService = $permissionService;
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
                $this->validateRoleChange($user, $event->oldRole, $event->newRole);

                // 3. Обновляем права доступа
                $this->updateUserPermissions($user, $event);

                // 4. Выполняем действия специфичные для новой роли
                $this->handleRoleSpecificActions($user, $event);

                // 5. Обновляем профиль пользователя
                $this->updateUserProfile($user, $event);

                // 6. Настраиваем специфичные для роли настройки
                $this->setupRoleSpecificSettings($user, $event);

                // 7. Создаем запись об изменении роли
                $this->logRoleChange($user, $event);

                // 8. Отправляем уведомления
                $this->sendRoleChangeNotifications($user, $event);

                Log::info('User role changed successfully', [
                    'user_id' => $event->userId,
                    'old_role' => $event->oldRole,
                    'new_role' => $event->newRole,
                    'changed_by' => $event->changedBy,
                    'reason' => $event->reason,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle UserRoleChanged event', [
                    'user_id' => $event->userId,
                    'old_role' => $event->oldRole,
                    'new_role' => $event->newRole,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Валидировать смену роли
     */
    private function validateRoleChange($user, string $oldRole, string $newRole): void
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
    private function getAllowedRoleTransitions(): array
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
    private function validateMasterRoleRequirements($user): void
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
     * Обновить права доступа пользователя
     */
    private function updateUserPermissions($user, UserRoleChanged $event): void
    {
        // Удаляем старые права
        $this->permissionService->revokeAllPermissions($user);

        // Назначаем права для новой роли
        $permissions = $this->getPermissionsForRole($event->newRole);
        $this->permissionService->grantPermissions($user, $permissions);

        Log::info('User permissions updated', [
            'user_id' => $user->id,
            'new_role' => $event->newRole,
            'permissions_count' => count($permissions),
        ]);
    }

    /**
     * Получить права для роли
     */
    private function getPermissionsForRole(string $role): array
    {
        $permissions = [
            'client' => [
                'bookings:create',
                'bookings:view_own',
                'bookings:cancel_own',
                'reviews:create',
                'profile:update_own',
                'favorites:manage',
            ],
            
            'master' => [
                'bookings:create',
                'bookings:view_own',
                'bookings:cancel_own',
                'bookings:manage_assigned',
                'master_profile:manage_own',
                'calendar:manage_own',
                'reviews:view_own',
                'earnings:view_own',
                'services:manage_own',
            ],
            
            'admin' => [
                'users:*',
                'bookings:*',
                'masters:*',
                'reviews:*',
                'analytics:*',
                'system:*',
            ],
            
            'moderator' => [
                'users:view',
                'users:moderate',
                'masters:moderate',
                'reviews:moderate',
                'content:moderate',
            ],
            
            'support' => [
                'users:view',
                'bookings:view',
                'support:handle_tickets',
            ],
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Выполнить действия специфичные для роли
     */
    private function handleRoleSpecificActions($user, UserRoleChanged $event): void
    {
        switch ($event->newRole) {
            case 'master':
                $this->handleMasterRoleAssignment($user, $event);
                break;

            case 'client':
                $this->handleClientRoleAssignment($user, $event);
                break;

            case 'admin':
                $this->handleAdminRoleAssignment($user, $event);
                break;

            case 'moderator':
                $this->handleModeratorRoleAssignment($user, $event);
                break;
        }
    }

    /**
     * Обработать назначение роли мастера
     */
    private function handleMasterRoleAssignment($user, UserRoleChanged $event): void
    {
        // Создаем мастер-профиль через событие если его еще нет
        if (!$user->getMasterProfile()) {
            $profile = $user->getProfile();
            
            // Отправляем событие создания мастер-профиля
            event(new \App\Domain\Master\Events\MasterProfileCreated(
                userId: $user->id,
                masterProfileId: 0, // Будет установлено в обработчике
                profileData: [
                    'name' => $profile?->name ?? 'Мастер',
                    'city' => $profile?->city ?? 'Москва',
                    'phone' => $profile?->phone,
                    'bio' => 'Новый мастер на платформе',
                    'services' => [],
                    'experience' => 0,
                ]
            ));
        }

        // Добавляем в группу мастеров для уведомлений
        $this->userRepository->addToGroup($user->id, 'masters');

        // Устанавливаем статус "новый мастер" на 30 дней
        $this->userRepository->addUserTag($user->id, 'new_master', now()->addDays(30));
    }

    /**
     * Обработать назначение роли клиента
     */
    private function handleClientRoleAssignment($user, UserRoleChanged $event): void
    {
        // Если пользователь был мастером, деактивируем его профиль
        if ($event->oldRole === 'master') {
            $masterProfile = $user->getMasterProfile();
            if ($masterProfile) {
                // Отправляем событие деактивации мастер-профиля
                event(new \App\Domain\Master\Events\MasterStatusChanged(
                    masterProfileId: $masterProfile->id,
                    userId: $user->id,
                    oldStatus: $masterProfile->status,
                    newStatus: 'inactive',
                    changedBy: $event->changedBy,
                    reason: 'Роль изменена на клиента',
                    changedAt: now()
                ));
            }
        }

        // Добавляем в группу клиентов
        $this->userRepository->addToGroup($user->id, 'clients');
    }

    /**
     * Обработать назначение роли админа
     */
    private function handleAdminRoleAssignment($user, UserRoleChanged $event): void
    {
        // Добавляем в группу администраторов
        $this->userRepository->addToGroup($user->id, 'admins');

        // Включаем двухфакторную аутентификацию по умолчанию
        $this->userRepository->updateSettings($user->id, [
            'two_factor_enabled' => true,
            'session_timeout' => 3600, // 1 час для админов
        ]);

        // Логируем назначение админа для аудита
        Log::warning('Admin role assigned', [
            'user_id' => $user->id,
            'assigned_by' => $event->changedBy,
            'reason' => $event->reason,
        ]);
    }

    /**
     * Обработать назначение роли модератора
     */
    private function handleModeratorRoleAssignment($user, UserRoleChanged $event): void
    {
        // Добавляем в группу модераторов
        $this->userRepository->addToGroup($user->id, 'moderators');

        // Создаем рабочую область модератора
        $this->userRepository->createModeratorWorkspace($user->id);
    }

    /**
     * Обновить профиль пользователя
     */
    private function updateUserProfile($user, UserRoleChanged $event): void
    {
        $profile = $user->getProfile();
        if (!$profile) {
            return;
        }

        // Обновляем поля специфичные для роли
        $profileUpdates = [];

        if ($event->newRole === 'master') {
            $profileUpdates['is_master'] = true;
            $profileUpdates['master_since'] = now();
        } elseif ($event->oldRole === 'master') {
            $profileUpdates['is_master'] = false;
        }

        if ($event->newRole === 'admin') {
            $profileUpdates['is_admin'] = true;
            $profileUpdates['admin_since'] = now();
        } elseif ($event->oldRole === 'admin') {
            $profileUpdates['is_admin'] = false;
        }

        if (!empty($profileUpdates)) {
            $profile->update($profileUpdates);
        }
    }

    /**
     * Настроить специфичные для роли настройки
     */
    private function setupRoleSpecificSettings($user, UserRoleChanged $event): void
    {
        $roleSettings = $this->getRoleSpecificSettings($event->newRole);
        
        if (!empty($roleSettings)) {
            $this->userRepository->updateSettings($user->id, $roleSettings);
        }
    }

    /**
     * Получить специфичные для роли настройки
     */
    private function getRoleSpecificSettings(string $role): array
    {
        $settings = [
            'master' => [
                'booking_notifications' => true,
                'client_message_notifications' => true,
                'earnings_notifications' => true,
                'review_notifications' => true,
            ],
            
            'admin' => [
                'system_notifications' => true,
                'security_alerts' => true,
                'user_activity_reports' => true,
                'session_timeout' => 3600,
            ],
            
            'moderator' => [
                'moderation_notifications' => true,
                'content_reports' => true,
                'user_reports' => true,
            ],
        ];

        return $settings[$role] ?? [];
    }

    /**
     * Логировать изменение роли
     */
    private function logRoleChange($user, UserRoleChanged $event): void
    {
        $this->userRepository->createRoleChangeRecord([
            'user_id' => $user->id,
            'old_role' => $event->oldRole,
            'new_role' => $event->newRole,
            'changed_by' => $event->changedBy,
            'reason' => $event->reason,
            'changed_at' => $event->changedAt,
            'ip_address' => $event->ipAddress ?? null,
            'user_agent' => $event->userAgent ?? null,
        ]);
    }

    /**
     * Отправить уведомления об изменении роли
     */
    private function sendRoleChangeNotifications($user, UserRoleChanged $event): void
    {
        try {
            // Уведомление пользователю
            $this->emailService->sendRoleChangeNotification($user->email, [
                'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                'old_role' => $this->getRoleDisplayName($event->oldRole),
                'new_role' => $this->getRoleDisplayName($event->newRole),
                'changed_at' => $event->changedAt->format('d.m.Y H:i'),
                'reason' => $event->reason,
                'new_permissions' => $this->getPermissionDescriptions($event->newRole),
            ]);

            // Специальные уведомления для определенных ролей
            if ($event->newRole === 'master') {
                $this->emailService->sendMasterWelcomeEmail($user->email, [
                    'user_name' => $user->getProfile()?->name,
                    'guide_url' => url('/master/guide'),
                    'support_email' => config('mail.support_email'),
                ]);
            }

            // Уведомления администраторам о важных изменениях ролей
            if (in_array($event->newRole, ['admin', 'moderator'])) {
                $this->emailService->sendAdminRoleChangeAlert([
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'new_role' => $event->newRole,
                    'changed_by' => $event->changedBy,
                    'reason' => $event->reason,
                ]);
            }

        } catch (Exception $e) {
            Log::warning('Failed to send role change notifications', [
                'user_id' => $user->id,
                'new_role' => $event->newRole,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить отображаемое имя роли
     */
    private function getRoleDisplayName(string $role): string
    {
        $names = [
            'client' => 'Клиент',
            'master' => 'Мастер',
            'admin' => 'Администратор',
            'moderator' => 'Модератор',
            'support' => 'Поддержка',
        ];

        return $names[$role] ?? $role;
    }

    /**
     * Получить описания прав для роли
     */
    private function getPermissionDescriptions(string $role): array
    {
        $descriptions = [
            'client' => [
                'Создание и управление бронированиями',
                'Написание отзывов',
                'Управление избранными мастерами',
            ],
            'master' => [
                'Создание и управление профилем мастера',
                'Управление календарем и услугами',
                'Просмотр заработка и статистики',
                'Работа с клиентскими бронированиями',
            ],
            'admin' => [
                'Полный доступ к системе',
                'Управление пользователями',
                'Просмотр аналитики и отчетов',
            ],
        ];

        return $descriptions[$role] ?? [];
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(UserRoleChanged::class, [self::class, 'handle']);
    }
}