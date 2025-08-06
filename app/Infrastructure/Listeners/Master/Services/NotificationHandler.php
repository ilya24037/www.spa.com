<?php

namespace App\Infrastructure\Listeners\Master\Services;

use App\Domain\Master\Events\MasterStatusChanged;
use App\Domain\Master\Services\MasterNotificationService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик уведомлений при изменении статуса мастера
 */
class NotificationHandler
{
    private MasterNotificationService $notificationService;

    public function __construct(MasterNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Отправить уведомления о смене статуса
     */
    public function sendStatusChangeNotifications($masterProfile, MasterStatusChanged $event): void
    {
        try {
            // Уведомление мастеру
            $this->notificationService->sendStatusChangeNotification($masterProfile, [
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'reason' => $event->reason,
                'moderator_feedback' => $event->moderatorFeedback,
            ]);

            // Специальные уведомления в зависимости от статуса
            switch ($event->newStatus) {
                case 'active':
                    $this->notificationService->sendActivationNotification($masterProfile);
                    break;

                case 'rejected':
                    $this->notificationService->sendRejectionNotification($masterProfile, $event->reason);
                    break;

                case 'suspended':
                case 'banned':
                    $this->notificationService->sendSuspensionNotification($masterProfile, $event->reason);
                    break;
            }

            // Уведомления администраторам о важных изменениях статуса
            if (in_array($event->oldStatus, ['pending_moderation']) && $event->newStatus === 'active') {
                $this->notificationService->sendNewActiveMasterNotification($masterProfile);
            }

        } catch (Exception $e) {
            Log::warning('Failed to send master status change notifications', [
                'master_profile_id' => $masterProfile->id,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }
}