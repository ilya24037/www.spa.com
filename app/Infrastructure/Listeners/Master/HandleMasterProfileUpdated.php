<?php

namespace App\Infrastructure\Listeners\Master;

use App\Domain\Master\Events\MasterProfileUpdated;
use App\Domain\Master\Repositories\MasterRepository;
use App\Domain\Master\Services\MasterNotificationService;
use App\Infrastructure\Services\SearchIndexService;
use App\Infrastructure\Services\MediaService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик обновления профиля мастера
 * 
 * ФУНКЦИИ:
 * - Обновление данных в БД
 * - Переиндексация в поиске
 * - Обработка медиа изменений
 * - Валидация изменений
 * - Уведомления о важных изменениях
 */
class HandleMasterProfileUpdated
{
    private MasterRepository $masterRepository;
    private MasterNotificationService $notificationService;
    private SearchIndexService $searchIndexService;
    private MediaService $mediaService;

    public function __construct(
        MasterRepository $masterRepository,
        MasterNotificationService $notificationService,
        SearchIndexService $searchIndexService,
        MediaService $mediaService
    ) {
        $this->masterRepository = $masterRepository;
        $this->notificationService = $notificationService;
        $this->searchIndexService = $searchIndexService;
        $this->mediaService = $mediaService;
    }

