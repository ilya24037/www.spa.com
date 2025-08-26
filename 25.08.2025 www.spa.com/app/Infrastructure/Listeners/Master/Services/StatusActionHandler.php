<?php

namespace App\Infrastructure\Listeners\Master\Services;

use App\Domain\Master\Events\MasterStatusChanged;
use App\Domain\Master\Repositories\MasterRepository;
use Illuminate\Support\Facades\Log;

/**
 * Обработчик действий для разных статусов мастера
 */
class StatusActionHandler
{
    private MasterRepository $masterRepository;
    private StatusValidationService $validationService;

    public function __construct(
        MasterRepository $masterRepository,
        StatusValidationService $validationService
    ) {
        $this->masterRepository = $masterRepository;
        $this->validationService = $validationService;
    }

    /**
     * Выполнить действия специфичные для статуса
     */
    public function handleStatusSpecificActions($masterProfile, MasterStatusChanged $event): void
    {
        switch ($event->newStatus) {
            case 'active':
                $this->handleActivatedStatus($masterProfile, $event);
                break;

            case 'inactive':
                $this->handleInactiveStatus($masterProfile, $event);
                break;

            case 'suspended':
                $this->handleSuspendedStatus($masterProfile, $event);
                break;

            case 'banned':
                $this->handleBannedStatus($masterProfile, $event);
                break;

            case 'rejected':
                $this->handleRejectedStatus($masterProfile, $event);
                break;

            case 'archived':
                $this->handleArchivedStatus($masterProfile, $event);
                break;
        }
    }

    /**
     * Обработка активации мастера
     */
    private function handleActivatedStatus($masterProfile, MasterStatusChanged $event): void
    {
        // Проверяем готовность профиля
        $completionScore = $this->masterRepository->calculateProfileCompletion($masterProfile->id);
        
        if ($completionScore < 80) {
            Log::warning('Master activated with incomplete profile', [
                'master_profile_id' => $masterProfile->id,
                'completion_score' => $completionScore,
            ]);
        }

        // Включаем в рекомендации
        $this->masterRepository->enableRecommendations($masterProfile->id);

        // Активируем все услуги
        $this->masterRepository->activateAllServices($masterProfile->id);
    }

    /**
     * Обработка деактивации мастера
     */
    private function handleInactiveStatus($masterProfile, MasterStatusChanged $event): void
    {
        // Исключаем из рекомендаций
        $this->masterRepository->disableRecommendations($masterProfile->id);

        // Деактивируем все услуги
        $this->masterRepository->deactivateAllServices($masterProfile->id);
    }

    /**
     * Обработка блокировки мастера
     */
    private function handleSuspendedStatus($masterProfile, MasterStatusChanged $event): void
    {
        // Блокируем создание новых бронирований
        $this->masterRepository->blockNewBookings($masterProfile->id);

        // Создаем запись о блокировке
        $this->masterRepository->createSuspensionRecord([
            'master_profile_id' => $masterProfile->id,
            'reason' => $event->reason,
            'suspended_by' => $event->changedBy,
            'suspended_at' => $event->changedAt,
            'auto_unblock_at' => $this->validationService->calculateAutoUnblockDate($event->reason),
        ]);
    }

    /**
     * Обработка бана мастера
     */
    private function handleBannedStatus($masterProfile, MasterStatusChanged $event): void
    {
        // Полная блокировка профиля
        $this->masterRepository->blockProfile($masterProfile->id);

        // Создаем запись о бане
        $this->masterRepository->createBanRecord([
            'master_profile_id' => $masterProfile->id,
            'reason' => $event->reason,
            'banned_by' => $event->changedBy,
            'banned_at' => $event->changedAt,
            'is_permanent' => $this->validationService->isPermanentBan($event->reason),
        ]);
    }

    /**
     * Обработка отклонения профиля
     */
    private function handleRejectedStatus($masterProfile, MasterStatusChanged $event): void
    {
        // Создаем запись об отклонении с комментариями
        $this->masterRepository->createRejectionRecord([
            'master_profile_id' => $masterProfile->id,
            'reason' => $event->reason,
            'rejected_by' => $event->changedBy,
            'rejected_at' => $event->changedAt,
            'feedback' => $event->moderatorFeedback ?? null,
        ]);
    }

    /**
     * Обработка архивирования профиля
     */
    private function handleArchivedStatus($masterProfile, MasterStatusChanged $event): void
    {
        // Архивируем все связанные данные
        $this->masterRepository->archiveRelatedData($masterProfile->id);
    }
}