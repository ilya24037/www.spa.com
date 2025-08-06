<?php

namespace App\Infrastructure\Listeners\Master\Processors;

use App\Domain\Master\Repositories\MasterRepository;
use App\Infrastructure\Services\MediaService;
use Exception;

/**
 * Валидатор обновлений профиля
 */
class ProfileUpdateValidator
{
    private MasterRepository $masterRepository;
    private MediaService $mediaService;

    public function __construct(
        MasterRepository $masterRepository,
        MediaService $mediaService
    ) {
        $this->masterRepository = $masterRepository;
        $this->mediaService = $mediaService;
    }

    /**
     * Валидировать обновления
     */
    public function validateUpdates($masterProfile, array $updatedData, array $changes): void
    {
        if ($masterProfile->is_blocked) {
            throw new Exception("Профиль заблокирован для редактирования");
        }

        if (!empty($changes['critical'])) {
            $this->validateCriticalChanges($masterProfile, $changes['critical']);
        }

        if (!empty($changes['media'])) {
            $this->validateMediaFiles($updatedData);
        }
    }

    /**
     * Валидировать критические изменения
     */
    private function validateCriticalChanges($masterProfile, array $criticalChanges): void
    {
        $monthlyChanges = $this->masterRepository->getCriticalChangesCount($masterProfile->id, 30);
        $maxMonthlyChanges = config('master.max_critical_changes_per_month', 3);

        if ($monthlyChanges >= $maxMonthlyChanges) {
            throw new Exception("Превышен лимит критических изменений профиля в месяц ($maxMonthlyChanges)");
        }

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
}