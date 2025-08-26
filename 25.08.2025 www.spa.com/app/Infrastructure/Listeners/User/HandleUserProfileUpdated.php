<?php

namespace App\Infrastructure\Listeners\User;

use App\Domain\User\Events\UserProfileUpdated;
use App\Domain\User\Repositories\UserRepository;
use App\Infrastructure\Services\EmailService;
use App\Infrastructure\Services\SearchIndexService;
use App\Infrastructure\Services\MediaService;
use App\Infrastructure\Handlers\UserProfile\ProfileValidationHandler;
use App\Infrastructure\Handlers\UserProfile\ProfileSyncHandler;
use App\Infrastructure\Handlers\UserProfile\ProfileAnalyticsHandler;
use App\Infrastructure\Handlers\UserProfile\ProfileNotificationHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Упрощенный обработчик обновления профиля пользователя
 * Делегирует логику специализированным обработчикам
 */
class HandleUserProfileUpdated
{
    protected ProfileValidationHandler $validationHandler;
    protected ProfileSyncHandler $syncHandler;
    protected ProfileAnalyticsHandler $analyticsHandler;
    protected ProfileNotificationHandler $notificationHandler;

    public function __construct(
        protected UserRepository $userRepository,
        EmailService $emailService,
        SearchIndexService $searchIndexService,
        MediaService $mediaService
    ) {
        $this->validationHandler = new ProfileValidationHandler($userRepository, $emailService);
        $this->syncHandler = new ProfileSyncHandler($mediaService, $searchIndexService);
        $this->analyticsHandler = new ProfileAnalyticsHandler($userRepository);
        $this->notificationHandler = new ProfileNotificationHandler($emailService, $this->analyticsHandler);
    }

    /**
     * Обработка события UserProfileUpdated
     */
    public function handle(UserProfileUpdated $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем пользователя
                $user = $this->userRepository->findById($event->userId);
                if (!$user) {
                    throw new Exception("Пользователь с ID {$event->userId} не найден");
                }

                // 2. Анализируем изменения
                $changes = $this->analyticsHandler->analyzeProfileChanges(
                    $user, 
                    $event->updatedFields, 
                    $event->oldData
                );

                // 3. Валидируем критические изменения
                $this->validationHandler->validateCriticalChanges($user, $changes);

                // 4. Обрабатываем медиа изменения
                $this->syncHandler->processMediaUpdates($user, $event->updatedFields, $changes);

                // 5. Синхронизируем с мастер-профилем если пользователь - мастер
                $this->syncHandler->syncWithMasterProfile($user, $changes);

                // 6. Обновляем поисковый индекс
                $this->syncHandler->updateSearchIndex($user, $changes);

                // 7. Рассчитываем новый процент заполнения профиля
                $completionPercentage = $this->analyticsHandler->calculateProfileCompletion($user);

                // 8. Создаем запись об изменениях
                $this->analyticsHandler->logProfileChanges($user, $event, $changes);

                // 9. Отправляем уведомления при критических изменениях
                $this->notificationHandler->sendUpdateNotifications($user, $changes, $event);

                // 10. Обновляем кеш пользователя
                $this->syncHandler->updateUserCache($user);

                // 11. Синхронизируем с внешними сервисами
                $this->syncHandler->syncWithExternalServices($user, $changes);

                // 12. Отслеживаем конверсионные события
                $this->analyticsHandler->trackConversionEvents($user, $changes);

                // 13. Отправляем уведомления администраторам при необходимости
                $this->notificationHandler->sendAdminNotifications($user, $changes, $event);

                Log::info('User profile updated successfully', [
                    'user_id' => $event->userId,
                    'updated_fields' => $event->updatedFields,
                    'critical_changes' => $changes['critical'],
                    'completion_percentage' => $completionPercentage,
                ]);

            } catch (Exception $e) {
                Log::error('Failed to handle UserProfileUpdated event', [
                    'user_id' => $event->userId,
                    'updated_fields' => $event->updatedFields,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Получить метрики изменений профиля
     */
    public function getProfileMetrics($user): array
    {
        return $this->analyticsHandler->getProfileChangeMetrics($user);
    }

    /**
     * Получить рекомендации по улучшению профиля
     */
    public function getImprovementSuggestions($user): array
    {
        return $this->analyticsHandler->getProfileImprovementSuggestions($user);
    }

    /**
     * Оценить риск мошенничества
     */
    public function assessFraudRisk($user, array $changes): string
    {
        return $this->analyticsHandler->assessFraudRisk($user, $changes);
    }

    /**
     * Проверить ограничения на изменения
     */
    public function checkChangeRateLimits($user, array $changes): array
    {
        return $this->validationHandler->checkChangeRateLimits($user, $changes);
    }

    /**
     * Валидировать формат данных
     */
    public function validateDataFormats($user, array $changes): array
    {
        return $this->validationHandler->validateDataFormats($user, $changes);
    }

    /**
     * Регистрация обработчика событий
     */
    public static function register(Dispatcher $events): void
    {
        $events->listen(UserProfileUpdated::class, [self::class, 'handle']);
    }
}