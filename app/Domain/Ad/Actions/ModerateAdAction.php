<?php

namespace App\Domain\Ad\Actions;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Repositories\AdRepository;
use App\Domain\Ad\Enums\AdStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action для модерации объявления
 */
class ModerateAdAction
{
    private AdRepository $adRepository;

    public function __construct(AdRepository $adRepository)
    {
        $this->adRepository = $adRepository;
    }

    /**
     * Модерировать объявление
     */
    public function execute(int $adId, bool $approved, ?string $reason = null, ?int $moderatorId = null): array
    {
        try {
            return DB::transaction(function () use ($adId, $approved, $reason, $moderatorId) {
                $ad = $this->adRepository->findOrFail($adId);

                // Проверяем, можно ли модерировать
                if (!$this->canModerate($ad)) {
                    return [
                        'success' => false,
                        'message' => 'Объявление не может быть промодерировано в текущем статусе',
                    ];
                }

                // Подготавливаем данные для обновления
                $updateData = [
                    'moderated_at' => now(),
                    'moderated_by' => $moderatorId
                ];

                if ($reason) {
                    $updateData['moderation_reason'] = $reason;
                }

                if ($approved) {
                    // Одобряем объявление
                    $updateData['status'] = AdStatus::ACTIVE->value;
                    $updateData['published_at'] = now();
                    $updateData['expires_at'] = now()->addDays(30);
                    
                    $message = 'Объявление одобрено и опубликовано';
                    $logAction = 'approved';
                } else {
                    // Отклоняем объявление
                    $updateData['status'] = AdStatus::REJECTED->value;
                    
                    $message = 'Объявление отклонено';
                    $logAction = 'rejected';
                }

                // Обновляем через репозиторий
                $this->adRepository->updateAd($ad, $updateData);

                Log::info("Ad {$logAction} by moderator", [
                    'ad_id' => $ad->id,
                    'moderator_id' => $moderatorId,
                    'reason' => $reason,
                    'approved' => $approved,
                ]);

                return [
                    'success' => true,
                    'message' => $message,
                    'ad' => $ad,
                    'approved' => $approved,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to moderate ad', [
                'ad_id' => $adId,
                'moderator_id' => $moderatorId,
                'approved' => $approved,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при модерации объявления',
            ];
        }
    }

    /**
     * Одобрить объявление (удобный метод)
     */
    public function approve(int $adId, ?string $note = null, ?int $moderatorId = null): array
    {
        return $this->execute($adId, true, $note, $moderatorId);
    }

    /**
     * Отклонить объявление (удобный метод)
     */
    public function reject(int $adId, string $reason, ?int $moderatorId = null): array
    {
        return $this->execute($adId, false, $reason, $moderatorId);
    }

    /**
     * Проверить, можно ли модерировать объявление
     */
    private function canModerate(Ad $ad): bool
    {
        return in_array($ad->status, [
            AdStatus::WAITING_PAYMENT->value,
            AdStatus::ACTIVE->value, // Для повторной модерации
        ]);
    }

    /**
     * Массовая модерация объявлений
     */
    public function moderateBulk(array $adIds, bool $approved, ?string $reason = null, ?int $moderatorId = null): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($adIds),
        ];

        foreach ($adIds as $adId) {
            $result = $this->execute($adId, $approved, $reason, $moderatorId);
            
            if ($result['success']) {
                $results['success'][] = $adId;
            } else {
                $results['failed'][] = [
                    'ad_id' => $adId,
                    'message' => $result['message'],
                ];
            }
        }

        $results['success_count'] = count($results['success']);
        $results['failed_count'] = count($results['failed']);

        Log::info('Bulk moderation completed', [
            'moderator_id' => $moderatorId,
            'total' => $results['total'],
            'success' => $results['success_count'],
            'failed' => $results['failed_count'],
            'approved' => $approved,
        ]);

        return $results;
    }
}