    /**
     * Обработка события MasterProfileUpdated
     */
    public function handle(MasterProfileUpdated $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Получаем профиль мастера
                $masterProfile = $this->masterRepository->findById($event->masterProfileId);
                if (!$masterProfile) {
                    throw new Exception("Профиль мастера с ID {$event->masterProfileId} не найден");
                }

                // 2. Анализируем изменения
                $changes = $this->analyzeChanges($masterProfile, $event->updatedData, $event->changedFields);

                // 3. Валидируем изменения
                $this->validateUpdates($masterProfile, $event->updatedData, $changes);

                // 4. Обновляем основные данные
                $this->updateProfileData($masterProfile, $event->updatedData);

                // 5. Обрабатываем медиа изменения
                $this->processMediaUpdates($masterProfile, $event->updatedData, $changes);

                // 6. Обновляем услуги если изменились
                $this->updateServicesIfChanged($masterProfile, $event->updatedData, $changes);

                // 7. Переиндексируем в поиске
                $this->reindexInSearch($masterProfile, $changes);

                // 8. Создаем запись об изменениях
                $this->logProfileChanges($masterProfile, $changes, $event);

                // 9. Отправляем уведомления при критических изменениях
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
     * Анализировать изменения
     */
    private function analyzeChanges($masterProfile, array $updatedData, array $changedFields): array
    {
        $changes = [
            'critical' => [], // Критические изменения (требуют модерации)
            'important' => [], // Важные изменения (уведомления)
            'media' => [], // Изменения медиа
            'services' => [], // Изменения услуг
            'contact' => [], // Контактные данные
            'pricing' => [], // Ценообразование
        ];

        foreach ($changedFields as $field) {
            $this->categorizeChange($field, $changes, $masterProfile, $updatedData);
        }

        return $changes;
    }

    /**
     * Категоризировать изменение
     */
    private function categorizeChange(string $field, array &$changes, $masterProfile, array $updatedData): void
    {
        // Критические изменения (требуют повторной модерации)
        $criticalFields = ['phone', 'location_type', 'city', 'certifications'];
        if (in_array($field, $criticalFields)) {
            $changes['critical'][] = $field;
            return;
        }

        // Важные изменения
        $importantFields = ['display_name', 'bio', 'experience_years', 'working_hours'];
        if (in_array($field, $importantFields)) {
            $changes['important'][] = $field;
            return;
        }

        // Медиа изменения
        $mediaFields = ['avatar', 'portfolio_photos', 'certificate_photos'];
        if (in_array($field, $mediaFields)) {
            $changes['media'][] = $field;
            return;
        }

        // Изменения услуг
        if ($field === 'services') {
            $changes['services'][] = $field;
            $this->analyzeServiceChanges($changes, $masterProfile, $updatedData);
            return;
        }

        // Контактные данные
        $contactFields = ['phone', 'email', 'languages'];
        if (in_array($field, $contactFields)) {
            $changes['contact'][] = $field;
            return;
        }
    }

    /**
     * Анализировать изменения услуг
     */
    private function analyzeServiceChanges(array &$changes, $masterProfile, array $updatedData): void
    {
        if (!isset($updatedData['services'])) {
            return;
        }

        $currentServices = $masterProfile->services->keyBy('id')->toArray();
        $updatedServices = collect($updatedData['services'])->keyBy('id');

        // Проверяем изменения цен
        foreach ($updatedServices as $serviceId => $serviceData) {
            if (isset($currentServices[$serviceId])) {
                $currentPrice = $currentServices[$serviceId]['price'];
                $newPrice = $serviceData['price'] ?? $currentPrice;
                
                if ($currentPrice != $newPrice) {
                    $changes['pricing'][] = "service_{$serviceId}_price";
                }
            }
        }
    }

    /**
     * Валидировать обновления
     */
    private function validateUpdates($masterProfile, array $updatedData, array $changes): void
    {
        // Проверяем, что профиль не заблокирован для редактирования
        if ($masterProfile->is_blocked) {
            throw new Exception("Профиль заблокирован для редактирования");
        }

        // Проверяем лимиты на критические изменения
        if (!empty($changes['critical'])) {
            $this->validateCriticalChanges($masterProfile, $changes['critical']);
        }

        // Валидируем медиа файлы
        if (!empty($changes['media'])) {
            $this->validateMediaFiles($updatedData);
        }
    }

    /**
     * Валидировать критические изменения
     */
    private function validateCriticalChanges($masterProfile, array $criticalChanges): void
    {
        // Проверяем лимит критических изменений в месяц
        $monthlyChanges = $this->masterRepository->getCriticalChangesCount($masterProfile->id, 30);
        $maxMonthlyChanges = config('master.max_critical_changes_per_month', 3);

        if ($monthlyChanges >= $maxMonthlyChanges) {
            throw new Exception("Превышен лимит критических изменений профиля в месяц ($maxMonthlyChanges)");
        }

        // Если есть критические изменения, переводим профиль на модерацию
        if ($masterProfile->status === 'active') {
            $this->masterRepository->updateStatus($masterProfile->id, 'pending_moderation');
        }
    }

    /**
     * Валидировать медиа файлы
     */
    private function validateMediaFiles(array $updatedData): void
    {
        $mediaFields = ['avatar', 'portfolio_photos', 'certificate_photos'];
        
        foreach ($mediaFields as $field) {
            if (isset($updatedData[$field])) {
                $this->mediaService->validateMediaFiles($updatedData[$field], $field);
            }
        }
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
     * Обработать медиа обновления
     */
    private function processMediaUpdates($masterProfile, array $updatedData, array $changes): void
    {
        foreach ($changes['media'] as $mediaType) {
            if (!isset($updatedData[$mediaType])) {
                continue;
            }

            try {
                switch ($mediaType) {
                    case 'avatar':
                        $this->updateAvatar($masterProfile, $updatedData['avatar']);
                        break;
                        
                    case 'portfolio_photos':
                        $this->updatePortfolioPhotos($masterProfile, $updatedData['portfolio_photos']);
                        break;
                        
                    case 'certificate_photos':
                        $this->updateCertificatePhotos($masterProfile, $updatedData['certificate_photos']);
                        break;
                }

            } catch (Exception $e) {
                Log::warning("Failed to update {$mediaType} for master profile", [
                    'master_profile_id' => $masterProfile->id,
                    'media_type' => $mediaType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Обновить аватар
     */
    private function updateAvatar($masterProfile, $avatarData): void
    {
        // Удаляем старый аватар
        if ($masterProfile->avatar_url) {
            $this->mediaService->deleteFile($masterProfile->avatar_url);
        }

        // Загружаем и обрабатываем новый
        $processedAvatar = $this->mediaService->processProfilePhoto(
            $avatarData,
            $masterProfile->id,
            'avatar'
        );

        $masterProfile->update(['avatar_url' => $processedAvatar['url']]);
    }

    /**
     * Обновить фото портфолио
     */
    private function updatePortfolioPhotos($masterProfile, array $portfolioData): void
    {
        // Получаем текущие фото
        $currentPhotos = $this->masterRepository->getPortfolioPhotos($masterProfile->id);
        
        // Обрабатываем новые/обновленные фото
        foreach ($portfolioData as $photoData) {
            if (isset($photoData['id']) && $photoData['id']) {
                // Обновляем существующее фото
                $this->masterRepository->updatePortfolioPhoto($photoData['id'], $photoData);
            } else {
                // Добавляем новое фото
                $processedPhoto = $this->mediaService->processPortfolioPhoto(
                    $photoData,
                    $masterProfile->id
                );

                $this->masterRepository->addPortfolioPhoto($masterProfile->id, [
                    'url' => $processedPhoto['url'],
                    'thumbnail_url' => $processedPhoto['thumbnail_url'],
                    'description' => $photoData['description'] ?? null,
                    'is_primary' => $photoData['is_primary'] ?? false,
                ]);
            }
        }
    }

    /**
     * Обновить фото сертификатов
     */
    private function updateCertificatePhotos($masterProfile, array $certificateData): void
    {
        // Аналогично портфолио, но для сертификатов
        foreach ($certificateData as $certData) {
            if (isset($certData['id']) && $certData['id']) {
                $this->masterRepository->updateCertificate($certData['id'], $certData);
            } else {
                $processedCert = $this->mediaService->processCertificatePhoto(
                    $certData,
                    $masterProfile->id
                );

                $this->masterRepository->addCertificate($masterProfile->id, [
                    'name' => $certData['name'] ?? 'Сертификат',
                    'issuer' => $certData['issuer'] ?? null,
                    'issued_at' => $certData['issued_at'] ?? null,
                    'photo_url' => $processedCert['url'],
                ]);
            }
        }
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
            // Переиндексируем только если изменились поля, влияющие на поиск
            $searchableFields = ['display_name', 'city', 'bio', 'services', 'location_type'];
            $needsReindex = !empty(array_intersect($searchableFields, 
                array_merge($changes['critical'], $changes['important'], $changes['services'])));

            if ($needsReindex) {
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