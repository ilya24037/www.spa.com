<?php

namespace App\Infrastructure\Listeners\Master;

use App\Domain\Master\Events\MasterStatusChanged;
use App\Domain\Master\Repositories\MasterRepository;
use App\Infrastructure\Services\SearchIndexService;
use App\Infrastructure\Listeners\Master\Services\StatusValidationService;
use App\Infrastructure\Listeners\Master\Services\StatusActionHandler;
use App\Infrastructure\Listeners\Master\Services\BookingsHandler;
use App\Infrastructure\Listeners\Master\Services\NotificationHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик изменения статуса мастера - координатор
 */
class HandleMasterStatusChanged
{
    private MasterRepository $masterRepository;
    private SearchIndexService $searchIndexService;
    private StatusValidationService $validationService;
    private StatusActionHandler $actionHandler;
    private BookingsHandler $bookingsHandler;
    private NotificationHandler $notificationHandler;

    public function __construct(
        MasterRepository $masterRepository,
        SearchIndexService $searchIndexService,
        StatusValidationService $validationService,
        StatusActionHandler $actionHandler,
        BookingsHandler $bookingsHandler,
        NotificationHandler $notificationHandler
    ) {
        $this->masterRepository = $masterRepository;
        $this->searchIndexService = $searchIndexService;
        $this->validationService = $validationService;
        $this->actionHandler = $actionHandler;
        $this->bookingsHandler = $bookingsHandler;
        $this->notificationHandler = $notificationHandler;
    }

    /**
     * Обработка события MasterStatusChanged
     */
    public function handle(MasterStatusChanged $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем профиль мастера
                $masterProfile = $this->masterRepository->findById($event->masterProfileId);
                if (!$masterProfile) {
                    throw new Exception("Профиль мастера с ID {$event->masterProfileId} не найден");
                }

                // 2. Валидируем смену статуса
                $this->validationService->validateStatusChange(
                    $masterProfile, 
                    $event->oldStatus, 
                    $event->newStatus
                );

                // 3. Обновляем статус
                $this->updateMasterStatus($masterProfile, $event);

                // 4. Выполняем действия специфичные для статуса
                $this->actionHandler->handleStatusSpecificActions($masterProfile, $event);

                // 5. Обрабатываем активные бронирования
                $this->bookingsHandler->handleActiveBookings($masterProfile, $event);

                // 6. Обновляем поисковый индекс
                $this->updateSearchIndex($masterProfile, $event);

                // 7. Создаем запись об изменении статуса
                $this->logStatusChange($masterProfile, $event);

                // 8. Отправляем уведомления
                $this->notificationHandler->sendStatusChangeNotifications($masterProfile, $event);

                Log::info('Master status changed successfully', [
                    'master_profile_id' => $event->masterProfileId,
                    'user_id' => $event->userId,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'changed_by' => $event->changedBy,
                    'reason' => $event->reason,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle MasterStatusChanged event', [
                    'master_profile_id' => $event->masterProfileId,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Обновить статус мастера
     */
    private function updateMasterStatus($masterProfile, MasterStatusChanged $event): void
    {
        $updateData = [
            'status' => $event->newStatus,
            'status_updated_at' => $event->changedAt,
            'status_updated_by' => $event->changedBy,
        ];

        // Обновляем флаг активности
        $updateData['is_active'] = in_array($event->newStatus, ['active']);

        $masterProfile->update($updateData);
    }

    /**
     * Обновить поисковый индекс
     */
    private function updateSearchIndex($masterProfile, MasterStatusChanged $event): void
    {
        try {
            if ($event->newStatus === 'active') {
                // Добавляем/обновляем в индексе
                $this->searchIndexService->indexMasterProfile($masterProfile);
            } else {
                // Удаляем из индекса
                $this->searchIndexService->removeMasterProfile($masterProfile->id);
            }

        } catch (Exception $e) {
            Log::warning('Failed to update search index for master status change', [
                'master_profile_id' => $masterProfile->id,
                'new_status' => $event->newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логировать изменение статуса
     */
    private function logStatusChange($masterProfile, MasterStatusChanged $event): void
    {
        $this->masterRepository->createStatusHistory([
            'master_profile_id' => $masterProfile->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'changed_by' => $event->changedBy,
            'reason' => $event->reason,
            'moderator_feedback' => $event->moderatorFeedback ?? null,
            'changed_at' => $event->changedAt,
            'ip_address' => $event->ipAddress ?? null,
        ]);
    }


    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(MasterStatusChanged::class, [self::class, 'handle']);
    }
}