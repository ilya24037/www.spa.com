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
 * Job для пакетной отправки уведомлений
 */
class SendNotificationBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения в секундах
     */
    public int $timeout = 300;

    /**
     * Массив ID уведомлений для отправки
     */
    public array $notificationIds;

    public function __construct(array $notificationIds)
    {
        $this->notificationIds = $notificationIds;
        $this->onQueue('notification-batch');
    }

    /**
     * Выполнить job
     */
    public function handle(NotificationService $notificationService): void
    {
        $processed = 0;
        $succeeded = 0;
        $failed = 0;
        $skipped = 0;

        Log::info('Starting notification batch job', [
            'batch_size' => count($this->notificationIds),
            'job_id' => $this->job->getJobId(),
        ]);

        foreach ($this->notificationIds as $notificationId) {
            try {
                $notification = Notification::find($notificationId);

                if (!$notification) {
                    $skipped++;
                    continue;
                }

                // Проверить, можно ли отправлять
                if (!$notification->isPending() || $notification->isExpired()) {
                    if ($notification->isExpired()) {
                        $notification->cancel();
                    }
                    $skipped++;
                    continue;
                }

                // Отправить уведомление
                $results = $notificationService->send($notification);
                
                // Проверить успешность
                $hasSuccessful = count(array_filter($results, fn($r) => $r['success']));
                
                if ($hasSuccessful > 0) {
                    $succeeded++;
                } else {
                    $failed++;
                }

                $processed++;

                // Небольшая пауза между отправками для снижения нагрузки
                if ($processed % 10 === 0) {
                    usleep(100000); // 0.1 секунды
                }

            } catch (\Exception $e) {
                $failed++;
                $processed++;
                
                Log::error('Error in batch notification', [
                    'notification_id' => $notificationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Notification batch job completed', [
            'total' => count($this->notificationIds),
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'skipped' => $skipped,
            'job_id' => $this->job->getJobId(),
        ]);
    }

    /**
     * Обработать неудачное выполнение job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification batch job permanently failed', [
            'batch_size' => count($this->notificationIds),
            'error' => $exception->getMessage(),
            'job_id' => $this->job?->getJobId(),
        ]);

        // Пометить все уведомления как неудачные
        Notification::whereIn('id', $this->notificationIds)
            ->where('status', \App\Enums\NotificationStatus::PENDING)
            ->update([
                'status' => \App\Enums\NotificationStatus::FAILED,
                'metadata->batch_job_failure' => $exception->getMessage(),
            ]);
    }

    /**
     * Получить теги для мониторинга
     */
    public function tags(): array
    {
        return [
            'notification-batch',
            'batch-size:' . count($this->notificationIds),
        ];
    }
}