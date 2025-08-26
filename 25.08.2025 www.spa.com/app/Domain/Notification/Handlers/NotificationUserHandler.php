<?php

namespace App\Domain\Notification\Handlers;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Repositories\NotificationRepository;
use App\Domain\User\Models\User;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик пользовательских операций с уведомлениями
 */
class NotificationUserHandler
{
    public function __construct(
        protected NotificationRepository $repository
    ) {}

    /**
     * Получить уведомления для пользователя
     */
    public function getForUser(int $userId, array $options = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->getForUser($userId, $options);
    }

    /**
     * Пометить уведомление как прочитанное
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->repository->find($notificationId);
        
        if (!$notification || $notification->user_id !== $userId) {
            return false;
        }

        if (!$notification->isRead()) {
            $notification->markAsRead();
            
            // Логировать прочтение
            Log::info('Notification marked as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ]);
        }

        return true;
    }

    /**
     * Пометить все уведомления пользователя как прочитанные
     */
    public function markAllAsRead(int $userId): int
    {
        $updated = Notification::forUser($userId)
            ->unread()
            ->update([
                'read_at' => now(),
                'status' => NotificationStatus::READ,
            ]);

        Log::info('All notifications marked as read', [
            'user_id' => $userId,
            'count' => $updated,
        ]);

        return $updated;
    }

    /**
     * Получить количество непрочитанных уведомлений
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->repository->getUnreadCount($userId);
    }

    /**
     * Удалить уведомление
     */
    public function delete(int $notificationId, int $userId): bool
    {
        $notification = $this->repository->find($notificationId);
        
        if (!$notification || $notification->user_id !== $userId) {
            return false;
        }

        $notification->delete();
        
        Log::info('Notification deleted', [
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Получить группированные уведомления
     */
    public function getGroupedNotifications(int $userId, array $options = []): array
    {
        $notifications = $this->getForUser($userId, $options);
        $grouped = [];

        foreach ($notifications as $notification) {
            $groupKey = $notification->group_key ?? 'default';
            
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'type' => $notification->type,
                    'count' => 0,
                    'latest' => null,
                    'notifications' => [],
                ];
            }

            $grouped[$groupKey]['count']++;
            $grouped[$groupKey]['notifications'][] = $notification;
            
            if (!$grouped[$groupKey]['latest'] || 
                $notification->created_at->gt($grouped[$groupKey]['latest']->created_at)) {
                $grouped[$groupKey]['latest'] = $notification;
            }
        }

        return $grouped;
    }

    /**
     * Проверить настройки уведомлений пользователя
     */
    public function canSendToUser(int $userId, NotificationType $type, array $channels): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        $preferences = $user->notification_preferences ?? [];
        
        // Проверить, разрешены ли уведомления этого типа
        if (isset($preferences['types'][$type->value]) && !$preferences['types'][$type->value]) {
            return false;
        }

        // Проверить, разрешены ли каналы
        foreach ($channels as $channel) {
            if (isset($preferences[$channel]) && $preferences[$channel]) {
                return true; // Хотя бы один канал разрешен
            }
        }

        return true; // По умолчанию разрешено
    }

    /**
     * Обновить настройки уведомлений пользователя
     */
    public function updateUserPreferences(int $userId, array $preferences): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        $user->update(['notification_preferences' => $preferences]);
        
        Log::info('User notification preferences updated', [
            'user_id' => $userId,
            'preferences' => $preferences,
        ]);

        return true;
    }

    /**
     * Получить настройки уведомлений пользователя
     */
    public function getUserPreferences(int $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }

        return $user->notification_preferences ?? [];
    }

    /**
     * Получить рекомендации по настройкам уведомлений
     */
    public function getRecommendedSettings(int $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }

        // Базовые настройки по умолчанию
        $recommendations = [
            'email' => true,
            'push' => true,
            'sms' => false,
            'types' => [
                'booking_confirmed' => true,
                'booking_reminder' => true,
                'payment_received' => true,
                'system_maintenance' => false,
                'marketing' => false,
            ],
        ];

        // Адаптировать рекомендации на основе активности пользователя
        $recentActivity = Notification::forUser($userId)
            ->where('created_at', '>', now()->subDays(30))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Если пользователь часто получает определенные типы, рекомендуем их включить
        foreach ($recentActivity as $type => $count) {
            if ($count > 5) {
                $recommendations['types'][$type] = true;
            }
        }

        return $recommendations;
    }
}