<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\Notification;
use App\Enums\NotificationStatus;

/**
 * Сервис очистки старых уведомлений
 */
class NotificationCleanupService
{
    /**
     * Cleanup старых уведомлений
     */
    public function cleanup(): int
    {
        $deletedCount = 0;
        
        // Удаляем прочитанные уведомления старше 30 дней
        $deletedCount += $this->cleanupReadNotifications();
        
        // Удаляем истекшие уведомления
        $deletedCount += $this->cleanupExpiredNotifications();
        
        // Удаляем неудачные уведомления старше 7 дней
        $deletedCount += $this->cleanupFailedNotifications();
        
        return $deletedCount;
    }

    /**
     * Очистка прочитанных уведомлений
     */
    private function cleanupReadNotifications(): int
    {
        return Notification::where('read_at', '<', now()->subDays(30))->delete();
    }

    /**
     * Очистка истекших уведомлений
     */
    private function cleanupExpiredNotifications(): int
    {
        return Notification::expired()->delete();
    }

    /**
     * Очистка неудачных уведомлений
     */
    private function cleanupFailedNotifications(): int
    {
        return Notification::where('status', NotificationStatus::FAILED)
                          ->where('created_at', '<', now()->subDays(7))
                          ->delete();
    }
}