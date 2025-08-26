<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\Master\Repositories\MasterRepository;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления рейтингом мастеров
 */
class MasterRatingService
{
    private MasterRepository $masterRepository;

    public function __construct(MasterRepository $masterRepository)
    {
        $this->masterRepository = $masterRepository;
    }

    /**
     * Пересчитать рейтинг мастера по ID пользователя
     */
    public function recalculateRating(int $userId): void
    {
        try {
            // Находим профиль мастера по user_id
            $masterProfile = MasterProfile::where('user_id', $userId)->first();
            
            if (!$masterProfile) {
                Log::warning('Master profile not found for rating recalculation', [
                    'user_id' => $userId
                ]);
                return;
            }

            // Используем метод репозитория для обновления рейтинга
            $this->masterRepository->updateRating($masterProfile);
            
            // Обновляем уровень мастера на основе нового рейтинга
            $this->masterRepository->updateLevel($masterProfile);

            Log::info('Master rating recalculated', [
                'user_id' => $userId,
                'master_id' => $masterProfile->id,
                'new_rating' => $masterProfile->rating,
                'reviews_count' => $masterProfile->reviews_count
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to recalculate master rating', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Пересчитать рейтинг мастера по профилю
     */
    public function recalculateRatingByProfile(MasterProfile $masterProfile): void
    {
        $this->masterRepository->updateRating($masterProfile);
        $this->masterRepository->updateLevel($masterProfile);
    }

    /**
     * Массовый пересчет рейтингов (для cron задач)
     */
    public function recalculateAllRatings(): int
    {
        $updated = 0;

        MasterProfile::where('status', 'active')
            ->chunk(100, function ($masters) use (&$updated) {
                foreach ($masters as $master) {
                    $this->recalculateRatingByProfile($master);
                    $updated++;
                }
            });

        Log::info('Mass rating recalculation completed', [
            'updated_count' => $updated
        ]);

        return $updated;
    }
}