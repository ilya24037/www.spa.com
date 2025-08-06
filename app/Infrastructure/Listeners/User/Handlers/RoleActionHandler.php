<?php

namespace App\Infrastructure\Listeners\User\Handlers;

use App\Domain\User\Events\UserRoleChanged;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик действий специфичных для ролей
 */
class RoleActionHandler
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Выполнить действия специфичные для роли
     */
    public function handleRoleSpecificActions($user, UserRoleChanged $event): void
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

            case 'support':
                $this->handleSupportRoleAssignment($user, $event);
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

        Log::info('Master role specific actions completed', ['user_id' => $user->id]);
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

        Log::info('Client role specific actions completed', ['user_id' => $user->id]);
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

        Log::info('Moderator role specific actions completed', ['user_id' => $user->id]);
    }

    /**
     * Обработать назначение роли поддержки
     */
    private function handleSupportRoleAssignment($user, UserRoleChanged $event): void
    {
        // Добавляем в группу поддержки
        $this->userRepository->addToGroup($user->id, 'support');

        // Создаем рабочую область поддержки
        $this->userRepository->createSupportWorkspace($user->id);

        Log::info('Support role specific actions completed', ['user_id' => $user->id]);
    }

    /**
     * Обновить профиль пользователя для новой роли
     */
    public function updateUserProfile($user, UserRoleChanged $event): void
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

        if ($event->newRole === 'moderator') {
            $profileUpdates['is_moderator'] = true;
            $profileUpdates['moderator_since'] = now();
        } elseif ($event->oldRole === 'moderator') {
            $profileUpdates['is_moderator'] = false;
        }

        if (!empty($profileUpdates)) {
            $profile->update($profileUpdates);
            
            Log::info('User profile updated for role change', [
                'user_id' => $user->id,
                'updates' => array_keys($profileUpdates),
            ]);
        }
    }

    /**
     * Настроить специфичные для роли настройки
     */
    public function setupRoleSpecificSettings($user, UserRoleChanged $event): void
    {
        $roleSettings = $this->getRoleSpecificSettings($event->newRole);
        
        if (!empty($roleSettings)) {
            $this->userRepository->updateSettings($user->id, $roleSettings);
            
            Log::info('Role specific settings applied', [
                'user_id' => $user->id,
                'role' => $event->newRole,
                'settings_count' => count($roleSettings),
            ]);
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

            'support' => [
                'support_notifications' => true,
                'ticket_assignments' => true,
                'escalation_alerts' => true,
            ],
        ];

        return $settings[$role] ?? [];
    }
}