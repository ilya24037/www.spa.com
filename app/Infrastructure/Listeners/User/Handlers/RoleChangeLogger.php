<?php

namespace App\Infrastructure\Listeners\User\Handlers;

use App\Domain\User\Events\UserRoleChanged;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;

/**
 * Логгер изменений ролей
 */
class RoleChangeLogger
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Логировать изменение роли
     */
    public function logRoleChange($user, UserRoleChanged $event): void
    {
        // Создаем запись в БД
        $this->createDatabaseRecord($user, $event);
        
        // Логируем в файл
        $this->writeToLog($user, $event);
        
        // Аудит для важных ролей
        $this->auditImportantRoles($user, $event);
    }

    /**
     * Создать запись в базе данных
     */
    private function createDatabaseRecord($user, UserRoleChanged $event): void
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
            'metadata' => $this->getChangeMetadata($user, $event),
        ]);
    }

    /**
     * Записать в лог файл
     */
    private function writeToLog($user, UserRoleChanged $event): void
    {
        Log::info('User role changed successfully', [
            'user_id' => $event->userId,
            'user_email' => $user->email,
            'old_role' => $event->oldRole,
            'new_role' => $event->newRole,
            'changed_by' => $event->changedBy,
            'reason' => $event->reason,
            'timestamp' => $event->changedAt->toISOString(),
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
        ]);
    }

    /**
     * Аудит для важных ролей
     */
    private function auditImportantRoles($user, UserRoleChanged $event): void
    {
        $importantRoles = ['admin', 'moderator'];
        
        if (in_array($event->newRole, $importantRoles) || in_array($event->oldRole, $importantRoles)) {
            Log::channel('audit')->warning('Important role change', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'old_role' => $event->oldRole,
                'new_role' => $event->newRole,
                'changed_by' => $event->changedBy,
                'reason' => $event->reason,
                'timestamp' => $event->changedAt->toISOString(),
                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,
                'profile_data' => [
                    'name' => $user->getProfile()?->name,
                    'phone' => $user->getProfile()?->phone,
                    'created_at' => $user->created_at,
                ],
            ]);
        }
    }

    /**
     * Получить метаданные изменения
     */
    private function getChangeMetadata($user, UserRoleChanged $event): array
    {
        return [
            'user_data' => [
                'email' => $user->email,
                'name' => $user->getProfile()?->name,
                'phone' => $user->getProfile()?->phone,
                'created_at' => $user->created_at,
                'last_login_at' => $user->last_login_at,
            ],
            'change_context' => [
                'previous_role_duration' => $this->calculateRoleDuration($user, $event->oldRole),
                'role_change_count' => $this->getRoleChangeCount($user),
                'is_first_role_change' => $this->isFirstRoleChange($user),
            ],
            'system_info' => [
                'app_version' => config('app.version'),
                'environment' => config('app.env'),
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Вычислить продолжительность роли
     */
    private function calculateRoleDuration($user, string $role): ?int
    {
        $lastRoleChange = $this->userRepository->getLastRoleChangeForUser($user->id);
        
        if (!$lastRoleChange) {
            return $user->created_at->diffInDays(now());
        }
        
        return $lastRoleChange->changed_at->diffInDays(now());
    }

    /**
     * Получить количество изменений ролей
     */
    private function getRoleChangeCount($user): int
    {
        return $this->userRepository->getRoleChangeCountForUser($user->id);
    }

    /**
     * Проверить, первое ли это изменение роли
     */
    private function isFirstRoleChange($user): bool
    {
        return $this->getRoleChangeCount($user) === 0;
    }

    /**
     * Логировать ошибку обработки события
     */
    public function logError($event, \Exception $exception): void
    {
        Log::error('Failed to handle UserRoleChanged event', [
            'user_id' => $event->userId,
            'old_role' => $event->oldRole,
            'new_role' => $event->newRole,
            'error' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'timestamp' => now()->toISOString(),
        ]);

        // Дополнительное логирование в канал аудита для критичных ошибок
        Log::channel('audit')->error('Critical role change failure', [
            'user_id' => $event->userId,
            'new_role' => $event->newRole,
            'error_message' => $exception->getMessage(),
            'changed_by' => $event->changedBy,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Получить историю изменений ролей пользователя
     */
    public function getRoleChangeHistory($userId, int $limit = 10): array
    {
        return $this->userRepository->getRoleChangeHistory($userId, $limit);
    }

    /**
     * Получить статистику изменений ролей
     */
    public function getRoleChangeStatistics(): array
    {
        return [
            'total_changes_today' => $this->userRepository->getRoleChangesCount('today'),
            'total_changes_this_week' => $this->userRepository->getRoleChangesCount('week'),
            'total_changes_this_month' => $this->userRepository->getRoleChangesCount('month'),
            'most_common_transitions' => $this->userRepository->getMostCommonRoleTransitions(),
            'role_distribution' => $this->userRepository->getRoleDistribution(),
        ];
    }
}