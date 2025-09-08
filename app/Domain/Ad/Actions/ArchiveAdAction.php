<?php

namespace App\Domain\Ad\Actions;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Support\Facades\Log;

/**
 * Action для архивирования объявления
 */
class ArchiveAdAction
{
    private AdRepository $adRepository;

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Архивировать объявление
     */
    public function execute(int $adId, int $userId): array
    {
        try {
            $ad = $this->adRepository->findOrFail($adId);

            // Проверяем права доступа
            if ($ad->user_id !== $userId) {
                return [
                    'success' => false,
                    'message' => 'У вас нет прав для архивирования этого объявления',
                ];
            }

            // Проверяем статус - можно архивировать из любого статуса, кроме уже архивированного
            if ($ad->status === AdStatus::ARCHIVED->value) {
                return [
                    'success' => false,
                    'message' => 'Объявление уже в архиве',
                ];
            }

            // Логируем текущий статус для отладки
            Log::info('Archiving ad with status', [
                'ad_id' => $ad->id,
                'current_status' => $ad->status,
                'user_id' => $userId,
            ]);

            // Архивируем через репозиторий
            $this->adRepository->updateAd($ad, [
                'status' => AdStatus::ARCHIVED->value,
                'archived_at' => now()
            ]);

            Log::info('Ad archived', [
                'ad_id' => $ad->id,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'Объявление перемещено в архив',
                'ad' => $ad,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to archive ad', [
                'ad_id' => $adId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при архивировании объявления',
            ];
        }
    }
}