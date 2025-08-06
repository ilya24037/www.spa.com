<?php

namespace App\Infrastructure\Listeners\Master;

use App\Domain\Master\Events\MasterProfileUpdated;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Services\MasterNotificationService;
use App\Infrastructure\Services\SearchIndexService;
use App\Infrastructure\Listeners\Master\Processors\ProfileChangeAnalyzer;
use App\Infrastructure\Listeners\Master\Processors\ProfileUpdateValidator;
use App\Infrastructure\Listeners\Master\Processors\ProfileMediaProcessor;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик обновления профиля мастера - координатор
 */
class HandleMasterProfileUpdated
{
    private MasterRepository $masterRepository;
    private MasterNotificationService $notificationService;
    private SearchIndexService $searchIndexService;
    private ProfileChangeAnalyzer $changeAnalyzer;
    private ProfileUpdateValidator $updateValidator;
    private ProfileMediaProcessor $mediaProcessor;

    public function __construct(
        MasterRepository $masterRepository,
        MasterNotificationService $notificationService,
        SearchIndexService $searchIndexService
    ) {
        $this->masterRepository = $masterRepository;
        $this->notificationService = $notificationService;
        $this->searchIndexService = $searchIndexService;
        $this->changeAnalyzer = new ProfileChangeAnalyzer();
        $this->updateValidator = new ProfileUpdateValidator(
            $masterRepository,
            app(\App\Infrastructure\Services\MediaService::class)
        );
        $this->mediaProcessor = new ProfileMediaProcessor(
            $masterRepository,
            app(\App\Infrastructure\Services\MediaService::class)
        );
    }

    /**
     * Обработка события MasterProfileUpdated
     */
    public function handle(MasterProfileUpdated $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                $masterProfile = $this->masterRepository->findById($event->masterProfileId);
                if (!$masterProfile) {
                    throw new Exception("Профиль мастера с ID {$event->masterProfileId} не найден");
                }

                $changes = $this->changeAnalyzer->analyzeChanges(
                    $masterProfile, 
                    $event->updatedData, 
                    $event->changedFields
                );

                $this->updateValidator->validateUpdates(
                    $masterProfile, 
                    $event->updatedData, 
                    $changes
                );

                $this->updateProfileData($masterProfile, $event->updatedData);

                $this->mediaProcessor->processMediaUpdates(
                    $masterProfile, 
                    $event->updatedData, 
                    $changes
                );

                $this->updateServicesIfChanged($masterProfile, $event->updatedData, $changes);
                $this->reindexInSearch($masterProfile, $changes);
                $this->logProfileChanges($masterProfile, $changes, $event);
                $this->sendUpdateNotifications($masterProfile, $changes, $event);

                Log::info('Master profile updated successfully', [
                    'master_profile_id' => $event->masterProfileId,
                    'user_id' => $event->userId,
                    'changed_fields' => $event->changedFields,
                    'critical_changes' => $changes['critical'],
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle MasterProfileUpdated event', [
                    'master_profile_id' => $event->masterProfileId,
                    'user_id' => $event->userId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }


    /**
     * Обновить данные профиля
     */
    private function updateProfileData($masterProfile, array $updatedData): void
    {
        // Фильтруем только разрешенные для обновления поля
        $allowedFields = [
            'display_name', 'bio', 'phone', 'experience_years', 'education',
            'languages', 'working_hours', 'location_type', 'travel_distance'
        ];

        $dataToUpdate = array_intersect_key($updatedData, array_flip($allowedFields));
        
        // Добавляем timestamp обновления
        $dataToUpdate['updated_at'] = now();

        // Обновляем профиль
        $masterProfile->update($dataToUpdate);
    }


    /**
     * Обновить услуги если изменились
     */
    private function updateServicesIfChanged($masterProfile, array $updatedData, array $changes): void
    {
        if (!in_array('services', $changes['services'])) {
            return;
        }

        // Синхронизируем услуги
        $this->masterRepository->syncServices($masterProfile->id, $updatedData['services']);
    }

    /**
     * Переиндексировать в поиске
     */
    private function reindexInSearch($masterProfile, array $changes): void
    {
        try {
            if ($this->changeAnalyzer->needsReindex($changes)) {
                $this->searchIndexService->reindexMasterProfile($masterProfile);
            }
        } catch (Exception $e) {
            Log::warning('Failed to reindex master profile in search', [
                'master_profile_id' => $masterProfile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логировать изменения профиля
     */
    private function logProfileChanges($masterProfile, array $changes, MasterProfileUpdated $event): void
    {
        $this->masterRepository->createChangeLog([
            'master_profile_id' => $masterProfile->id,
            'changed_by' => $event->updatedBy,
            'changed_fields' => $event->changedFields,
            'changes_summary' => $changes,
            'ip_address' => $event->ipAddress ?? null,
            'user_agent' => $event->userAgent ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Отправить уведомления об обновлениях
     */
    private function sendUpdateNotifications($masterProfile, array $changes, MasterProfileUpdated $event): void
    {
        try {
            // Уведомляем о критических изменениях
            if (!empty($changes['critical'])) {
                $this->notificationService->sendCriticalChangesNotification($masterProfile, $changes['critical']);
                
                // Уведомляем модераторов
                $this->notificationService->sendModerationRequiredNotification($masterProfile, $changes['critical']);
            }

            // Уведомляем о важных изменениях
            if (!empty($changes['important'])) {
                $this->notificationService->sendImportantChangesNotification($masterProfile, $changes['important']);
            }

            // Уведомляем об изменениях цен
            if (!empty($changes['pricing'])) {
                $this->notificationService->sendPricingChangesNotification($masterProfile, $changes['pricing']);
            }

        } catch (Exception $e) {
            Log::warning('Failed to send master profile update notifications', [
                'master_profile_id' => $masterProfile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(MasterProfileUpdated::class, [self::class, 'handle']);
    }
}