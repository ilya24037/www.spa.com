<?php

namespace App\Jobs;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для отправки уведомления
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения в секундах
     */
    public int $timeout = 120;

    /**
     * ID уведомления для отправки
     */
    public int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
        $this->onQueue('notifications');
    }

    /**
     * Выполнить job
     */
    public function handle(NotificationService $notificationService): void
    {
        $notification = Notification::find($this->notificationId);

        if (!$notification) {
            Log::warning('Notification not found for job', [
                'notification_id' => $this->notificationId,
                'job_id' => $this->job->getJobId(),
            ]);
            return;
        }

        // Проверить, не истекло ли уведомление
        if ($notification->isExpired()) {
            $notification->cancel();
            Log::info('Notification expired, cancelled', [
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        // Проверить, можно ли отправлять
        if (!$notification->isPending()) {
            Log::info('Notification is not pending, skipping', [
                'notification_id' => $this->notificationId,
                'status' => $notification->status->value,
            ]);
            return;
        }

        try {
            // Отправить уведомление
            $results = $notificationService->send($notification);
            
            Log::info('Notification job completed', [
                'notification_id' => $this->notificationId,
                'results' => $results,
                'job_id' => $this->job->getJobId(),
            ]);

        } catch (\Exception $e) {
            Log::error('Notification job failed', [
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'job_id' => $this->job->getJobId(),
            ]);

            // Если это последняя попытка, пометить как неудачное
            if ($this->attempts() >= $this->tries) {
                $notification->markAsFailed("Job failed after {$this->tries} attempts: {$e->getMessage()}");
            }

            throw $e;
        }
    }

    /**
     * Обработать неудачное выполнение job
     */
    public function failed(\Throwable $exception): void
    {
        $notification = Notification::find($this->notificationId);

        if ($notification) {
            $notification->markAsFailed("Job permanently failed: {$exception->getMessage()}");
        }

        Log::error('Notification job permanently failed', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
            'job_id' => $this->job?->getJobId(),
        ]);
    }

    /**
     * Получить теги для мониторинга
     */
    public function tags(): array
    {
        return [
            'notification:' . $this->notificationId,
            'notification-send',
        ];
    }
}