<?php

namespace App\Infrastructure\Listeners\Master;

use App\Domain\Master\Events\MasterStatusChanged;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Services\MasterNotificationService;
use App\Infrastructure\Services\SearchIndexService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик изменения статуса мастера
 * 
 * ФУНКЦИИ:
 * - Обновление статуса в БД
 * - Управление видимостью в поиске
 * - Обработка активных бронирований
 * - Отправка уведомлений о смене статуса
 * - Логирование изменений статуса
 */
class HandleMasterStatusChanged
{
    private MasterRepository $masterRepository;
    private MasterNotificationService $notificationService;
    private SearchIndexService $searchIndexService;

    public function __construct(
        MasterRepository $masterRepository,
        MasterNotificationService $notificationService,
        SearchIndexService $searchIndexService
    ) {
        $this->masterRepository = $masterRepository;
        $this->notificationService = $notificationService;
        $this->searchIndexService = $searchIndexService;
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
                $this->validateStatusChange($masterProfile, $event->oldStatus, $event->newStatus);

                // 3. Обновляем статус
                $this->updateMasterStatus($masterProfile, $event);

                // 4. Выполняем действия специфичные для статуса
                $this->handleStatusSpecificActions($masterProfile, $event);

                // 5. Обрабатываем активные бронирования
                $this->handleActiveBookings($masterProfile, $event);

                // 6. Обновляем поисковый индекс
                $this->updateSearchIndex($masterProfile, $event);

                // 7. Создаем запись об изменении статуса
                $this->logStatusChange($masterProfile, $event);

                // 8. Отправляем уведомления
                $this->sendStatusChangeNotifications($masterProfile, $event);

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
     * Валидировать смену статуса
     */
    private function validateStatusChange($masterProfile, string $oldStatus, string $newStatus): void
    {
        // Проверяем, что статус действительно изменился
        if ($masterProfile->status === $newStatus) {
            throw new Exception("Статус уже установлен в {$newStatus}");
        }

        // Проверяем допустимые переходы статуса
        $allowedTransitions = $this->getAllowedStatusTransitions();
        
        if (!isset($allowedTransitions[$oldStatus]) || 
            !in_array($newStatus, $allowedTransitions[$oldStatus])) {
            throw new Exception("Недопустимый переход статуса с {$oldStatus} на {$newStatus}");
        }
    }

    /**
     * Получить допустимые переходы статуса
     */
    private function getAllowedStatusTransitions(): array
    {
        return [
            'draft' => ['pending_moderation', 'archived'],
            'pending_moderation' => ['active', 'rejected', 'draft'],
            'active' => ['inactive', 'suspended', 'pending_moderation', 'archived'],
            'inactive' => ['active', 'archived'],
            'suspended' => ['active', 'banned'],
            'rejected' => ['draft', 'archived'],
            'banned' => ['suspended'], // Только админы могут разбанить
            'archived' => ['draft'], // Можно восстановить из архива
        ];
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
     * Выполнить действия специфичные для статуса
     */
    private function handleStatusSpecificActions($masterProfile, MasterStatusChanged $event): void
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
            'auto_unblock_at' => $this->calculateAutoUnblockDate($event->reason),
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
            'is_permanent' => $this->isPermanentBan($event->reason),
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

    /**
     * Рассчитать дату автоматической разблокировки
     */
    private function calculateAutoUnblockDate(?string $reason): ?\DateTime
    {
        if (!$reason) {
            return null;
        }

        // Временные блокировки
        $tempReasons = [
            'documents_check' => 7, // дней
            'quality_issues' => 14,
            'client_complaints' => 30,
        ];

        foreach ($tempReasons as $reasonKey => $days) {
            if (str_contains(strtolower($reason), $reasonKey)) {
                return (new \DateTime())->modify("+{$days} days");
            }
        }

        return null; // Постоянная блокировка
    }

    /**
     * Проверить, является ли бан permanent
     */
    private function isPermanentBan(?string $reason): bool
    {
        $permanentReasons = ['fraud', 'illegal_activities', 'severe_violations'];
        
        if (!$reason) {
            return false;
        }

        foreach ($permanentReasons as $permanentReason) {
            if (str_contains(strtolower($reason), $permanentReason)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Обработать активные бронирования
     */
    private function handleActiveBookings($masterProfile, MasterStatusChanged $event): void
    {
        // Получаем активные бронирования
        $activeBookings = $this->masterRepository->getActiveBookings($masterProfile->id);

        if ($activeBookings->isEmpty()) {
            return;
        }

        switch ($event->newStatus) {
            case 'suspended':
            case 'banned':
                // Отменяем будущие бронирования с возвратом средств
                $this->cancelFutureBookings($activeBookings, $event->reason);
                break;

            case 'inactive':
                // Уведомляем клиентов о временной недоступности
                $this->notifyClientsAboutUnavailability($activeBookings);
                break;
        }
    }

    /**
     * Отменить будущие бронирования
     */
    private function cancelFutureBookings($bookings, ?string $reason): void
    {
        foreach ($bookings as $booking) {
            if ($booking->scheduled_at > now()) {
                // Используем событие для отмены бронирования
                event(new \App\Domain\Booking\Events\BookingCancelled(
                    bookingId: $booking->id,
                    clientId: $booking->client_id,
                    masterId: $booking->master_id,
                    cancelledBy: null,
                    cancelledByRole: 'system',
                    reason: "Мастер недоступен: " . ($reason ?: 'Профиль заблокирован'),
                    cancelledAt: now()
                ));
            }
        }
    }

    /**
     * Уведомить клиентов о недоступности
     */
    private function notifyClientsAboutUnavailability($bookings): void
    {
        foreach ($bookings as $booking) {
            if ($booking->scheduled_at > now()) {
                $this->notificationService->sendMasterUnavailableNotification($booking);
            }
        }
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
     * Отправить уведомления о смене статуса
     */
    private function sendStatusChangeNotifications($masterProfile, MasterStatusChanged $event): void
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

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(MasterStatusChanged::class, [self::class, 'handle']);
    }
}