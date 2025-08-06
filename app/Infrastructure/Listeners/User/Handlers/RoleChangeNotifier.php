<?php

namespace App\Infrastructure\Listeners\User\Handlers;

use App\Domain\User\Events\UserRoleChanged;
use App\Infrastructure\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Отправщик уведомлений об изменении ролей
 */
class RoleChangeNotifier
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Отправить уведомления об изменении роли
     */
    public function sendRoleChangeNotifications($user, UserRoleChanged $event): void
    {
        try {
            // Уведомление пользователю
            $this->sendUserNotification($user, $event);

            // Специальные уведомления для определенных ролей
            $this->sendRoleSpecificNotifications($user, $event);

            // Уведомления администраторам о важных изменениях ролей
            $this->sendAdminNotifications($user, $event);

            Log::info('Role change notifications sent successfully', [
                'user_id' => $user->id,
                'new_role' => $event->newRole,
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to send role change notifications', [
                'user_id' => $user->id,
                'new_role' => $event->newRole,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправить уведомление пользователю
     */
    private function sendUserNotification($user, UserRoleChanged $event): void
    {
        $this->emailService->sendRoleChangeNotification($user->email, [
            'user_name' => $user->getProfile()?->name ?? 'Пользователь',
            'old_role' => $this->getRoleDisplayName($event->oldRole),
            'new_role' => $this->getRoleDisplayName($event->newRole),
            'changed_at' => $event->changedAt->format('d.m.Y H:i'),
            'reason' => $event->reason,
            'new_permissions' => $this->getPermissionDescriptions($event->newRole),
        ]);
    }

    /**
     * Отправить специфичные для роли уведомления
     */
    private function sendRoleSpecificNotifications($user, UserRoleChanged $event): void
    {
        if ($event->newRole === 'master') {
            $this->sendMasterWelcomeNotification($user);
        }

        if ($event->newRole === 'admin') {
            $this->sendAdminWelcomeNotification($user, $event);
        }

        if ($event->newRole === 'moderator') {
            $this->sendModeratorWelcomeNotification($user, $event);
        }
    }

    /**
     * Отправить приветственное уведомление мастеру
     */
    private function sendMasterWelcomeNotification($user): void
    {
        $this->emailService->sendMasterWelcomeEmail($user->email, [
            'user_name' => $user->getProfile()?->name,
            'guide_url' => url('/master/guide'),
            'support_email' => config('mail.support_email'),
        ]);
    }

    /**
     * Отправить приветственное уведомление админу
     */
    private function sendAdminWelcomeNotification($user, UserRoleChanged $event): void
    {
        $this->emailService->sendAdminWelcomeEmail($user->email, [
            'user_name' => $user->getProfile()?->name,
            'admin_panel_url' => url('/admin'),
            'security_guide_url' => url('/admin/security-guide'),
            'assigned_by' => $event->changedBy,
        ]);
    }

    /**
     * Отправить приветственное уведомление модератору
     */
    private function sendModeratorWelcomeNotification($user, UserRoleChanged $event): void
    {
        $this->emailService->sendModeratorWelcomeEmail($user->email, [
            'user_name' => $user->getProfile()?->name,
            'moderation_panel_url' => url('/moderator'),
            'guidelines_url' => url('/moderator/guidelines'),
            'assigned_by' => $event->changedBy,
        ]);
    }

    /**
     * Отправить уведомления администраторам
     */
    private function sendAdminNotifications($user, UserRoleChanged $event): void
    {
        if (in_array($event->newRole, ['admin', 'moderator'])) {
            $this->emailService->sendAdminRoleChangeAlert([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->getProfile()?->name,
                'new_role' => $this->getRoleDisplayName($event->newRole),
                'old_role' => $this->getRoleDisplayName($event->oldRole),
                'changed_by' => $event->changedBy,
                'reason' => $event->reason,
                'changed_at' => $event->changedAt->format('d.m.Y H:i'),
            ]);
        }
    }

    /**
     * Получить отображаемое имя роли
     */
    public function getRoleDisplayName(string $role): string
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
    public function getPermissionDescriptions(string $role): array
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
                'Конфигурация системы',
            ],
            'moderator' => [
                'Модерация пользователей и контента',
                'Обработка жалоб и отчетов',
                'Управление содержимым платформы',
            ],
            'support' => [
                'Просмотр данных пользователей',
                'Обработка тикетов поддержки',
                'Помощь клиентам',
            ],
        ];

        return $descriptions[$role] ?? [];
    }

    /**
     * Отправить уведомление о деактивации роли
     */
    public function sendRoleDeactivationNotification($user, string $deactivatedRole, string $reason): void
    {
        try {
            $this->emailService->sendRoleDeactivationNotification($user->email, [
                'user_name' => $user->getProfile()?->name ?? 'Пользователь',
                'deactivated_role' => $this->getRoleDisplayName($deactivatedRole),
                'reason' => $reason,
                'deactivated_at' => now()->format('d.m.Y H:i'),
                'support_email' => config('mail.support_email'),
            ]);

        } catch (Exception $e) {
            Log::warning('Failed to send role deactivation notification', [
                'user_id' => $user->id,
                'deactivated_role' => $deactivatedRole,
                'error' => $e->getMessage(),
            ]);
        }
    }
}